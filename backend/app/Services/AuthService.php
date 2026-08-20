<?php

namespace App\Services;

use App\Enums\ErrorCode;
use App\Enums\UserStatus;
use App\Exceptions\ZakatException;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/** PRD 01B dan 01C — login, password, dan session. */
class AuthService
{
    public function __construct(private readonly AuditService $audit) {}

    /** PRD 01 §9–§10 — login dengan email atau username. */
    public function login(Request $request, string $identifier, string $password, bool $remember = false): User
    {
        $throttleKey = Str::lower($identifier).'|'.$request->ip();
        $this->assertNotThrottled($throttleKey);

        $user = User::findByLoginIdentifier($identifier);

        // PRD 01 §21 — lock yang sudah lewat masa berlakunya dilepas sebelum dicek.
        $user?->releaseExpiredLock();

        if ($user === null || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($throttleKey, config('zakat.login.decay_seconds'));
            $this->registerFailedAttempt($user, $identifier, $request);

            // PRD 01 §10 — jangan bocorkan apakah email atau password yang salah.
            throw new ZakatException(ErrorCode::Unauthorized, 'Kredensial tidak valid.');
        }

        if (! $user->canLogin()) {
            $this->audit->record('login_failed', $user, context: [
                'identifier' => $identifier,
                'reason' => 'status_'.$user->status->value,
            ], actorId: $user->getKey());

            throw new ZakatException(
                ErrorCode::Forbidden,
                "Akun berstatus {$user->status->value} tidak dapat digunakan untuk login."
            );
        }

        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();
        Auth::guard('web')->login($user, $remember);

        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->saveQuietly();

        $this->audit->record('login', $user, actorId: $user->getKey());

        return $user;
    }

    public function logout(Request $request): void
    {
        $user = $request->user();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Guard sanctum menyimpan user hasil resolusi. Tanpa ini, user lama masih
        // terbaca pada request berikutnya dalam proses yang sama (test dan Octane).
        Auth::forgetGuards();

        if ($user !== null) {
            $this->audit->record('logout', $user, actorId: $user->getKey());
        }
    }

    /** PRD 01 §15 — ganti password lalu cabut session lain. */
    public function changePassword(Request $request, User $user, string $current, string $new): void
    {
        if (! Hash::check($current, $user->password)) {
            throw new ZakatException(ErrorCode::ValidationError, 'Password saat ini tidak sesuai.', [
                'current_password' => ['Password saat ini tidak sesuai.'],
            ]);
        }

        DB::transaction(function () use ($request, $user, $new) {
            $user->forceFill(['password' => $new])->saveQuietly();
            $this->revokeOtherSessions($user, $request->session()->getId());
        });

        $this->audit->record('password_changed', $user, actorId: $user->getKey());
    }

    /** PRD 01 §16 — response selalu generik agar email tidak bisa dienumerasi. */
    public function sendPasswordResetLink(string $email): void
    {
        Password::sendResetLink(['email' => $email]);

        $this->audit->record('password_reset_requested', context: ['email' => $email]);
    }

    /** @param array<string, string> $credentials */
    public function resetPassword(array $credentials): void
    {
        $status = Password::reset($credentials, function (User $user, string $password) {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);

            // PRD 01 §19 — password change mengakhiri seluruh session lama.
            if ($user->status === UserStatus::Locked) {
                $user->status = UserStatus::Active;
            }

            $user->saveQuietly();
            $this->revokeOtherSessions($user, null);

            $this->audit->record('password_reset_completed', $user, actorId: $user->getKey());
        });

        if ($status !== Password::PasswordReset) {
            throw new ZakatException(ErrorCode::ValidationError, 'Token reset password tidak valid atau sudah kedaluwarsa.', [
                'token' => [__($status)],
            ]);
        }
    }

    /** PRD 01 §18 — session aktif user. */
    public function sessions(User $user): Collection
    {
        return DB::table('sessions')
            ->where('user_id', $user->getKey())
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity', 'created_at']);
    }

    public function revokeSession(User $user, string $sessionId): void
    {
        $deleted = DB::table('sessions')
            ->where('user_id', $user->getKey())
            ->where('id', $sessionId)
            ->delete();

        if ($deleted === 0) {
            throw ZakatException::notFound('Session tidak ditemukan.');
        }

        $this->audit->record('session_revoked', $user, context: ['session_id' => $sessionId], actorId: $user->getKey());
    }

    /** PRD 01 §36 — hapus semua session kecuali yang sedang dipakai. */
    public function revokeOtherSessions(User $user, ?string $keepSessionId): int
    {
        return DB::table('sessions')
            ->where('user_id', $user->getKey())
            ->when($keepSessionId !== null, fn ($query) => $query->where('id', '!=', $keepSessionId))
            ->delete();
    }

    private function assertNotThrottled(string $key): void
    {
        if (RateLimiter::tooManyAttempts($key, config('zakat.login.max_attempts'))) {
            throw new ZakatException(
                ErrorCode::TooManyRequests,
                'Terlalu banyak percobaan login. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.'
            );
        }
    }

    /** PRD 01 §20 dan §21 — hitung kegagalan dan kunci akun bila melewati ambang. */
    private function registerFailedAttempt(?User $user, string $identifier, Request $request): void
    {
        if ($user === null) {
            $this->audit->record('login_failed', context: [
                'identifier' => $identifier,
                'reason' => 'unknown_identifier',
            ]);

            return;
        }

        $attempts = $user->failed_login_attempts + 1;
        $threshold = (int) config('zakat.login.lock_threshold');

        $user->failed_login_attempts = $attempts;

        if ($attempts >= $threshold && $user->status === UserStatus::Active) {
            $user->status = UserStatus::Locked;
            $user->locked_until = now()->addMinutes((int) config('zakat.login.lock_minutes'));
        }

        $user->saveQuietly();

        $this->audit->record('login_failed', $user, context: [
            'identifier' => $identifier,
            'reason' => 'invalid_password',
            'attempts' => $attempts,
        ], actorId: $user->getKey());

        if ($user->status === UserStatus::Locked && $user->wasChanged('status')) {
            $this->audit->record('account_locked', $user, context: [
                'until' => $user->locked_until?->toIso8601String(),
            ], actorId: $user->getKey());
        }
    }
}

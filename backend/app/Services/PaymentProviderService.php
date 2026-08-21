<?php

namespace App\Services;

use App\Enums\PaymentProviderStatus;
use App\Exceptions\ZakatException;
use App\Models\PaymentProvider;
use App\Payments\PaymentDriverManager;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/** PRD 13B — konfigurasi provider pembayaran. */
class PaymentProviderService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly PaymentDriverManager $drivers,
    ) {}

    /** @return Collection<int, PaymentProvider> */
    public function list(): Collection
    {
        return PaymentProvider::query()->orderBy('provider_code')->get();
    }

    public function find(string $id): PaymentProvider
    {
        return PaymentProvider::query()->find($id) ?? throw ZakatException::notFound('Payment provider tidak ditemukan.');
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): PaymentProvider
    {
        if (! $this->drivers->supports($data['driver'])) {
            throw ZakatException::conflict("Driver [{$data['driver']}] belum tersedia. Tersedia: ".implode(', ', $this->drivers->available()).'.');
        }

        $provider = new PaymentProvider;
        $provider->fill(collect($data)->only(['name', 'driver', 'sandbox_mode'])->all());
        $provider->organization_id = OrganizationContext::requireId();
        $provider->provider_code = strtoupper($data['provider_code']);
        $provider->status = PaymentProviderStatus::Inactive;
        $this->applyCredentials($provider, $data);
        $provider->save();

        return $provider;
    }

    /** @param array<string, mixed> $data */
    public function update(PaymentProvider $provider, array $data): PaymentProvider
    {
        // provider_code immutable setelah dibuat (PRD 13B §5).
        $provider->fill(collect($data)->only(['name', 'sandbox_mode'])->all());
        $this->applyCredentials($provider, $data);
        $provider->save();

        return $provider;
    }

    public function changeStatus(PaymentProvider $provider, PaymentProviderStatus $status): PaymentProvider
    {
        if ($status === PaymentProviderStatus::Active && ! $provider->hasWebhookSecret()) {
            // PRD 13H §17 — webhook wajib diverifikasi, jadi provider tanpa
            // rahasia tidak boleh aktif dan menerima notifikasi apa pun.
            throw ZakatException::conflict('Provider belum memiliki webhook secret, tidak dapat diaktifkan.');
        }

        $previous = $provider->status;
        $provider->status = $status;
        $provider->saveQuietly();

        $this->audit->record(
            $status === PaymentProviderStatus::Active ? 'payment_provider_activated' : 'payment_provider_deactivated',
            $provider,
            ['status' => $previous->value],
            ['status' => $status->value],
        );

        return $provider;
    }

    /**
     * Uji kesiapan konfigurasi tanpa membocorkan isinya (PRD 13T §40).
     *
     * @return array<string, mixed>
     */
    public function test(PaymentProvider $provider): array
    {
        return [
            'driver' => $provider->driver,
            'driver_available' => $this->drivers->supports($provider->driver),
            'sandbox_mode' => $provider->sandbox_mode,
            'webhook_secret_configured' => $provider->hasWebhookSecret(),
            'configured_keys' => $provider->configuredKeys(),
        ];
    }

    /**
     * Kredensial hanya ditulis, tidak pernah dibaca balik lewat API.
     *
     * @param  array<string, mixed>  $data
     */
    private function applyCredentials(PaymentProvider $provider, array $data): void
    {
        if (array_key_exists('config', $data) && $data['config'] !== null) {
            $provider->config_encrypted = $data['config'];
        }

        if (array_key_exists('webhook_secret', $data) && filled($data['webhook_secret'])) {
            $provider->webhook_secret_encrypted = $data['webhook_secret'];
        }

        // Sekali diaktifkan tanpa rahasia, sediakan satu yang kuat supaya tidak
        // ada provider yang berjalan tanpa verifikasi tanda tangan.
        if (blank($provider->webhook_secret_encrypted) && ($data['generate_webhook_secret'] ?? false)) {
            $provider->webhook_secret_encrypted = Str::random(48);
        }
    }
}

<?php

namespace App\Services;

use App\Exceptions\ZakatException;
use App\Models\Setting;
use App\Support\OrganizationContext;
use App\Support\SettingRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * PRD 20 — System Settings.
 *
 * Nilai efektif dipasang kembali ke config('zakat.*') oleh middleware, sehingga
 * seluruh pemanggil lama (AuthService, ApiRequest, service pagination) tidak
 * perlu tahu asal nilainya. Urutan resolusi mengikuti PRD 02 §24:
 * System Default -> Organization Setting.
 */
class SettingService
{
    public function __construct(private readonly AuditService $audits) {}

    /** Nilai tersimpan untuk satu scope, tanpa default. */
    public function stored(?string $organizationId): array
    {
        $cacheKey = $organizationId === null ? 'settings:global' : 'settings:org:'.$organizationId;

        return Cache::rememberForever($cacheKey, fn () => Setting::query()
            ->where('organization_id', $organizationId)
            ->pluck('value', 'key')
            ->all());
    }

    /** Nilai efektif: default config ditimpa global, lalu ditimpa organisasi. */
    public function effective(?string $organizationId): array
    {
        $values = [];

        foreach (array_keys(SettingRegistry::KEYS) as $key) {
            $values[$key] = SettingRegistry::default($key);
        }

        foreach ($this->stored(null) as $key => $value) {
            $values[$key] = $value;
        }

        if ($organizationId !== null) {
            foreach ($this->stored($organizationId) as $key => $value) {
                // Organisasi tidak boleh menimpa konfigurasi keamanan global.
                if (SettingRegistry::scopeOf($key) === SettingRegistry::ORGANIZATION) {
                    $values[$key] = $value;
                }
            }
        }

        return array_intersect_key($values, SettingRegistry::KEYS);
    }

    /** Memasang nilai efektif ke config runtime. */
    public function apply(?string $organizationId): void
    {
        foreach ($this->effective($organizationId) as $key => $value) {
            config()->set('zakat.'.$key, SettingRegistry::cast($key, $value));
        }
    }

    /** Bentuk untuk UI: nilai efektif berikut asal dan defaultnya. */
    public function describe(?string $organizationId): array
    {
        $effective = $this->effective($organizationId);
        $global = $this->stored(null);
        $organization = $organizationId === null ? [] : $this->stored($organizationId);

        return collect(SettingRegistry::KEYS)
            ->map(fn (array $meta, string $key) => [
                'key' => $key,
                'label' => $meta['label'],
                'group' => $meta['group'],
                'scope' => $meta['scope'],
                'type' => $meta['type'],
                'value' => SettingRegistry::cast($key, $effective[$key]),
                'default_value' => SettingRegistry::cast($key, SettingRegistry::default($key)),
                'source' => match (true) {
                    array_key_exists($key, $organization) && $meta['scope'] === SettingRegistry::ORGANIZATION => 'ORGANIZATION',
                    array_key_exists($key, $global) => 'GLOBAL',
                    default => 'DEFAULT',
                },
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<int, array<string, mixed>>
     */
    public function update(array $values, string $scope): array
    {
        $organizationId = $scope === SettingRegistry::GLOBAL ? null : OrganizationContext::requireId();

        if ($scope === SettingRegistry::GLOBAL && ! Auth::user()?->isPlatformAdmin()) {
            throw ZakatException::forbidden('Hanya platform admin yang dapat mengubah setting global.');
        }

        foreach (array_keys($values) as $key) {
            if (SettingRegistry::scopeOf($key) !== $scope) {
                throw ZakatException::forbidden("Setting {$key} tidak dapat diubah pada scope {$scope}.");
            }
        }

        $before = $this->effective($organizationId);

        DB::transaction(function () use ($values, $organizationId) {
            foreach ($values as $key => $value) {
                Setting::query()->updateOrCreate(
                    ['organization_id' => $organizationId, 'key' => $key],
                    ['value' => SettingRegistry::cast($key, $value), 'updated_by' => Auth::id()],
                );
            }
        });

        $this->flush($organizationId);

        $after = $this->effective($organizationId);
        $changed = array_keys(array_diff_assoc($after, $before));

        if ($changed !== []) {
            $this->audits->record(
                action: 'setting_updated',
                before: array_intersect_key($before, array_flip($changed)),
                after: array_intersect_key($after, array_flip($changed)),
                context: ['scope' => $scope],
                organizationId: $organizationId,
                description: 'Perubahan setting: '.implode(', ', $changed),
            );
        }

        $this->apply($organizationId);

        return $this->describe($organizationId);
    }

    /** Mengembalikan satu key ke nilai warisan di atasnya. */
    public function reset(string $key, string $scope): array
    {
        $organizationId = $scope === SettingRegistry::GLOBAL ? null : OrganizationContext::requireId();

        if ($scope === SettingRegistry::GLOBAL && ! Auth::user()?->isPlatformAdmin()) {
            throw ZakatException::forbidden('Hanya platform admin yang dapat mengubah setting global.');
        }

        if (SettingRegistry::scopeOf($key) !== $scope) {
            throw ZakatException::forbidden("Setting {$key} tidak dapat diubah pada scope {$scope}.");
        }

        Setting::query()->where('organization_id', $organizationId)->where('key', $key)->delete();

        $this->flush($organizationId);
        $this->audits->record(
            action: 'setting_reset',
            context: ['scope' => $scope, 'key' => $key],
            organizationId: $organizationId,
            description: "Setting {$key} dikembalikan ke nilai bawaan.",
        );
        $this->apply($organizationId);

        return $this->describe($organizationId);
    }

    private function flush(?string $organizationId): void
    {
        Cache::forget('settings:global');

        if ($organizationId !== null) {
            Cache::forget('settings:org:'.$organizationId);
        }
    }
}

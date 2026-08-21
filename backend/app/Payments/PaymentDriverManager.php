<?php

namespace App\Payments;

use App\Exceptions\ZakatException;
use App\Models\PaymentProvider;
use App\Payments\Contracts\PaymentProviderDriver;
use App\Payments\Drivers\ManualPaymentDriver;

/**
 * PRD 13B §6 — pemetaan kolom `driver` ke implementasinya.
 *
 * Provider baru cukup didaftarkan di sini setelah kelas driver-nya ditulis.
 */
class PaymentDriverManager
{
    /** @var array<string, class-string<PaymentProviderDriver>> */
    private array $drivers = [
        ManualPaymentDriver::CODE => ManualPaymentDriver::class,
    ];

    /** @return array<int, string> */
    public function available(): array
    {
        return array_keys($this->drivers);
    }

    public function supports(string $driver): bool
    {
        return isset($this->drivers[$driver]);
    }

    public function for(PaymentProvider $provider): PaymentProviderDriver
    {
        $class = $this->drivers[$provider->driver] ?? null;

        if ($class === null) {
            throw ZakatException::conflict("Driver pembayaran [{$provider->driver}] belum tersedia.");
        }

        return app($class);
    }
}

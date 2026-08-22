<?php

namespace App\Notifications\Channels;

use App\Enums\EntityStatus;
use App\Models\Notification;
use App\Models\NotificationDelivery;
use App\Models\NotificationEmailConfig;
use App\Models\User;
use App\Notifications\Contracts\NotificationChannelDriver;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/** PRD 16H — email, memakai SMTP organisasi bila ada, kalau tidak mailer default. */
class EmailChannel implements NotificationChannelDriver
{
    public function resolveAddress(Notification $notification): ?string
    {
        return User::query()->whereKey($notification->recipient_id)->value('email');
    }

    public function send(NotificationDelivery $delivery): array
    {
        $notification = $delivery->notification;
        $config = $this->configFor($notification->organization_id);

        $mailer = $config === null ? Mail::mailer() : Mail::mailer($this->registerMailer($config));

        $mailer->raw($notification->message, function ($message) use ($delivery, $notification, $config) {
            $message->to($delivery->recipient_address)->subject($notification->title);

            if ($config?->from_email) {
                $message->from($config->from_email, $config->from_name);
            }
        });

        return ['provider' => $config?->driver ?? config('mail.default'), 'provider_reference' => null, 'delivered' => false];
    }

    private function configFor(string $organizationId): ?NotificationEmailConfig
    {
        return NotificationEmailConfig::query()
            ->acrossOrganizations()
            ->where('organization_id', $organizationId)
            ->where('status', EntityStatus::Active)
            ->first();
    }

    /** Mailer per organisasi didaftarkan saat dibutuhkan, tidak disimpan di config file. */
    private function registerMailer(NotificationEmailConfig $config): string
    {
        $name = 'org_'.Str::lower($config->organization_id);

        config()->set('mail.mailers.'.$name, [
            'transport' => $config->driver,
            'host' => $config->host,
            'port' => $config->port,
            'encryption' => $config->encryption,
            'username' => $config->username_encrypted,
            'password' => $config->password_encrypted,
        ]);

        return $name;
    }
}

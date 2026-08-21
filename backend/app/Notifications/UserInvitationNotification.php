<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** PRD 01 §8 — undangan aktivasi akun. */
class UserInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim((string) config('app.frontend_url'), '/')
            .'/accept-invitation?token='.$this->token
            .'&email='.urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Undangan Akun '.config('app.name'))
            ->greeting('Halo '.$notifiable->name)
            ->line('Anda diundang untuk menggunakan '.config('app.name').'.')
            ->action('Atur Password', $url)
            ->line('Tautan ini berlaku '.config('zakat.invitation.expires_hours').' jam dan hanya dapat dipakai satu kali.');
    }
}

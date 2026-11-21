<?php

namespace App\Notifications;

use App\Models\MemberInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberInviteNotification extends Notification
{
    use Queueable;

    public function __construct(public MemberInvitation $invitation)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('invite.show', ['token' => $this->invitation->token]);

        return (new MailMessage)
            ->subject('Welcome to ' . config('app.name') . ' — Set Up Your Account')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your gym membership account has been created at the front desk.')
            ->line('Click the button below to set your password and access your Member Portal, QR check-in pass, and class bookings.')
            ->action('Set My Password', $url)
            ->line('This link expires in 72 hours.');
    }
}

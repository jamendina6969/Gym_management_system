<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(public Subscription $subscription)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $days = $this->subscription->daysRemaining();

        return (new MailMessage)
            ->subject('Your ' . config('app.name') . ' Membership Expires in ' . $days . ' ' . str('Day')->plural($days))
            ->greeting('Hi ' . $notifiable->name . ',')
            ->line('Your ' . $this->subscription->plan->name . ' plan will expire on ' . $this->subscription->ends_at->format('M d, Y') . '.')
            ->line('Renew online anytime through your Member Portal to keep uninterrupted gym access.')
            ->action('Renew Membership', route('portal.membership'));
    }
}

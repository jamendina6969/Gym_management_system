<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceiptNotification extends Notification
{
    use Queueable;

    public function __construct(public Payment $payment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $symbol = config('app.currency_symbol', '₱');

        return (new MailMessage)
            ->subject('Payment Receipt ' . $this->payment->invoice_number . ' — ' . config('app.name'))
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('We received your payment of ' . $symbol . number_format((float) $this->payment->amount, 2) . '.')
            ->line('Invoice Number: ' . $this->payment->invoice_number)
            ->action('Download PDF Receipt', route('invoices.download', $this->payment))
            ->line('Thank you for training with ' . config('app.name') . '!');
    }
}

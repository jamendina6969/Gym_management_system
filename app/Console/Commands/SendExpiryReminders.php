<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\Subscription;
use App\Notifications\MembershipExpiringNotification;
use Illuminate\Console\Command;

class SendExpiryReminders extends Command
{
    protected $signature = 'gym:send-expiry-reminders';

    protected $description = 'Expire past-due subscriptions and email members whose plans expire soon.';

    public function handle(): int
    {
        // 1. Expire past-due active subscriptions
        $expiredSubs = Subscription::query()
            ->with('member')
            ->where('status', 'active')
            ->whereDate('ends_at', '<', now()->toDateString())
            ->get();

        foreach ($expiredSubs as $sub) {
            $sub->update(['status' => 'expired']);
            $sub->member->syncStatusFromSubscriptions();
        }

        // 2. Send reminders N days before expiry
        $reminderDays = max(1, (int) Setting::getValue('expiry_reminder_days', null, 7));
        $targetDate = now()->addDays($reminderDays)->toDateString();

        $expiringSoon = Subscription::query()
            ->with(['member.user', 'plan'])
            ->where('status', 'active')
            ->whereDate('ends_at', $targetDate)
            ->get();

        $sent = 0;
        foreach ($expiringSoon as $sub) {
            if ($sub->member?->user) {
                $sub->member->user->notify(new MembershipExpiringNotification($sub));
                $sent++;
            }
        }

        $this->info("Expired {$expiredSubs->count()} subscription(s). Sent {$sent} expiry reminder(s) for {$targetDate}.");

        return self::SUCCESS;
    }
}

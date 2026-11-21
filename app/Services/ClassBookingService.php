<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ClassSchedule;
use App\Models\Member;
use DomainException;
use Illuminate\Support\Facades\DB;

class ClassBookingService
{
    public function book(Member $member, ClassSchedule $schedule): Booking
    {
        if ($schedule->status !== 'scheduled') {
            throw new DomainException('This class session is no longer open for booking.');
        }

        if ($schedule->starts_at->isPast()) {
            throw new DomainException('Cannot book a class that has already started.');
        }

        if (! $member->canAccessBranch($schedule->branch)) {
            throw new DomainException('Your active membership does not grant access to this branch.');
        }

        return DB::transaction(function () use ($member, $schedule) {
            /** @var ClassSchedule $lockedSchedule */
            $lockedSchedule = ClassSchedule::query()->whereKey($schedule->id)->lockForUpdate()->firstOrFail();

            $existing = Booking::query()
                ->where('class_schedule_id', $lockedSchedule->id)
                ->where('member_id', $member->id)
                ->first();

            if ($existing && in_array($existing->status, ['confirmed', 'waitlisted', 'attended'], true)) {
                throw new DomainException('You already have a booking for this session.');
            }

            $subscription = $member->activeSubscription()->lockForUpdate()->first();
            if (! $subscription) {
                throw new DomainException('An active membership subscription is required to book classes.');
            }

            $confirmedCount = Booking::query()
                ->where('class_schedule_id', $lockedSchedule->id)
                ->whereIn('status', ['confirmed', 'attended'])
                ->count();

            $hasSpot = $confirmedCount < $lockedSchedule->capacity;

            if ($hasSpot) {
                if ($subscription->remaining_class_credits !== null && $subscription->remaining_class_credits <= 0) {
                    throw new DomainException('You have no class credits remaining on your current plan.');
                }

                if ($subscription->remaining_class_credits !== null) {
                    $subscription->decrement('remaining_class_credits');
                }

                $status = 'confirmed';
                $waitlistPosition = null;
            } else {
                $maxPos = (int) Booking::query()
                    ->where('class_schedule_id', $lockedSchedule->id)
                    ->where('status', 'waitlisted')
                    ->max('waitlist_position');

                $status = 'waitlisted';
                $waitlistPosition = $maxPos + 1;
            }

            if ($existing) {
                $existing->update([
                    'status' => $status,
                    'waitlist_position' => $waitlistPosition,
                    'booked_at' => now(),
                    'cancelled_at' => null,
                ]);

                return $existing->fresh();
            }

            return Booking::create([
                'class_schedule_id' => $lockedSchedule->id,
                'member_id' => $member->id,
                'status' => $status,
                'waitlist_position' => $waitlistPosition,
                'booked_at' => now(),
            ]);
        });
    }

    public function cancel(Booking $booking): void
    {
        if ($booking->status === 'cancelled') {
            return;
        }

        DB::transaction(function () use ($booking) {
            /** @var Booking $lockedBooking */
            $lockedBooking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $wasConfirmed = $lockedBooking->status === 'confirmed';

            $lockedBooking->update([
                'status' => 'cancelled',
                'waitlist_position' => null,
                'cancelled_at' => now(),
            ]);

            // Refund class credit if previously confirmed
            if ($wasConfirmed) {
                $sub = $lockedBooking->member->activeSubscription()->first();
                if ($sub && $sub->remaining_class_credits !== null) {
                    $sub->increment('remaining_class_credits');
                }

                $this->promoteNextWaitlisted($lockedBooking->class_schedule_id);
            }
        });
    }

    protected function promoteNextWaitlisted(int $scheduleId): void
    {
        $waitlisted = Booking::query()
            ->with('member')
            ->where('class_schedule_id', $scheduleId)
            ->where('status', 'waitlisted')
            ->orderBy('waitlist_position')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($waitlisted as $candidate) {
            $sub = $candidate->member->activeSubscription()->lockForUpdate()->first();
            if (! $sub) {
                continue;
            }

            if ($sub->remaining_class_credits !== null && $sub->remaining_class_credits <= 0) {
                continue;
            }

            if ($sub->remaining_class_credits !== null) {
                $sub->decrement('remaining_class_credits');
            }

            $candidate->update([
                'status' => 'confirmed',
                'waitlist_position' => null,
            ]);

            break;
        }
    }
}

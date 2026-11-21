<?php

namespace App\Livewire\Portal;

use App\Models\Booking;
use App\Models\ClassSchedule;
use App\Services\ClassBookingService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ClassBrowser extends Component
{
    public function book(int $scheduleId): void
    {
        $member = Auth::user()->member;
        if (! $member) {
            session()->flash('error', 'Please complete your membership profile first.');

            return;
        }

        $schedule = ClassSchedule::with('branch')->findOrFail($scheduleId);

        try {
            $booking = app(ClassBookingService::class)->book($member, $schedule);

            if ($booking->status === 'waitlisted') {
                session()->flash('success', "Class is full — you've been added to the waitlist (#{$booking->waitlist_position}).");
            } else {
                session()->flash('success', "Booked! You're confirmed for {$schedule->gymClass->name}.");
            }
        } catch (DomainException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function cancelBooking(int $bookingId): void
    {
        $member = Auth::user()->member;
        if (! $member) {
            return;
        }

        $booking = Booking::where('member_id', $member->id)->findOrFail($bookingId);
        app(ClassBookingService::class)->cancel($booking);
        session()->flash('success', 'Your booking has been cancelled.');
    }

    public function render()
    {
        $member = Auth::user()->member?->load(['activeSubscription.plan', 'homeBranch']);
        $activeSub = $member?->activeSubscription;
        $isAllAccess = $activeSub?->plan?->access_scope === 'all_access';

        $schedules = ClassSchedule::query()
            ->with(['gymClass', 'branch', 'trainer.user'])
            ->withCount([
                'confirmedBookings as confirmed_bookings_count',
                'waitlistedBookings as waitlisted_bookings_count',
            ])
            ->where('status', 'scheduled')
            ->where('starts_at', '>', now())
            ->when($member && ! $isAllAccess && $member->home_branch_id, fn ($q) => $q->where('branch_id', $member->home_branch_id))
            ->orderBy('starts_at')
            ->limit(30)
            ->get();

        $myBookings = $member
            ? Booking::query()
                ->with(['schedule.gymClass', 'schedule.branch', 'schedule.trainer.user'])
                ->where('member_id', $member->id)
                ->whereIn('status', ['confirmed', 'waitlisted'])
                ->whereHas('schedule', fn ($q) => $q->where('starts_at', '>', now()->subHours(2)))
                ->get()
            : collect();

        $myBookingMap = $myBookings->keyBy('class_schedule_id');

        return view('livewire.portal.class-browser', [
            'member' => $member,
            'activeSubscription' => $activeSub,
            'schedules' => $schedules,
            'myBookings' => $myBookings,
            'myBookingMap' => $myBookingMap,
        ]);
    }
}

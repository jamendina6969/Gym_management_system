<div class="space-y-6">
    {{-- Credits Banner --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-lg font-bold text-slate-900">Group Classes &amp; Bookings</h1>
            <p class="text-xs text-slate-500 mt-0.5">Reserve your spot or join the waitlist — we'll auto-promote you if a spot opens up.</p>
        </div>
        @if ($activeSubscription)
            <div class="px-3.5 py-2 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-900 font-semibold">
                Class Credits: {{ $activeSubscription->remaining_class_credits === null ? 'Unlimited' : $activeSubscription->remaining_class_credits }}
            </div>
        @endif
    </div>

    {{-- My Upcoming Bookings --}}
    @if ($myBookings->isNotEmpty())
        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900 mb-3">My Upcoming Bookings</h2>
            <div class="space-y-2.5">
                @foreach ($myBookings as $booking)
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-slate-900 text-sm">{{ $booking->schedule->gymClass->name }}</span>
                                <span class="px-2 py-0.5 rounded-full font-semibold {{ $booking->status === 'confirmed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $booking->status === 'confirmed' ? 'Confirmed' : 'Waitlist #' . $booking->waitlist_position }}
                                </span>
                            </div>
                            <div class="text-slate-500 mt-0.5">
                                {{ $booking->schedule->starts_at->format('D, M d · h:i A') }} · {{ $booking->schedule->branch->name }}
                            </div>
                        </div>
                        <button wire:click="cancelBooking({{ $booking->id }})" wire:confirm="Cancel this class booking?"
                                class="px-3 py-1.5 rounded-lg border border-rose-200 text-rose-700 hover:bg-rose-50 font-medium self-start sm:self-center">
                            Cancel Booking
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Available Schedule --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @forelse ($schedules as $session)
            @php
                $existing = $myBookingMap->get($session->id);
            @endphp
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="text-xs font-semibold text-emerald-600">{{ $session->starts_at->format('D, M d · h:i A') }}</div>
                            <h3 class="font-bold text-slate-900 text-base mt-0.5">{{ $session->gymClass->name }}</h3>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $session->isFull() ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                            {{ $session->isFull() ? 'Full (Waitlist)' : $session->spotsLeft() . ' spots left' }}
                        </span>
                    </div>
                    <div class="text-xs text-slate-500 mt-2 space-y-0.5">
                        <div>Coach: <strong class="text-slate-700">{{ $session->trainer?->user?->name ?? 'TBA' }}</strong> · {{ $session->room ?: 'Main Studio' }}</div>
                        <div>Branch: {{ $session->branch->name }}</div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100">
                    @if ($existing)
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold {{ $existing->status === 'confirmed' ? 'text-emerald-700' : 'text-amber-700' }}">
                                {{ $existing->status === 'confirmed' ? '✓ Booked' : 'Waitlisted #' . $existing->waitlist_position }}
                            </span>
                            <button wire:click="cancelBooking({{ $existing->id }})" class="text-rose-600 hover:underline font-medium">Cancel</button>
                        </div>
                    @else
                        <button wire:click="book({{ $session->id }})"
                                class="w-full py-2 rounded-lg text-xs font-semibold transition {{ $session->isFull() ? 'bg-amber-500 text-white hover:bg-amber-600' : 'bg-slate-900 text-white hover:bg-emerald-600' }}">
                            {{ $session->isFull() ? 'Join Waitlist' : 'Book Class' }}
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-500 text-sm">
                No upcoming classes scheduled yet.
            </div>
        @endforelse
    </div>
</div>

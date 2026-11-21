<div class="space-y-4">
    {{-- Tabs & Action Buttons --}}
    <div class="bg-white rounded-xl border border-slate-200 p-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-sm">
        <div class="flex gap-1.5">
            @foreach (['schedules' => 'Upcoming Schedule', 'classes' => 'Class Types', 'trainers' => 'Trainers'] as $key => $label)
                <button wire:click="$set('tab', '{{ $key }}')"
                        class="px-3.5 py-2 rounded-lg text-xs font-semibold transition {{ $tab === $key ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="flex flex-wrap gap-2">
            @if ($canManageClasses)
                <button wire:click="openScheduleModal" class="px-3.5 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">+ Schedule Session</button>
                <button wire:click="openClassModal" class="px-3.5 py-2 rounded-lg border border-slate-300 text-slate-700 text-xs font-semibold hover:bg-slate-50">+ New Class Type</button>
            @endif
            @if ($canManageTrainers)
                <button wire:click="openTrainerModal" class="px-3.5 py-2 rounded-lg border border-slate-300 text-slate-700 text-xs font-semibold hover:bg-slate-50">+ Add Trainer</button>
            @endif
        </div>
    </div>

    {{-- TAB 1: SCHEDULES --}}
    @if ($tab === 'schedules')
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3 text-left">Date &amp; Time</th>
                            <th class="px-5 py-3 text-left">Class</th>
                            <th class="px-5 py-3 text-left">Trainer / Room</th>
                            <th class="px-5 py-3 text-center">Occupancy</th>
                            <th class="px-5 py-3 text-right">Roster</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @forelse ($schedules as $session)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-3.5 font-medium text-slate-900">
                                    {{ $session->starts_at->format('D, M d, Y') }}
                                    <div class="text-slate-500 font-normal">{{ $session->starts_at->format('h:i A') }} – {{ $session->ends_at->format('h:i A') }}</div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="font-semibold text-slate-900">{{ $session->gymClass->name }}</div>
                                    <div class="text-slate-500">{{ $session->branch->name }}</div>
                                </td>
                                <td class="px-5 py-3.5 text-slate-600">
                                    <div>{{ $session->trainer?->user?->name ?? 'Unassigned' }}</div>
                                    <div class="text-slate-400">{{ $session->room ?: 'Main Studio' }}</div>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full font-semibold {{ $session->isFull() ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                        {{ $session->confirmed_bookings_count }} / {{ $session->capacity }}
                                    </span>
                                    @if ($session->waitlisted_bookings_count > 0)
                                        <div class="text-[11px] text-amber-700 mt-0.5">+{{ $session->waitlisted_bookings_count }} waitlisted</div>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <button wire:click="openRoster({{ $session->id }})" class="px-3 py-1.5 rounded-md bg-slate-100 hover:bg-slate-200 text-slate-800 font-medium">
                                        View Roster
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-slate-500">No upcoming sessions scheduled.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- TAB 2: CLASS TYPES --}}
    @if ($tab === 'classes')
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($classes as $gymClass)
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="font-semibold text-slate-900">{{ $gymClass->name }}</h3>
                            <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-medium">{{ $gymClass->category ?: 'General' }}</span>
                        </div>
                        @if ($canManageClasses)
                            <button wire:click="openClassModal({{ $gymClass->id }})" class="text-xs text-emerald-700 hover:underline font-medium">Edit</button>
                        @endif
                    </div>
                    @if ($gymClass->description)
                        <p class="text-xs text-slate-500 mt-2">{{ $gymClass->description }}</p>
                    @endif
                    <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-600 flex justify-between">
                        <span>{{ $gymClass->duration_minutes }} min · Cap {{ $gymClass->default_capacity }}</span>
                        <span>{{ $gymClass->trainer?->user?->name ?? 'Any Trainer' }}</span>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-500 text-sm">No class types created yet.</div>
            @endforelse
        </div>
    @endif

    {{-- TAB 3: TRAINERS --}}
    @if ($tab === 'trainers')
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($trainers as $trainer)
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-full bg-slate-900 text-emerald-400 font-bold flex items-center justify-center text-sm">
                            {{ strtoupper(substr($trainer->user->name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="font-semibold text-slate-900">{{ $trainer->user->name }}</div>
                            <div class="text-xs text-emerald-700 font-medium">{{ $trainer->specialization ?: 'Personal Trainer' }}</div>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-slate-500 space-y-1">
                        <div>Branch: {{ $trainer->branch?->name ?? '—' }}</div>
                        <div>Email: {{ $trainer->user->email }}</div>
                        @if ($trainer->hourly_rate)
                            <div>Rate: {{ $currencySymbol }}{{ number_format((float) $trainer->hourly_rate, 2) }}/hr</div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-500 text-sm">No trainers registered yet.</div>
            @endforelse
        </div>
    @endif

    {{-- Trainer Modal --}}
    @if ($showTrainerModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h3 class="font-semibold text-slate-900">Add Trainer</h3>
                    <button wire:click="$set('showTrainerModal', false)" class="text-slate-400">✕</button>
                </div>
                <form wire:submit="saveTrainer" class="p-6 space-y-3.5 text-sm">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Full Name *</label>
                        <input type="text" wire:model="trainer_name" class="w-full rounded-lg border-slate-300 text-sm">
                        @error('trainer_name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Email *</label>
                        <input type="email" wire:model="trainer_email" class="w-full rounded-lg border-slate-300 text-sm">
                        @error('trainer_email') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Branch *</label>
                            <select wire:model="trainer_branch_id" class="w-full rounded-lg border-slate-300 text-sm">
                                @foreach ($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Hourly Rate ({{ $currencySymbol }})</label>
                            <input type="number" step="0.01" wire:model="trainer_hourly_rate" class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Specialization</label>
                        <input type="text" wire:model="trainer_specialization" placeholder="e.g. HIIT, Strength, Yoga" class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div class="pt-3 border-t flex justify-end gap-2">
                        <button type="button" wire:click="$set('showTrainerModal', false)" class="px-4 py-2 rounded-lg border text-xs">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold">Save Trainer</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Class Type Modal --}}
    @if ($showClassModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h3 class="font-semibold text-slate-900">{{ $editingClassId ? 'Edit Class Type' : 'New Class Type' }}</h3>
                    <button wire:click="$set('showClassModal', false)" class="text-slate-400">✕</button>
                </div>
                <form wire:submit="saveClass" class="p-6 space-y-3.5 text-sm">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Class Name *</label>
                            <input type="text" wire:model="class_name" placeholder="HIIT Burn" class="w-full rounded-lg border-slate-300 text-sm">
                            @error('class_name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Category</label>
                            <input type="text" wire:model="class_category" placeholder="HIIT / Yoga / Spin" class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Branch *</label>
                            <select wire:model="class_branch_id" class="w-full rounded-lg border-slate-300 text-sm">
                                @foreach ($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Default Trainer</label>
                            <select wire:model="class_trainer_id" class="w-full rounded-lg border-slate-300 text-sm">
                                <option value="">None</option>
                                @foreach ($trainers as $t)
                                    <option value="{{ $t->id }}">{{ $t->user->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Capacity *</label>
                            <input type="number" wire:model="class_capacity" min="1" class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Duration (min) *</label>
                            <input type="number" wire:model="class_duration" min="15" class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Description</label>
                        <textarea wire:model="class_description" rows="2" class="w-full rounded-lg border-slate-300 text-sm"></textarea>
                    </div>
                    <div class="pt-3 border-t flex justify-end gap-2">
                        <button type="button" wire:click="$set('showClassModal', false)" class="px-4 py-2 rounded-lg border text-xs">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold">Save Class</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Schedule Session Modal --}}
    @if ($showScheduleModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h3 class="font-semibold text-slate-900">Schedule Class Session</h3>
                    <button wire:click="$set('showScheduleModal', false)" class="text-slate-400">✕</button>
                </div>
                <form wire:submit="saveSchedule" class="p-6 space-y-3.5 text-sm">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Class *</label>
                        <select wire:model.live="schedule_class_id" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">Choose class...</option>
                            @foreach ($classes as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->branch->code }})</option>
                            @endforeach
                        </select>
                        @error('schedule_class_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Date *</label>
                            <input type="date" wire:model="schedule_date" class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Start Time *</label>
                            <input type="time" wire:model="schedule_time" class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Room</label>
                            <input type="text" wire:model="schedule_room" class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Capacity *</label>
                            <input type="number" wire:model="schedule_capacity" min="1" class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Trainer</label>
                        <select wire:model="schedule_trainer_id" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">Use class default</option>
                            @foreach ($trainers as $t)
                                <option value="{{ $t->id }}">{{ $t->user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pt-3 border-t flex justify-end gap-2">
                        <button type="button" wire:click="$set('showScheduleModal', false)" class="px-4 py-2 rounded-lg border text-xs">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold">Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Roster Modal --}}
    @if ($showRosterModal && $rosterSchedule)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ $rosterSchedule->gymClass->name }} Roster</h3>
                        <p class="text-xs text-slate-500">{{ $rosterSchedule->starts_at->format('D, M d · h:i A') }}</p>
                    </div>
                    <button wire:click="$set('showRosterModal', false)" class="text-slate-400">✕</button>
                </div>
                <div class="p-6 max-h-96 overflow-y-auto divide-y divide-slate-100 text-xs">
                    @forelse ($rosterSchedule->bookings as $booking)
                        <div class="py-2.5 flex items-center justify-between gap-2">
                            <div>
                                <div class="font-semibold text-slate-900">{{ $booking->member->user->name }}</div>
                                <div class="text-slate-500">{{ $booking->member->member_code }}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-full font-medium {{ $booking->status === 'confirmed' || $booking->status === 'attended' ? 'bg-emerald-100 text-emerald-800' : ($booking->status === 'waitlisted' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-500') }}">
                                    {{ ucfirst($booking->status) }}{{ $booking->waitlist_position ? ' #' . $booking->waitlist_position : '' }}
                                </span>
                                @if ($booking->status === 'confirmed')
                                    <button wire:click="markBookingStatus({{ $booking->id }}, 'attended')" class="text-emerald-700 hover:underline font-medium">Attended</button>
                                    <button wire:click="markBookingStatus({{ $booking->id }}, 'cancelled')" class="text-rose-600 hover:underline font-medium">Cancel</button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-slate-500 py-6">No members booked yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>

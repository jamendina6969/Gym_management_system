<div class="space-y-4">
    {{-- Tabs & Actions --}}
    <div class="bg-white rounded-xl border border-slate-200 p-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-sm">
        <div class="flex gap-1.5">
            @foreach (['workouts' => 'Workout Plans', 'diets' => 'Diet Plans', 'assignments' => 'Member Assignments'] as $key => $label)
                <button wire:click="$set('tab', '{{ $key }}')"
                        class="px-3.5 py-2 rounded-lg text-xs font-semibold transition {{ $tab === $key ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if ($canManage)
            <div class="flex flex-wrap gap-2">
                <button wire:click="openWorkoutModal" class="px-3.5 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">+ Workout Plan</button>
                <button wire:click="openDietModal" class="px-3.5 py-2 rounded-lg border border-slate-300 text-slate-700 text-xs font-semibold hover:bg-slate-50">+ Diet Plan</button>
                <button wire:click="openAssignModal" class="px-3.5 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800">Assign to Member</button>
            </div>
        @endif
    </div>

    {{-- WORKOUT PLANS --}}
    @if ($tab === 'workouts')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse ($workoutPlans as $plan)
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h3 class="font-bold text-slate-900">{{ $plan->title }}</h3>
                            <div class="text-xs text-slate-500 mt-0.5">{{ $plan->goal ?: 'General Fitness' }} · {{ ucfirst($plan->difficulty) }} · {{ $plan->duration_weeks }} weeks</div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-semibold">{{ $plan->items->count() }} exercises</span>
                    </div>
                    <div class="mt-3 divide-y divide-slate-100 text-xs">
                        @foreach ($plan->items->take(5) as $item)
                            <div class="py-1.5 flex justify-between">
                                <span><strong class="text-slate-700">{{ $item->day_label }}:</strong> {{ $item->exercise_name }}</span>
                                <span class="font-mono text-slate-500">{{ $item->sets }}×{{ $item->reps }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-500 text-sm">No workout plans created yet.</div>
            @endforelse
        </div>
    @endif

    {{-- DIET PLANS --}}
    @if ($tab === 'diets')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse ($dietPlans as $plan)
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h3 class="font-bold text-slate-900">{{ $plan->title }}</h3>
                            <div class="text-xs text-slate-500 mt-0.5">{{ $plan->goal ?: 'Nutrition' }}</div>
                        </div>
                        @if ($plan->daily_calories)
                            <span class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-800 text-xs font-semibold">{{ $plan->daily_calories }} kcal/day</span>
                        @endif
                    </div>
                    <div class="mt-2 flex gap-3 text-[11px] text-slate-600">
                        @if ($plan->protein_grams) <span>Protein: <strong>{{ $plan->protein_grams }}g</strong></span> @endif
                        @if ($plan->carbs_grams) <span>Carbs: <strong>{{ $plan->carbs_grams }}g</strong></span> @endif
                        @if ($plan->fat_grams) <span>Fat: <strong>{{ $plan->fat_grams }}g</strong></span> @endif
                    </div>
                    <div class="mt-3 divide-y divide-slate-100 text-xs">
                        @foreach ($plan->items as $item)
                            <div class="py-1.5">
                                <span class="font-semibold text-slate-800">{{ $item->meal_time }}:</span>
                                <span class="text-slate-600">{{ $item->food_items }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-500 text-sm">No diet plans created yet.</div>
            @endforelse
        </div>
    @endif

    {{-- ASSIGNMENTS --}}
    @if ($tab === 'assignments')
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase">
                    <tr>
                        <th class="px-5 py-3 text-left">Member</th>
                        <th class="px-5 py-3 text-left">Workout Plan</th>
                        <th class="px-5 py-3 text-left">Diet Plan</th>
                        <th class="px-5 py-3 text-left">Period</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($assignments as $a)
                        <tr>
                            <td class="px-5 py-3.5 font-semibold text-slate-900">{{ $a->member->user->name }}</td>
                            <td class="px-5 py-3.5 text-slate-700">{{ $a->workoutPlan?->title ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-slate-700">{{ $a->dietPlan?->title ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-slate-500">{{ $a->starts_at->format('M d, Y') }} {{ $a->ends_at ? '→ ' . $a->ends_at->format('M d, Y') : '' }}</td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="px-2 py-0.5 rounded-full font-medium {{ $a->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($a->status) }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-right space-x-2">
                                @if ($a->status === 'active' && $canManage)
                                    <button wire:click="markAssignmentStatus({{ $a->id }}, 'completed')" class="text-emerald-700 hover:underline font-medium">Complete</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">No plans assigned yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    {{-- Workout Modal --}}
    @if ($showWorkoutModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden my-8">
                <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h3 class="font-semibold text-slate-900">Create Workout Plan</h3>
                    <button wire:click="$set('showWorkoutModal', false)" class="text-slate-400">✕</button>
                </div>
                <form wire:submit="saveWorkout" class="p-6 space-y-4 text-sm max-h-[80vh] overflow-y-auto">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Plan Title *</label>
                            <input type="text" wire:model="w_title" placeholder="4-Week Push/Pull/Legs" class="w-full rounded-lg border-slate-300 text-sm">
                            @error('w_title') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Goal</label>
                            <input type="text" wire:model="w_goal" class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Difficulty</label>
                            <select wire:model="w_difficulty" class="w-full rounded-lg border-slate-300 text-sm">
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Duration (Weeks)</label>
                            <input type="number" wire:model="w_duration_weeks" min="1" class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <label class="text-xs font-semibold text-slate-700">Exercises *</label>
                            <button type="button" wire:click="addWorkoutRow" class="text-xs text-emerald-700 font-semibold hover:underline">+ Add Exercise</button>
                        </div>
                        @foreach ($w_items as $idx => $row)
                            <div class="grid grid-cols-12 gap-2 items-center bg-slate-50 p-2 rounded-lg">
                                <input type="text" wire:model="w_items.{{ $idx }}.day_label" placeholder="Day" class="col-span-3 rounded border-slate-300 text-xs">
                                <input type="text" wire:model="w_items.{{ $idx }}.exercise_name" placeholder="Exercise name" class="col-span-4 rounded border-slate-300 text-xs">
                                <input type="number" wire:model="w_items.{{ $idx }}.sets" placeholder="Sets" class="col-span-2 rounded border-slate-300 text-xs">
                                <input type="text" wire:model="w_items.{{ $idx }}.reps" placeholder="Reps" class="col-span-2 rounded border-slate-300 text-xs">
                                <button type="button" wire:click="removeWorkoutRow({{ $idx }})" class="col-span-1 text-rose-500 text-xs font-bold">✕</button>
                            </div>
                        @endforeach
                    </div>

                    <div class="pt-3 border-t flex justify-end gap-2">
                        <button type="button" wire:click="$set('showWorkoutModal', false)" class="px-4 py-2 rounded-lg border text-xs">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold">Save Workout Plan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Diet Modal --}}
    @if ($showDietModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden my-8">
                <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h3 class="font-semibold text-slate-900">Create Diet Plan</h3>
                    <button wire:click="$set('showDietModal', false)" class="text-slate-400">✕</button>
                </div>
                <form wire:submit="saveDiet" class="p-6 space-y-4 text-sm max-h-[80vh] overflow-y-auto">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Plan Title *</label>
                            <input type="text" wire:model="d_title" placeholder="High-Protein Lean Cut" class="w-full rounded-lg border-slate-300 text-sm">
                            @error('d_title') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Goal</label>
                            <input type="text" wire:model="d_goal" class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-4 gap-2">
                        <div><label class="block text-[11px] text-slate-600">Daily kcal</label><input type="number" wire:model="d_calories" class="w-full rounded-lg border-slate-300 text-xs"></div>
                        <div><label class="block text-[11px] text-slate-600">Protein (g)</label><input type="number" wire:model="d_protein" class="w-full rounded-lg border-slate-300 text-xs"></div>
                        <div><label class="block text-[11px] text-slate-600">Carbs (g)</label><input type="number" wire:model="d_carbs" class="w-full rounded-lg border-slate-300 text-xs"></div>
                        <div><label class="block text-[11px] text-slate-600">Fat (g)</label><input type="number" wire:model="d_fat" class="w-full rounded-lg border-slate-300 text-xs"></div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <label class="text-xs font-semibold text-slate-700">Meals *</label>
                            <button type="button" wire:click="addDietRow" class="text-xs text-emerald-700 font-semibold hover:underline">+ Add Meal</button>
                        </div>
                        @foreach ($d_items as $idx => $row)
                            <div class="grid grid-cols-12 gap-2 items-center bg-slate-50 p-2 rounded-lg">
                                <input type="text" wire:model="d_items.{{ $idx }}.meal_time" placeholder="Meal" class="col-span-3 rounded border-slate-300 text-xs">
                                <input type="text" wire:model="d_items.{{ $idx }}.food_items" placeholder="Food items" class="col-span-5 rounded border-slate-300 text-xs">
                                <input type="number" wire:model="d_items.{{ $idx }}.calories" placeholder="kcal" class="col-span-2 rounded border-slate-300 text-xs">
                                <input type="number" wire:model="d_items.{{ $idx }}.protein_grams" placeholder="Prot g" class="col-span-1 rounded border-slate-300 text-xs">
                                <button type="button" wire:click="removeDietRow({{ $idx }})" class="col-span-1 text-rose-500 text-xs font-bold">✕</button>
                            </div>
                        @endforeach
                    </div>

                    <div class="pt-3 border-t flex justify-end gap-2">
                        <button type="button" wire:click="$set('showDietModal', false)" class="px-4 py-2 rounded-lg border text-xs">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold">Save Diet Plan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Assign Modal --}}
    @if ($showAssignModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h3 class="font-semibold text-slate-900">Assign Plans to Member</h3>
                    <button wire:click="$set('showAssignModal', false)" class="text-slate-400">✕</button>
                </div>
                <form wire:submit="saveAssignment" class="p-6 space-y-3.5 text-sm">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Member *</label>
                        <select wire:model="assign_member_id" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">Choose member...</option>
                            @foreach ($members as $m)
                                <option value="{{ $m->id }}">{{ $m->user->name }} ({{ $m->member_code }})</option>
                            @endforeach
                        </select>
                        @error('assign_member_id') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Workout Plan</label>
                        <select wire:model="assign_workout_id" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">None</option>
                            @foreach ($workoutPlans as $w)
                                <option value="{{ $w->id }}">{{ $w->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Diet Plan</label>
                        <select wire:model="assign_diet_id" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">None</option>
                            @foreach ($dietPlans as $d)
                                <option value="{{ $d->id }}">{{ $d->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Start Date *</label>
                            <input type="date" wire:model="assign_starts_at" class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">End Date</label>
                            <input type="date" wire:model="assign_ends_at" class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                    </div>
                    <div class="pt-3 border-t flex justify-end gap-2">
                        <button type="button" wire:click="$set('showAssignModal', false)" class="px-4 py-2 rounded-lg border text-xs">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold">Assign</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

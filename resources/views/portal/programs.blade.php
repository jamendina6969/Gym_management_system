@extends('layouts.member')

@section('title', 'My Workout & Diet Plans')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-xl font-bold text-slate-900">My Workout &amp; Diet Plans</h1>
        <p class="text-xs text-slate-500 mt-0.5">Personalized training and nutrition plans assigned by your coach.</p>
    </div>

    @if (!$activeAssignment)
        <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center text-slate-500 text-sm shadow-sm">
            No active workout or diet plan assigned yet. Ask a trainer at your branch to create a program for you!
        </div>
    @else
        {{-- Workout Plan --}}
        @if ($activeAssignment->workoutPlan)
            @php $wp = $activeAssignment->workoutPlan; @endphp
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="bg-slate-900 text-white px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <div class="text-[11px] uppercase tracking-wider text-emerald-400 font-semibold">Assigned Workout Plan</div>
                        <h2 class="text-lg font-bold">{{ $wp->title }}</h2>
                    </div>
                    <span class="text-xs text-slate-300">{{ $wp->goal }} · {{ ucfirst($wp->difficulty) }} · {{ $wp->duration_weeks }} weeks</span>
                </div>

                <div class="p-6 space-y-5">
                    @foreach ($wp->items->groupBy('day_label') as $day => $exercises)
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-700 mb-2">{{ $day }}</h3>
                            <div class="divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden text-sm">
                                @foreach ($exercises as $ex)
                                    <div class="px-4 py-3 flex items-center justify-between bg-white">
                                        <div>
                                            <div class="font-semibold text-slate-900">{{ $ex->exercise_name }}</div>
                                            @if ($ex->notes)
                                                <div class="text-xs text-slate-500">{{ $ex->notes }}</div>
                                            @endif
                                        </div>
                                        <div class="text-right text-xs font-mono">
                                            <div class="font-bold text-slate-900">{{ $ex->sets }} sets × {{ $ex->reps }}</div>
                                            @if ($ex->rest_seconds)
                                                <div class="text-slate-400">Rest {{ $ex->rest_seconds }}s</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Diet Plan --}}
        @if ($activeAssignment->dietPlan)
            @php $dp = $activeAssignment->dietPlan; @endphp
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <div class="text-[11px] uppercase tracking-wider text-emerald-600 font-semibold">Assigned Diet Plan</div>
                        <h2 class="text-lg font-bold text-slate-900">{{ $dp->title }}</h2>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs">
                        @if ($dp->daily_calories) <span class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-800 font-semibold">{{ $dp->daily_calories }} kcal</span> @endif
                        @if ($dp->protein_grams) <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 font-semibold">P: {{ $dp->protein_grams }}g</span> @endif
                        @if ($dp->carbs_grams) <span class="px-2.5 py-1 rounded-lg bg-sky-50 text-sky-800 font-semibold">C: {{ $dp->carbs_grams }}g</span> @endif
                        @if ($dp->fat_grams) <span class="px-2.5 py-1 rounded-lg bg-rose-50 text-rose-800 font-semibold">F: {{ $dp->fat_grams }}g</span> @endif
                    </div>
                </div>

                <div class="divide-y divide-slate-100">
                    @foreach ($dp->items as $meal)
                        <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-sm">
                            <div>
                                <div class="font-semibold text-slate-900">{{ $meal->meal_time }}</div>
                                <div class="text-xs text-slate-600 mt-0.5">{{ $meal->food_items }}</div>
                            </div>
                            <div class="text-xs text-slate-500 font-mono shrink-0">
                                @if ($meal->calories) {{ $meal->calories }} kcal @endif
                                @if ($meal->protein_grams) · {{ $meal->protein_grams }}g protein @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>
@endsection

<?php

namespace App\Livewire\Admin\Programs;

use App\Models\DietPlan;
use App\Models\Member;
use App\Models\PlanAssignment;
use App\Models\WorkoutPlan;
use App\Services\BranchContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ProgramManager extends Component
{
    public string $tab = 'workouts'; // workouts, diets, assignments

    // Workout Plan Modal
    public bool $showWorkoutModal = false;
    public string $w_title = '';
    public string $w_goal = 'Muscle Gain';
    public string $w_difficulty = 'intermediate';
    public int $w_duration_weeks = 4;
    public string $w_notes = '';
    /** @var array<int, array{day_label: string, exercise_name: string, sets: int, reps: string, rest_seconds: int}> */
    public array $w_items = [];

    // Diet Plan Modal
    public bool $showDietModal = false;
    public string $d_title = '';
    public string $d_goal = 'Fat Loss';
    public string $d_calories = '2000';
    public string $d_protein = '150';
    public string $d_carbs = '200';
    public string $d_fat = '65';
    public string $d_notes = '';
    /** @var array<int, array{meal_time: string, food_items: string, calories: string, protein_grams: string}> */
    public array $d_items = [];

    // Assign Modal
    public bool $showAssignModal = false;
    public ?int $assign_member_id = null;
    public ?int $assign_workout_id = null;
    public ?int $assign_diet_id = null;
    public string $assign_starts_at = '';
    public string $assign_ends_at = '';
    public string $assign_notes = '';

    public function mount(): void
    {
        $this->assign_starts_at = now()->toDateString();
    }

    public function openWorkoutModal(): void
    {
        abort_unless(Auth::user()->can('workouts.manage'), 403);

        $this->reset(['w_title', 'w_notes']);
        $this->w_goal = 'Muscle Gain';
        $this->w_difficulty = 'intermediate';
        $this->w_duration_weeks = 4;
        $this->w_items = [
            ['day_label' => 'Day 1 - Push', 'exercise_name' => 'Barbell Bench Press', 'sets' => 4, 'reps' => '8-10', 'rest_seconds' => 90],
        ];
        $this->resetValidation();
        $this->showWorkoutModal = true;
    }

    public function addWorkoutRow(): void
    {
        $this->w_items[] = ['day_label' => 'Day 1', 'exercise_name' => '', 'sets' => 3, 'reps' => '10-12', 'rest_seconds' => 60];
    }

    public function removeWorkoutRow(int $index): void
    {
        unset($this->w_items[$index]);
        $this->w_items = array_values($this->w_items);
    }

    public function saveWorkout(): void
    {
        abort_unless(Auth::user()->can('workouts.manage'), 403);

        $validated = $this->validate([
            'w_title' => ['required', 'string', 'max:150'],
            'w_goal' => ['nullable', 'string', 'max:80'],
            'w_difficulty' => ['required', 'in:beginner,intermediate,advanced'],
            'w_duration_weeks' => ['required', 'integer', 'min:1', 'max:52'],
            'w_notes' => ['nullable', 'string', 'max:1000'],
            'w_items' => ['required', 'array', 'min:1'],
            'w_items.*.day_label' => ['required', 'string', 'max:50'],
            'w_items.*.exercise_name' => ['required', 'string', 'max:120'],
            'w_items.*.sets' => ['required', 'integer', 'min:1', 'max:20'],
            'w_items.*.reps' => ['required', 'string', 'max:30'],
            'w_items.*.rest_seconds' => ['nullable', 'integer', 'min:0', 'max:600'],
        ]);

        $branchId = app(BranchContext::class)->currentBranchId() ?? Auth::user()->accessibleBranches()->first()?->id;

        DB::transaction(function () use ($validated, $branchId) {
            $plan = WorkoutPlan::create([
                'branch_id' => $branchId,
                'trainer_id' => Auth::user()->trainer?->id,
                'created_by' => Auth::id(),
                'title' => $validated['w_title'],
                'goal' => $validated['w_goal'] ?: null,
                'difficulty' => $validated['w_difficulty'],
                'duration_weeks' => $validated['w_duration_weeks'],
                'notes' => $validated['w_notes'] ?: null,
            ]);

            foreach ($validated['w_items'] as $idx => $item) {
                $plan->items()->create([
                    'day_label' => $item['day_label'],
                    'exercise_name' => $item['exercise_name'],
                    'sets' => (int) $item['sets'],
                    'reps' => $item['reps'],
                    'rest_seconds' => $item['rest_seconds'] !== '' ? (int) $item['rest_seconds'] : null,
                    'sort_order' => $idx,
                ]);
            }
        });

        $this->showWorkoutModal = false;
        session()->flash('success', 'Workout plan created.');
    }

    public function openDietModal(): void
    {
        abort_unless(Auth::user()->can('diets.manage'), 403);

        $this->reset(['d_title', 'd_notes']);
        $this->d_goal = 'Lean Cut';
        $this->d_calories = '2000';
        $this->d_protein = '150';
        $this->d_carbs = '200';
        $this->d_fat = '65';
        $this->d_items = [
            ['meal_time' => 'Breakfast', 'food_items' => '3 eggs, oats with banana', 'calories' => '520', 'protein_grams' => '32'],
        ];
        $this->resetValidation();
        $this->showDietModal = true;
    }

    public function addDietRow(): void
    {
        $this->d_items[] = ['meal_time' => 'Meal', 'food_items' => '', 'calories' => '', 'protein_grams' => ''];
    }

    public function removeDietRow(int $index): void
    {
        unset($this->d_items[$index]);
        $this->d_items = array_values($this->d_items);
    }

    public function saveDiet(): void
    {
        abort_unless(Auth::user()->can('diets.manage'), 403);

        $validated = $this->validate([
            'd_title' => ['required', 'string', 'max:150'],
            'd_goal' => ['nullable', 'string', 'max:80'],
            'd_calories' => ['nullable', 'integer', 'min:500', 'max:10000'],
            'd_protein' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'd_carbs' => ['nullable', 'integer', 'min:0', 'max:1500'],
            'd_fat' => ['nullable', 'integer', 'min:0', 'max:500'],
            'd_notes' => ['nullable', 'string', 'max:1000'],
            'd_items' => ['required', 'array', 'min:1'],
            'd_items.*.meal_time' => ['required', 'string', 'max:50'],
            'd_items.*.food_items' => ['required', 'string', 'max:1000'],
            'd_items.*.calories' => ['nullable', 'integer', 'min:0'],
            'd_items.*.protein_grams' => ['nullable', 'integer', 'min:0'],
        ]);

        $branchId = app(BranchContext::class)->currentBranchId() ?? Auth::user()->accessibleBranches()->first()?->id;

        DB::transaction(function () use ($validated, $branchId) {
            $plan = DietPlan::create([
                'branch_id' => $branchId,
                'trainer_id' => Auth::user()->trainer?->id,
                'created_by' => Auth::id(),
                'title' => $validated['d_title'],
                'goal' => $validated['d_goal'] ?: null,
                'daily_calories' => $validated['d_calories'] ?: null,
                'protein_grams' => $validated['d_protein'] ?: null,
                'carbs_grams' => $validated['d_carbs'] ?: null,
                'fat_grams' => $validated['d_fat'] ?: null,
                'notes' => $validated['d_notes'] ?: null,
            ]);

            foreach ($validated['d_items'] as $idx => $item) {
                $plan->items()->create([
                    'meal_time' => $item['meal_time'],
                    'food_items' => $item['food_items'],
                    'calories' => $item['calories'] !== '' ? (int) $item['calories'] : null,
                    'protein_grams' => $item['protein_grams'] !== '' ? (int) $item['protein_grams'] : null,
                    'sort_order' => $idx,
                ]);
            }
        });

        $this->showDietModal = false;
        session()->flash('success', 'Diet plan created.');
    }

    public function openAssignModal(): void
    {
        abort_unless(Auth::user()->can('workouts.manage') || Auth::user()->can('diets.manage'), 403);

        $this->reset(['assign_member_id', 'assign_workout_id', 'assign_diet_id', 'assign_ends_at', 'assign_notes']);
        $this->assign_starts_at = now()->toDateString();
        $this->resetValidation();
        $this->showAssignModal = true;
    }

    public function saveAssignment(): void
    {
        abort_unless(Auth::user()->can('workouts.manage') || Auth::user()->can('diets.manage'), 403);

        $validated = $this->validate([
            'assign_member_id' => ['required', 'exists:members,id'],
            'assign_workout_id' => ['nullable', 'exists:workout_plans,id', 'required_without:assign_diet_id'],
            'assign_diet_id' => ['nullable', 'exists:diet_plans,id', 'required_without:assign_workout_id'],
            'assign_starts_at' => ['required', 'date'],
            'assign_ends_at' => ['nullable', 'date', 'after_or_equal:assign_starts_at'],
            'assign_notes' => ['nullable', 'string', 'max:500'],
        ]);

        PlanAssignment::create([
            'member_id' => $validated['assign_member_id'],
            'workout_plan_id' => $validated['assign_workout_id'] ?: null,
            'diet_plan_id' => $validated['assign_diet_id'] ?: null,
            'assigned_by' => Auth::id(),
            'starts_at' => $validated['assign_starts_at'],
            'ends_at' => $validated['assign_ends_at'] ?: null,
            'status' => 'active',
            'notes' => $validated['assign_notes'] ?: null,
        ]);

        $this->showAssignModal = false;
        session()->flash('success', 'Plan assigned to member.');
    }

    public function markAssignmentStatus(int $assignmentId, string $status): void
    {
        abort_unless(Auth::user()->can('workouts.manage') || Auth::user()->can('diets.manage'), 403);

        if (in_array($status, ['active', 'completed', 'archived'], true)) {
            PlanAssignment::findOrFail($assignmentId)->update(['status' => $status]);
        }
    }

    public function render()
    {
        $scopedBranchIds = app(BranchContext::class)->scopedBranchIds();

        $workoutPlans = WorkoutPlan::with(['items', 'creator'])
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->where(fn ($i) => $i->whereNull('branch_id')->orWhereIn('branch_id', $scopedBranchIds)))
            ->latest()
            ->get();

        $dietPlans = DietPlan::with(['items', 'creator'])
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->where(fn ($i) => $i->whereNull('branch_id')->orWhereIn('branch_id', $scopedBranchIds)))
            ->latest()
            ->get();

        $assignments = PlanAssignment::with(['member.user', 'workoutPlan', 'dietPlan', 'assigner'])
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereHas('member', fn ($m) => $m->whereIn('home_branch_id', $scopedBranchIds)))
            ->latest()
            ->limit(30)
            ->get();

        $members = Member::with('user')
            ->when(count($scopedBranchIds) > 0, fn ($q) => $q->whereIn('home_branch_id', $scopedBranchIds))
            ->latest()
            ->limit(100)
            ->get();

        return view('livewire.admin.programs.program-manager', [
            'workoutPlans' => $workoutPlans,
            'dietPlans' => $dietPlans,
            'assignments' => $assignments,
            'members' => $members,
            'canManage' => Auth::user()->can('workouts.manage') || Auth::user()->can('diets.manage'),
        ]);
    }
}

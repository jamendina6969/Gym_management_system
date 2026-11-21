<?php

use App\Livewire\Admin\Programs\ProgramManager;
use App\Models\Branch;
use App\Models\DietPlan;
use App\Models\Member;
use App\Models\PlanAssignment;
use App\Models\User;
use App\Models\WorkoutPlan;
use Database\Seeders\RoleAndPermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('trainer can create workout & diet plans, assign to member, and member views them in portal', function () {
    $branch = Branch::factory()->create();
    $trainer = User::factory()->create();
    $trainer->assignRole('trainer');
    $trainer->branches()->attach($branch->id, ['is_primary' => true]);

    $memberUser = User::factory()->create();
    $memberUser->assignRole('member');
    $member = Member::create([
        'user_id' => $memberUser->id,
        'home_branch_id' => $branch->id,
        'status' => 'active',
    ]);

    // Create workout plan
    Livewire::actingAs($trainer)
        ->test(ProgramManager::class)
        ->call('openWorkoutModal')
        ->set('w_title', 'Upper/Lower Strength')
        ->set('w_goal', 'Strength')
        ->set('w_difficulty', 'intermediate')
        ->set('w_duration_weeks', 6)
        ->set('w_items', [
            ['day_label' => 'Day 1 - Upper', 'exercise_name' => 'Incline Dumbbell Press', 'sets' => 4, 'reps' => '8-10', 'rest_seconds' => 90],
            ['day_label' => 'Day 2 - Lower', 'exercise_name' => 'Barbell Back Squat', 'sets' => 5, 'reps' => '5', 'rest_seconds' => 120],
        ])
        ->call('saveWorkout')
        ->assertHasNoErrors();

    $workout = WorkoutPlan::where('title', 'Upper/Lower Strength')->firstOrFail();
    expect($workout->items()->count())->toBe(2);

    // Create diet plan
    Livewire::actingAs($trainer)
        ->test(ProgramManager::class)
        ->call('openDietModal')
        ->set('d_title', 'Lean Muscle Fuel')
        ->set('d_calories', '2400')
        ->set('d_protein', '170')
        ->set('d_carbs', '260')
        ->set('d_fat', '70')
        ->set('d_items', [
            ['meal_time' => 'Breakfast', 'food_items' => '4 egg whites + 2 whole eggs + oats', 'calories' => '600', 'protein_grams' => '42'],
        ])
        ->call('saveDiet')
        ->assertHasNoErrors();

    $diet = DietPlan::where('title', 'Lean Muscle Fuel')->firstOrFail();

    // Assign both plans to member
    Livewire::actingAs($trainer)
        ->test(ProgramManager::class)
        ->call('openAssignModal')
        ->set('assign_member_id', $member->id)
        ->set('assign_workout_id', $workout->id)
        ->set('assign_diet_id', $diet->id)
        ->set('assign_starts_at', now()->toDateString())
        ->call('saveAssignment')
        ->assertHasNoErrors();

    expect(PlanAssignment::where('member_id', $member->id)->count())->toBe(1);

    // Member views assigned plans in portal
    $this->actingAs($memberUser)
        ->get(route('portal.programs'))
        ->assertOk()
        ->assertSee('Upper/Lower Strength')
        ->assertSee('Incline Dumbbell Press')
        ->assertSee('Lean Muscle Fuel')
        ->assertSee('2400 kcal');
});

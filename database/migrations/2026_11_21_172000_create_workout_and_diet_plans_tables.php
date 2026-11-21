<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 150);
            $table->string('goal', 80)->nullable();
            $table->string('difficulty', 20)->default('intermediate'); // beginner, intermediate, advanced
            $table->unsignedInteger('duration_weeks')->default(4);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('workout_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_plan_id')->constrained()->cascadeOnDelete();
            $table->string('day_label', 50)->default('Day 1');
            $table->string('exercise_name', 120);
            $table->unsignedInteger('sets')->default(3);
            $table->string('reps', 30)->default('10-12');
            $table->unsignedInteger('rest_seconds')->nullable()->default(60);
            $table->string('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('diet_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 150);
            $table->string('goal', 80)->nullable();
            $table->unsignedInteger('daily_calories')->nullable();
            $table->unsignedInteger('protein_grams')->nullable();
            $table->unsignedInteger('carbs_grams')->nullable();
            $table->unsignedInteger('fat_grams')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('diet_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diet_plan_id')->constrained()->cascadeOnDelete();
            $table->string('meal_time', 50); // Breakfast, Lunch, Snack, Dinner
            $table->text('food_items');
            $table->unsignedInteger('calories')->nullable();
            $table->unsignedInteger('protein_grams')->nullable();
            $table->string('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('plan_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workout_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('diet_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->string('status', 20)->default('active'); // active, completed, archived
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_assignments');
        Schema::dropIfExists('diet_plan_items');
        Schema::dropIfExists('diet_plans');
        Schema::dropIfExists('workout_plan_items');
        Schema::dropIfExists('workout_plans');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('specialization', 120)->nullable();
            $table->text('bio')->nullable();
            $table->string('certifications')->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('gym_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->string('category', 50)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('default_capacity')->default(20);
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('room', 80)->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedInteger('capacity')->default(20);
            $table->string('status', 20)->default('scheduled'); // scheduled, cancelled, completed
            $table->timestamps();

            $table->index(['branch_id', 'starts_at']);
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('confirmed'); // confirmed, waitlisted, attended, cancelled, no_show
            $table->unsignedInteger('waitlist_position')->nullable();
            $table->timestamp('booked_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['class_schedule_id', 'member_id']);
            $table->index(['class_schedule_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('class_schedules');
        Schema::dropIfExists('gym_classes');
        Schema::dropIfExists('trainers');
    }
};

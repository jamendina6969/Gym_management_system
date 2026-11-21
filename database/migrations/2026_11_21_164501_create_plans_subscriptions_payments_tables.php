<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('billing_cycle', 20)->default('monthly'); // daily, weekly, monthly, quarterly, annual, custom
            $table->unsignedInteger('duration_days')->default(30);
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('class_credits')->nullable(); // null = unlimited
            $table->string('access_scope', 20)->default('single_branch'); // single_branch, all_access
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->date('starts_at');
            $table->date('ends_at');
            $table->decimal('price', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->unsignedInteger('remaining_class_credits')->nullable();
            $table->string('status', 20)->default('pending'); // pending, active, frozen, expired, cancelled
            $table->date('frozen_at')->nullable();
            $table->date('frozen_until')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['member_id', 'status']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 40)->unique();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('PHP');
            $table->string('payment_method', 30)->default('cash'); // cash, card, gcash, maya, bank_transfer, paymongo, stripe
            $table->string('gateway', 30)->default('manual'); // manual, paymongo, stripe
            $table->string('gateway_reference')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('status', 20)->default('paid'); // pending, paid, failed, refunded
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['member_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('membership_plans');
    }
};

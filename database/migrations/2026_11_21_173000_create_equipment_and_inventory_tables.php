<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('category', 60)->nullable(); // Cardio, Free Weights, Machines, Accessories
            $table->string('serial_number', 80)->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 10, 2)->nullable();
            $table->string('condition', 30)->default('good'); // good, needs_maintenance, out_of_order
            $table->date('last_maintained_at')->nullable();
            $table->date('next_maintenance_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'condition']);
        });

        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->date('performed_at');
            $table->text('issue_description');
            $table->text('action_taken')->nullable();
            $table->decimal('cost', 10, 2)->default(0);
            $table->string('performed_by', 120)->nullable();
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('sku', 40)->nullable();
            $table->string('category', 60)->nullable(); // Supplements, Beverages, Merchandise
            $table->decimal('price', 10, 2);
            $table->decimal('cost', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'is_active']);
        });

        Schema::create('product_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_method', 30)->default('cash');
            $table->foreignId('sold_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sold_at');
            $table->timestamps();

            $table->index(['branch_id', 'sold_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_sales');
        Schema::dropIfExists('products');
        Schema::dropIfExists('maintenance_logs');
        Schema::dropIfExists('equipment');
    }
};

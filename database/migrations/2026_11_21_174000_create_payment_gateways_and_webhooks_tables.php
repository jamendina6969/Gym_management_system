<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('driver', 30); // paymongo, stripe, manual
            $table->string('display_name', 80);
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_default')->default(false);
            $table->string('mode', 20)->default('test'); // test, live
            $table->text('public_key')->nullable(); // encrypted
            $table->text('secret_key')->nullable(); // encrypted
            $table->text('webhook_secret')->nullable(); // encrypted
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'driver']);
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('driver', 30);
            $table->string('event_id', 150)->unique();
            $table->string('event_type', 100);
            $table->json('payload')->nullable();
            $table->string('status', 20)->default('processed'); // processed, ignored, failed
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('payment_gateways');
    }
};

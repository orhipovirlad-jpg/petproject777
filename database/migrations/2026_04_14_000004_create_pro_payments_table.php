<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pro_payments', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->index();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('RUB');
            $table->string('provider', 32)->default('yookassa');
            $table->string('provider_payment_id', 128)->nullable()->unique();
            $table->string('idempotence_key', 64)->unique();
            $table->string('status', 32)->default('pending');
            $table->string('confirmation_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pro_payments');
    }
};

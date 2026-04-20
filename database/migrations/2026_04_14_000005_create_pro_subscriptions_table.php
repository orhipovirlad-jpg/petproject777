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
        Schema::create('pro_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->string('plan', 32)->default('pro');
            $table->decimal('amount', 10, 2)->default(790);
            $table->string('currency', 3)->default('RUB');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->index();
            $table->string('status', 32)->default('active');
            $table->unsignedBigInteger('last_payment_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pro_subscriptions');
    }
};

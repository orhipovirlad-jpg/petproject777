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
        Schema::create('calculation_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('ip_address', 45)->index();
            $table->string('platform', 32);
            $table->string('email')->nullable();
            $table->decimal('cost_price', 10, 2);
            $table->decimal('packaging_cost', 10, 2);
            $table->decimal('logistics_cost', 10, 2);
            $table->decimal('commission_percent', 5, 2);
            $table->decimal('ad_spend_percent', 5, 2);
            $table->decimal('tax_percent', 5, 2);
            $table->decimal('returns_percent', 5, 2);
            $table->decimal('desired_margin_percent', 5, 2);
            $table->decimal('break_even_price', 10, 2);
            $table->decimal('recommended_price', 10, 2);
            $table->decimal('net_profit', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calculation_requests');
    }
};

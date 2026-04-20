<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workbook_model_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('model', 32);
            $table->decimal('commission_percent', 5, 2)->default(0);
            $table->decimal('buyout_percent', 5, 2)->default(95);
            $table->decimal('logistics_base_cost', 10, 2)->default(0);
            $table->decimal('storage_daily_cost', 10, 2)->default(0);
            $table->decimal('storage_days', 8, 2)->default(0);
            $table->decimal('extra_cost', 10, 2)->default(0);
            $table->decimal('ad_spend_percent', 5, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(7);
            $table->decimal('mp_discount_percent', 5, 2)->default(0);
            $table->decimal('acquiring_percent', 5, 2)->default(0);
            $table->decimal('last_mile_cost', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workbook_model_settings');
    }
};

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
        Schema::create('ai_insights', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('calculation_request_id')->nullable()->index();
            $table->string('type', 32)->index();
            $table->string('source', 32)->default('fallback');
            $table->string('email')->nullable()->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('status', 32)->default('success');
            $table->json('input_payload')->nullable();
            $table->json('output_payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_insights');
    }
};

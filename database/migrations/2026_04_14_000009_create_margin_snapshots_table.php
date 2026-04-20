<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('margin_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->index();
            $table->string('title', 190);
            $table->string('platform', 32);
            $table->json('input_payload');
            $table->json('result_payload');
            $table->timestamps();

            $table->index(['email', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('margin_snapshots');
    }
};

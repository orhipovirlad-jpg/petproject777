<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calculation_requests', function (Blueprint $table): void {
            $table->unsignedInteger('planned_units')->nullable()->after('desired_margin_percent');
        });
    }

    public function down(): void
    {
        Schema::table('calculation_requests', function (Blueprint $table): void {
            $table->dropColumn('planned_units');
        });
    }
};

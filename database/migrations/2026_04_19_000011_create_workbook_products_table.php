<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workbook_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('group', 120);
            $table->string('name', 190);
            $table->string('sku', 120);
            $table->string('barcode', 120)->nullable();
            $table->decimal('purchase_price', 10, 2);
            $table->decimal('agent_percent', 5, 2)->default(0);
            $table->decimal('defect_percent', 5, 2)->default(0);
            $table->decimal('delivery_cost', 10, 2)->default(0);
            $table->decimal('marking_cost', 10, 2)->default(0);
            $table->decimal('storage_cost', 10, 2)->default(0);
            $table->decimal('packaging_cost', 10, 2)->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->decimal('sale_price', 10, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workbook_products');
    }
};

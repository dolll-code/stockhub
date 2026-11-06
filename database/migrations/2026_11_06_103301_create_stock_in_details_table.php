<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_in_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_in_id')->constrained('stock_ins')->onDelete('restrict');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['stock_in_id', 'product_id'], 'stock_in_details_header_product_unique');
            $table->index('product_id', 'stock_in_details_product_index');
        });

        DB::statement('ALTER TABLE stock_in_details ADD CONSTRAINT chk_stock_in_details_quantity CHECK (quantity > 0)');
        DB::statement('ALTER TABLE stock_in_details ADD CONSTRAINT chk_stock_in_details_unit_price CHECK (unit_price >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_in_details');
    }
};

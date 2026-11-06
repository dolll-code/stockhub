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
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->date('adjustment_date');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->decimal('system_qty', 15, 3);
            $table->decimal('physical_qty', 15, 3);
            $table->decimal('difference', 15, 3);
            $table->string('reason_code', 30);
            $table->text('notes');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->index('adjustment_date', 'stock_adjustments_date_index');
            $table->index(['product_id', 'adjustment_date'], 'stock_adjustments_product_date_index');
            $table->index('created_by', 'stock_adjustments_created_by_index');
        });

        DB::statement('ALTER TABLE stock_adjustments ADD CONSTRAINT chk_stock_adjustments_system_qty CHECK (system_qty >= 0)');
        DB::statement('ALTER TABLE stock_adjustments ADD CONSTRAINT chk_stock_adjustments_physical_qty CHECK (physical_qty >= 0)');
        DB::statement('ALTER TABLE stock_adjustments ADD CONSTRAINT chk_stock_adjustments_difference_not_zero CHECK (difference <> 0)');
        DB::statement('ALTER TABLE stock_adjustments ADD CONSTRAINT chk_stock_adjustments_difference_formula CHECK (difference = physical_qty - system_qty)');
        DB::statement("ALTER TABLE stock_adjustments ADD CONSTRAINT chk_stock_adjustments_reason_code CHECK (reason_code IN ('damaged', 'lost', 'recording_error', 'count_error', 'expired', 'found', 'other'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};

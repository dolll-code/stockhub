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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->string('movement_type', 20);
            $table->string('reference_type', 30)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_number', 30)->nullable();
            $table->date('movement_date');
            $table->decimal('qty_before', 15, 3);
            $table->decimal('qty_change', 15, 3);
            $table->decimal('qty_after', 15, 3);
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('notes', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['product_id', 'id'], 'stock_movements_product_id_index');
            $table->index('movement_date', 'stock_movements_date_index');
            $table->index(['movement_type', 'movement_date'], 'stock_movements_type_date_index');
            $table->index(['reference_type', 'reference_id'], 'stock_movements_reference_index');
            $table->index('user_id', 'stock_movements_user_index');
        });

        DB::statement("ALTER TABLE stock_movements ADD CONSTRAINT chk_stock_movements_type CHECK (movement_type IN ('opening_balance', 'stock_in', 'stock_out', 'adjustment', 'stock_in_cancel', 'stock_out_cancel'))");
        DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT chk_stock_movements_qty_before CHECK (qty_before >= 0)');
        DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT chk_stock_movements_qty_change_not_zero CHECK (qty_change <> 0)');
        DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT chk_stock_movements_qty_after CHECK (qty_after >= 0)');
        DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT chk_stock_movements_formula CHECK (qty_after = qty_before + qty_change)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};

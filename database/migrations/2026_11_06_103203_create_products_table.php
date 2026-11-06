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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150)->index();
            $table->foreignId('category_id')->constrained('categories')->onDelete('restrict');
            $table->foreignId('unit_id')->constrained('units')->onDelete('restrict');
            $table->foreignId('main_supplier_id')->nullable()->constrained('suppliers')->onDelete('restrict');
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->nullable();
            $table->decimal('current_stock', 15, 3)->default(0);
            $table->decimal('min_stock', 15, 3)->default(0);
            $table->string('storage_location', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->index(['category_id', 'is_active'], 'products_category_is_active_index');
            $table->index(['is_active', 'current_stock'], 'products_is_active_current_stock_index');
        });

        DB::statement('ALTER TABLE products ADD CONSTRAINT chk_products_purchase_price CHECK (purchase_price >= 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT chk_products_selling_price CHECK (selling_price IS NULL OR selling_price >= 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT chk_products_current_stock CHECK (current_stock >= 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT chk_products_min_stock CHECK (min_stock >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

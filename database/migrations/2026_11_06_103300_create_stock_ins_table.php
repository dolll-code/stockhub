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
        Schema::create('stock_ins', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->date('transaction_date');
            $table->string('source_type', 20);
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('restrict');
            $table->string('reference_document', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('draft');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['status', 'transaction_date'], 'stock_ins_status_date_index');
            $table->index('supplier_id', 'stock_ins_supplier_index');
            $table->index(['created_by', 'status'], 'stock_ins_created_by_status_index');
        });

        DB::statement("ALTER TABLE stock_ins ADD CONSTRAINT chk_stock_ins_source_supplier CHECK (source_type <> 'purchase' OR supplier_id IS NOT NULL)");
        DB::statement('ALTER TABLE stock_ins ADD CONSTRAINT chk_stock_ins_total_amount CHECK (total_amount >= 0)');
        DB::statement("ALTER TABLE stock_ins ADD CONSTRAINT chk_stock_ins_source_type CHECK (source_type IN ('purchase', 'internal', 'return', 'other'))");
        DB::statement("ALTER TABLE stock_ins ADD CONSTRAINT chk_stock_ins_status CHECK (status IN ('draft', 'confirmed', 'cancelled'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_ins');
    }
};

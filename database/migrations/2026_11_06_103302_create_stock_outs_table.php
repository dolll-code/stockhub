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
        Schema::create('stock_outs', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->date('transaction_date');
            $table->string('purpose_type', 20);
            $table->string('destination', 150);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['status', 'transaction_date'], 'stock_outs_status_date_index');
            $table->index(['created_by', 'status'], 'stock_outs_created_by_status_index');
        });

        DB::statement("ALTER TABLE stock_outs ADD CONSTRAINT chk_stock_outs_purpose_type CHECK (purpose_type IN ('usage', 'sale', 'shipment', 'transfer_to_party', 'other'))");
        DB::statement("ALTER TABLE stock_outs ADD CONSTRAINT chk_stock_outs_status CHECK (status IN ('draft', 'confirmed', 'cancelled'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_outs');
    }
};

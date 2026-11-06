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
        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 100)->change();
            $table->string('email', 150)->change();
            $table->boolean('is_active')->default(true)->after('password')->index();
            $table->boolean('must_change_password')->default(false)->after('is_active');
            $table->dateTime('last_login_at')->nullable()->after('must_change_password');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn(['is_active', 'must_change_password', 'last_login_at', 'last_login_ip']);
            $table->string('name', 255)->change();
            $table->string('email', 255)->change();
        });
    }
};

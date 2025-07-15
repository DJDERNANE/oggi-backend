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
            $table->float('payments')->nullable();
            $table->float('debts')->nullable();
            $table->timestamp('last_payment_time')->nullable();
            $table->timestamp('last_debt_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['payments', 'debts', 'last_payment_time', 'last_debt_time']);
        });
    }
};

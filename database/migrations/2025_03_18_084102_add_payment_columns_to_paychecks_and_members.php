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
        Schema::table('paychecks', function (Blueprint $table) {
            $table->tinyInteger('payment_type')->nullable();
            $table->date('target_payment_date')->nullable();
            $table->tinyInteger('salary_payment_type')->nullable();
        });

        Schema::table('members', function (Blueprint $table) {
            $table->date('last_payment_date')->nullable();
            $table->date('last_target_payment_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paychecks', function (Blueprint $table) {
            $table->dropColumn(['payment_type', 'target_payment_date', 'salary_payment_type']);
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['last_payment_date', 'last_target_payment_date']);
        });
    }
};

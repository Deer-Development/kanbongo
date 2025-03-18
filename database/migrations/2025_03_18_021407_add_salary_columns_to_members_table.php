<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\PaymentType;
use App\Enums\SalaryPaymentTypes;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->tinyInteger('payment_type')->default(PaymentType::HOURLY)->index();
            $table->decimal('salary', 10, 2)->nullable();
            $table->tinyInteger('salary_payment_type')->default(SalaryPaymentTypes::MONTHLY)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('payment_type');
            $table->dropColumn('salary');
            $table->dropColumn('salary_payment_type');
        });
    }
};

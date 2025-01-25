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
        Schema::table('time_entries', function (Blueprint $table) {
            $table->boolean('added_manually')->default(false)->after('billable');
            $table->boolean('is_paid')->default(false)->after('task_id');
            $table->decimal('amount_paid', 10, 2)->nullable()->after('is_paid');
            $table->decimal('paid_rate', 10, 2)->nullable()->after('amount_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropColumn('added_manually');
        });
    }
};

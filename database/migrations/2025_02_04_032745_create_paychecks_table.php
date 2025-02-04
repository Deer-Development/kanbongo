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
        Schema::create('paychecks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('container_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->decimal('total_hours', 8, 2);
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->timestamps();
        });

        Schema::table('time_entries', function (Blueprint $table) {
            $table->foreignId('paycheck_id')->nullable()->constrained('paychecks')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropForeign(['paycheck_id']);
            $table->dropColumn('paycheck_id');
        });
        Schema::dropIfExists('paychecks');
    }
};

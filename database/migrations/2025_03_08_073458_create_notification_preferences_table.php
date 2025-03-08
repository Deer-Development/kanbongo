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
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Activities notifications
            $table->boolean('activities_enabled')->default(true);
            $table->enum('activities_frequency', ['4_hours', '8_hours', 'daily'])->default('daily');
            
            // Daily reports
            $table->boolean('member_report_enabled')->default(true);
            $table->boolean('owner_report_enabled')->default(false);
            $table->time('daily_report_time')->default('00:00:00');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};

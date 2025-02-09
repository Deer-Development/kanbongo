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
        Schema::create('comment_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('comments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->constrained('comments')->onDelete('cascade');
        });

        Schema::create('comment_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('comments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('mention_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mention_id')->constrained('comment_mentions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_mentions');

        Schema::table('comments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });

        Schema::dropIfExists('comment_reads');
        Schema::dropIfExists('mention_reads');
    }
};

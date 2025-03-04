<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('documentation_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documentation_tab_id')->constrained()->onDelete('cascade');
            $table->longText('content');
            $table->integer('version_number');
            $table->foreignId('created_by')->constrained('users');
            $table->string('comment');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('documentation_versions');
    }
}; 
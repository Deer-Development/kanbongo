<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ChangeTaskNameToText extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->text('name')->change();
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
            DB::statement('CREATE INDEX tasks_name_gin_idx ON tasks USING gin (name gin_trgm_ops)');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS tasks_name_gin_idx');
        }
        
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('name')->change();
        });
        
        Schema::table('tasks', function (Blueprint $table) {
            $table->index('name');
        });
    }
}
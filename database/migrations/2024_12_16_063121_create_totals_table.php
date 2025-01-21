<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Permission;

class CreateTotalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('totals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('container_id')
                ->references('id')
                ->on('containers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('task_id')
                ->references('id')
                ->on('tasks')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->decimal('total_billable', 15, 2)->default(0.00);
            $table->decimal('total_non_billable', 15, 2)->default(0.00);
            $table->bigInteger('total_seconds')->unsigned()->default(0);

            $table->timestamps();
        });

        Permission::addPermissions('Total');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('totals');
    }
}

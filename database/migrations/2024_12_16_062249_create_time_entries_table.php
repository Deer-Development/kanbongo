<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Permission;

class CreateTimeEntriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_entries', function (Blueprint $table): void {
            $table->id();
            $table->dateTime('start');
            $table->dateTime('end')->nullable();

            $table->decimal('billable_rate', 10, 2)->nullable();
            $table->boolean('billable')->default(false);

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

            $table->timestamps();
            $table->softDeletes();

            $table->index('start');
            $table->index('end');
            $table->index('billable');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('time_entries');
    }
}

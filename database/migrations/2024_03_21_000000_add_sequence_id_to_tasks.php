<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Container;
use App\Models\Task;

return new class extends Migration
{
    public function up()
    {
        // Add sequence_id and container_id columns
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedInteger('sequence_id')->after('board_id')->nullable();
            $table->foreignId('container_id')->after('sequence_id')->nullable();
        });

        // Populate container_id and sequence_id for existing tasks
        $containers = Container::with(['boards.tasks' => function ($query) {
            $query->orderBy('id');
        }])->get();

        foreach ($containers as $container) {
            $sequenceId = 1;
            
            foreach ($container->boards as $board) {
                foreach ($board->tasks as $task) {
                    $task->update([
                        'sequence_id' => $sequenceId,
                        'container_id' => $container->id
                    ]);
                    $sequenceId++;
                }
            }
        }

        // Add unique constraint for sequence_id in the context of a container
        Schema::table('tasks', function (Blueprint $table) {
            $table->unique(['sequence_id', 'container_id']);
            $table->foreign('container_id')
                ->references('id')
                ->on('containers')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['container_id']);
            $table->dropUnique(['sequence_id', 'container_id']);
            $table->dropColumn(['sequence_id', 'container_id']);
        });
    }
}; 
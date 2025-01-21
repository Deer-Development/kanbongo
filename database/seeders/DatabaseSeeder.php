<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\Comment;
use App\Models\Container;
use App\Models\Member;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesTableSeeder::class);
        $this->call(UsersTableSeeder::class);

//        $users = User::factory()->count(10)->create();
//
//        $projectNames = [
//            "E-commerce Website",
//            "Portfolio Website",
//            "Corporate Website",
//            "Social Media Platform",
//            "Learning Management System",
//            "Real Estate Platform",
//            "Healthcare Management System",
//            "Online Marketplace",
//            "Event Management System",
//            "Blog Platform"
//        ];
//
//        $containerNames = [
//            "Planning",
//            "Design",
//            "Development",
//            "Testing",
//            "Deployment",
//            "Maintenance"
//        ];
//
//        $boardNames = [
//            "Backlog",
//            "To Do",
//            "In Progress",
//            "Review",
//            "Completed"
//        ];
//
//        foreach ($projectNames as $projectName) {
//            $project = Project::create([
//                'name' => $projectName,
//                'description' => "Description for $projectName",
//                'owner_id' => $users->random()->id,
//            ]);
//
//            foreach ($containerNames as $containerName) {
//                $container = Container::create([
//                    'project_id' => $project->id,
//                    'name' => $containerName,
//                    'owner_id' => $users->random()->id,
//                    'is_active' => true,
//                ]);
//
//                $containerMembers = $users->random(rand(4, 6));
//                foreach ($containerMembers as $member) {
//                    Member::create([
//                        'memberable_type' => Container::class,
//                        'memberable_id' => $container->id,
//                        'user_id' => $member->id,
//                    ]);
//                }
//
//                foreach ($boardNames as $boardName) {
//                    $board = Board::create([
//                        'container_id' => $container->id,
//                        'name' => $boardName,
//                        'is_active' => true,
//                        'order' => array_search($boardName, $boardNames) + 1,
//                        'color' => sprintf('#%06X', mt_rand(0, 0xFFFFFF)),
//                    ]);
//
//                    for ($t = 1; $t <= rand(10, 15); $t++) {
//                        $task = Task::create([
//                            'board_id' => $board->id,
//                            'name' => "Task $t: " . fake()->sentence(3),
//                            'is_active' => true,
//                            'order' => $t,
//                        ]);
//
//                        $taskMembers = $containerMembers->random(rand(2, 3));
//                        foreach ($taskMembers as $member) {
//                            Member::create([
//                                'memberable_type' => Task::class,
//                                'memberable_id' => $task->id,
//                                'user_id' => $member->id,
//                            ]);
//                        }
//
//                        for ($cm = 1; $cm <= rand(1, 3); $cm++) {
//                            Comment::create([
//                                'commentable_id' => $task->id,
//                                'commentable_type' => Task::class,
//                                'created_by' => $users->random()->id,
//                                'content' => "This is comment $cm for Task $t in Board {$board->name}",
//                            ]);
//                        }
//                    }
//                }
//            }
//        }
    }
}

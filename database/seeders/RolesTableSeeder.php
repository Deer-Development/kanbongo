<?php

namespace Database\Seeders;

use App\Enums\PermissionType;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->createRoles();
    }

    /**
     * Create predefined roles.
     *
     * @return void
     */
    protected function createRoles(): void
    {
        $roles = [
            ['name' => 'Super-Admin', 'type' => PermissionType::SUPER],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role['name'],
                'guard_name' => 'web',
                'type' => $role['type'],
            ]);
        }
    }
}

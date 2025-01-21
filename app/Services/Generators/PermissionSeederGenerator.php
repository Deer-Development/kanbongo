<?php

namespace App\Services\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class PermissionSeederGenerator
{
    public static function generateFromMigration($pluralName, $permissionPrefix): void
    {
        $migrationPath = database_path('migrations');
        $files = File::files($migrationPath);

        foreach ($files as $file) {
            if (Str::contains($file->getFilename(), "create_{$pluralName}_table")) {
                self::generatePermissions($permissionPrefix);
            }
        }
    }

    protected static function generatePermissions(string $permissionPrefix): void
    {
        $permissionsFolder = database_path('seeders/Permissions');

        if (!File::exists($permissionsFolder)) {
            File::makeDirectory($permissionsFolder, 0755, true);
        }

        $seederName = implode('', array_map('ucfirst', explode('-', $permissionPrefix))) . "RolePermissionSeeder";
        $roleSeederPath = "{$permissionsFolder}/{$seederName}.php";

        $permissions = [
            'view', 'create', 'edit', 'delete', 'list'
        ];

        $permissionEntries = array_map(function ($action) use ($permissionPrefix) {
            $permissionName = "{$action}-{$permissionPrefix}";
            return <<<PHP
        Permission::create([
            'name' => '{$permissionName}',
            'display_name' => ucfirst(str_replace('-', ' ', '{$permissionName}')),
            'description' => 'Can {$action} {$permissionPrefix}',
            'type' => PermissionType::COMPANY,
            'is_default' => true,
        ]);
PHP;
        }, $permissions);

        $roleContent = <<<PHP
<?php

namespace Database\Seeders\Permissions;

use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;

class {$seederName} extends Seeder
{
    public function run()
    {
        {$permissionEntries}
    }
}
PHP;

        $roleContent = str_replace(
            ['{$permissionEntries}'],
            [implode("\n\n", $permissionEntries)],
            $roleContent
        );

        File::put($roleSeederPath, $roleContent);
    }
}

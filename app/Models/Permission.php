<?php

namespace App\Models;

use App\Enums\PermissionType;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    public static function addPermissions(string $prefix, $type = null): void
    {
        $kebabPrefix = Str::kebab($prefix);

        $actions = [
            'view' => 'Permission to view',
            'create' => 'Permission to create',
            'edit' => 'Permission to edit',
            'delete' => 'Permission to delete',
            'list' => 'Permission to list',
        ];

        foreach ($actions as $action => $description) {
            $name = "{$action}-{$kebabPrefix}";
            self::firstOrCreate(
                ['name' => $name],
                [
                    'display_name' => ucfirst(str_replace('-', ' ', $name)),
                    'description' => "{$description} {$kebabPrefix}",
                    'category' => $kebabPrefix, // Change this to PermissionType::{YOUR_TYPE} if needed
                    'type' => $type ?? PermissionType::SUPER,
                    'is_default' => true,
                ]
            );
        }
    }
}

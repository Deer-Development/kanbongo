<?php

namespace App\Enums;

use App\Services\Enum;

class PermissionType extends Enum
{
    public const SUPER = 1;
    public const COMPANY = 2;

    protected static array $data = [
        self::SUPER => 'Super Admin',
        self::COMPANY => 'Company Admin',
    ];

    public static function getKey($value): int
    {
        return array_search($value, self::$data);
    }

    public static function getName($value): string
    {
        return self::$data[$value];
    }
}

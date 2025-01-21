<?php

namespace App\Enums;

use App\Services\Enum;

class Priority extends Enum
{
    public const URGENT = 1;
    public const HIGH = 2;
    public const NORMAL = 3;
    public const LOW = 4;

    protected static array $data = [
        self::URGENT => 'Urgent',
        self::HIGH => 'High',
        self::NORMAL => 'Normal',
        self::LOW => 'Low',
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

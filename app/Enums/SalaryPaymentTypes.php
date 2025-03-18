<?php

namespace App\Enums;

use App\Services\Enum;

class SalaryPaymentTypes extends Enum
{
    public const MONTHLY = 1;
    public const WEEKLY = 2;
    public const BI_WEEKLY = 3;
    
    protected static array $data = [
        self::MONTHLY => 'Monthly',
        self::WEEKLY => 'Weekly',
        self::BI_WEEKLY => 'Bi-Weekly',
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
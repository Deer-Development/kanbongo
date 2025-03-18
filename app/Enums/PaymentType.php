<?php

namespace App\Enums;

use App\Services\Enum;

class PaymentType extends Enum
{
    public const HOURLY = 1;
    public const SALARY = 2;
    public const NO_PAYMENT = 3;

    protected static array $data = [
        self::HOURLY => 'Hourly',
        self::SALARY => 'Salary',
        self::NO_PAYMENT => 'No Payment',
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

<?php

namespace App\Enums;

use App\Services\Enum;

class Colors extends Enum
{
    public const DATA = [
        '#ef5350',
        '#ec407a',
        '#ab47bc',
        '#7e57c2',
        '#5c6bc0',
        '#42a5f5',
        '#29b6f6',
        '#26c6da',
        '#26a69a',
        '#66bb6a',
        '#9ccc65',
        '#d4e157',
        '#ffee58',
        '#ffca28',
        '#ffa726',
        '#ff7043',
        '#8d6e63',
        '#bdbdbd',
        '#78909c',
    ];

    public static function getKey($value): int
    {
        return array_search($value, self::$data);
    }

    private const VALID_REGEX = '/^#[0-9a-f]{6}$/';

    public function getRandomColor(?string $seed = null): string
    {
        if ($seed !== null) {
            srand(crc32($seed));
        }

        return self::DATA[array_rand(self::DATA)];
    }

    public function isValid(string $color): bool
    {
        return preg_match(self::VALID_REGEX, $color) === 1;
    }

    public static function getName($value): string
    {
        return self::$data[$value];
    }
}

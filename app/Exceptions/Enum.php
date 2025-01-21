<?php

namespace App\Exceptions;

use InvalidArgumentException;

class Enum extends InvalidArgumentException
{
    public static function keyNotFound(): static
    {
        return new static(__('Key not found'));
    }
}

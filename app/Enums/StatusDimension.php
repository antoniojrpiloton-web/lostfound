<?php

namespace App\Enums;

enum StatusDimension: string
{
    case Verification = 'verification';
    case Item = 'item';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

<?php

namespace App\Enums;

enum RoleScope: string
{
    case Platform = 'platform';
    case Organization = 'organization';
    case Public = 'public';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

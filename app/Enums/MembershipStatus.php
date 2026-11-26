<?php

namespace App\Enums;

enum MembershipStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Revoked = 'revoked';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

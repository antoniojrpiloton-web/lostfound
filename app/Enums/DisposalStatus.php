<?php

namespace App\Enums;

enum DisposalStatus: string
{
    case Eligible = 'eligible';
    case Proposed = 'proposed';
    case Approved = 'approved';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

<?php

namespace App\Enums;

enum MatchStatus: string
{
    case Suggested = 'suggested';
    case Possible = 'possible';
    case Dismissed = 'dismissed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

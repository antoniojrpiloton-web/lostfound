<?php

namespace App\Enums;

enum ImageValidationStatus: string
{
    case Passed = 'passed';
    case Review = 'review';
    case Rejected = 'rejected';
    case Overridden = 'overridden';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

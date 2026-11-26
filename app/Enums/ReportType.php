<?php

namespace App\Enums;

enum ReportType: string
{
    case Lost = 'lost';
    case Found = 'found';

    public function label(): string
    {
        return match ($this) {
            self::Lost => 'Lost',
            self::Found => 'Found',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

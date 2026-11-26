<?php

namespace App\Enums;

enum OrganizationType: string
{
    case BarangayLgu = 'barangay_lgu';
    case PoliceStation = 'police_station';

    public function label(): string
    {
        return match ($this) {
            self::BarangayLgu => 'Police Assistance Booth',
            self::PoliceStation => 'Police Station',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

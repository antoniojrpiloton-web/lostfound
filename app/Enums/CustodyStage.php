<?php

namespace App\Enums;

enum CustodyStage: string
{
    case WithFinder = 'with_finder';
    case AtBooth = 'at_booth';
    case AtStation = 'at_station';
    case ScheduledForBooth = 'scheduled_for_booth';
    case Released = 'released';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::WithFinder => 'Held by Finder',
            self::AtBooth => 'At Police Booth / Tent',
            self::AtStation => 'At Police Station (Safekeeping)',
            self::ScheduledForBooth => 'Scheduled for Next-Day Booth Handoff',
            self::Released => 'Released to Owner',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::WithFinder => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            self::AtBooth => 'bg-info-subtle text-info-emphasis border border-info-subtle',
            self::AtStation => 'bg-primary-subtle text-primary border border-primary-subtle',
            self::ScheduledForBooth => 'bg-success-subtle text-success border border-success-subtle',
            self::Released => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
        };
    }
}

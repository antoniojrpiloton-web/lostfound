<?php

namespace App\Enums;

enum CustodyAction: string
{
    case Received = 'received';
    case Transferred = 'transferred';
    case Released = 'released';
    case Disposed = 'disposed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

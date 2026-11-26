<?php

namespace App\Enums;

enum ItemStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case AwaitingTurnover = 'awaiting_turnover';
    case InCustody = 'in_custody';
    case ClaimPending = 'claim_pending';
    case Returned = 'returned';
    case DisposalPending = 'disposal_pending';
    case Disposed = 'disposed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

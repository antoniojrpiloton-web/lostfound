<?php

namespace App\Enums;

enum ClaimStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case InfoRequested = 'info_requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Withdrawn = 'withdrawn';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

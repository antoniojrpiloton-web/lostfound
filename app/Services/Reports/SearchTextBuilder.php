<?php

namespace App\Services\Reports;

use App\Models\ItemReport;
use Illuminate\Support\Str;

class SearchTextBuilder
{
    public function build(ItemReport $report): string
    {
        $report->loadMissing(['category', 'barangay', 'location']);

        $categoryLabels = is_array($report->category?->labels)
            ? array_values($report->category->labels)
            : [];

        $parts = array_merge([
            $report->reference_no,
            $report->item_name,
            $report->description,
            $report->brand,
            $report->model,
            $report->color,
            $report->distinguishing_features,
            $report->location_details,
            $report->barangay?->name,
            $report->location?->name,
        ], $categoryLabels);

        $joined = implode(' ', array_filter($parts, fn (mixed $value): bool => is_string($value) && trim($value) !== ''));
        $ascii = Str::ascii(mb_strtolower($joined));
        $cleaned = preg_replace('/[^a-z0-9\s]+/', ' ', $ascii) ?? '';

        return trim((string) preg_replace('/\s+/', ' ', $cleaned));
    }
}

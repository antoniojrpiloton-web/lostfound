<?php

namespace App\Services\Reports;

use Illuminate\Support\Facades\DB;

class ReferenceNumberService
{
    public function next(string $prefix, ?int $year = null): string
    {
        $year ??= now()->year;
        $prefix = strtoupper(trim($prefix));

        $nextNumber = DB::transaction(function () use ($prefix, $year): int {
            $row = DB::table('reference_sequences')
                ->where('prefix', $prefix)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                DB::table('reference_sequences')->insert([
                    'prefix' => $prefix,
                    'year' => $year,
                    'last_number' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return 1;
            }

            $next = ((int) $row->last_number) + 1;

            DB::table('reference_sequences')
                ->where('prefix', $prefix)
                ->where('year', $year)
                ->update([
                    'last_number' => $next,
                    'updated_at' => now(),
                ]);

            return $next;
        });

        return sprintf('%s-%d-%06d', $prefix, $year, $nextNumber);
    }
}

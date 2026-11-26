<?php

namespace App\Services\Reports;

use App\Models\ItemRecovery;
use App\Models\ItemReport;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class OfficialPdfService
{
    public function buildTurnoverReceipt(ItemRecovery $recovery): string
    {
        $pdf = new OfficialDocumentPdf('P', 'mm', 'A4');
        $pdf->AliasNbPages();
        $pdf->SetMargins(15, 14, 15);
        $pdf->SetAutoPageBreak(true, 16);
        $pdf->documentTitle = 'LOST & FOUND TURNOVER RECEIPT';
        $pdf->issuingOffice = (string) ($recovery->organization?->name ?? 'Boracay Lost & Found Authority');

        $pdf->AddPage();
        $pageW = $pdf->GetPageWidth() - 30;

        $pdf->SetFont('Arial', '', 8.5);
        $pdf->SetTextColor(25, 25, 25);
        $pdf->MultiCell(
            $pageW,
            4.6,
            $pdf->encodeText('This form certifies the verified physical release of a recovered item under the Boracay Island Lost and Found System. Government ID details are masked pursuant to RA 10173 (Data Privacy Act of 2012).')
        );
        $pdf->Ln(3);

        $pdf->underlinedMetaRow([
            ['label' => 'Institution:', 'value' => (string) ($recovery->organization?->name ?? ''), 'width' => $pageW * 0.62],
            ['label' => 'Receipt Ref:', 'value' => (string) ($recovery->claim?->reference_no ?? 'REC-'.$recovery->id), 'width' => $pageW * 0.38],
        ]);
        $pdf->underlinedMetaRow([
            ['label' => 'Claimant:', 'value' => (string) ($recovery->received_by_name ?? ''), 'width' => $pageW * 0.34],
            ['label' => 'ID Presented:', 'value' => trim(($recovery->id_type ?? 'ID').' (****'.($recovery->id_last4 ?? '----').')'), 'width' => $pageW * 0.43],
            ['label' => 'Date:', 'value' => (string) ($recovery->turnover_at?->format('M d, Y') ?? now()->format('M d, Y')), 'width' => $pageW * 0.23],
        ]);

        $pdf->thickDivider();

        $cols = [$pageW * 0.34, $pageW * 0.66];
        $this->drawBlackHeader($pdf, ['Field', 'Recorded Details'], $cols, ['L', 'L']);

        $details = [
            ['Item Reference No.', (string) ($recovery->itemReport?->reference_no ?? '-')],
            ['Claim Reference No.', (string) ($recovery->claim?->reference_no ?? '-')],
            ['Item Name', (string) ($recovery->itemReport?->item_name ?? '-')],
            ['Category', (string) ($recovery->itemReport?->category?->label() ?? '-')],
            ['Brand / Color', trim(($recovery->itemReport?->brand ?? '-').' / '.($recovery->itemReport?->color ?? '-'))],
            ['Location Found', (string) ($recovery->itemReport?->location?->name ?? $recovery->itemReport?->barangay?->name ?? 'Boracay Island')],
            ['Date Found', (string) ($recovery->itemReport?->occurred_date?->format('M d, Y') ?? '-')],
            ['Verification Outcome', ucfirst((string) $recovery->verification_outcome)],
            ['Turnover Timestamp', (string) ($recovery->turnover_at?->format('M d, Y h:i A') ?? '-')],
            ['Processed By', (string) ($recovery->processor?->name ?? 'Institutional Staff')],
            ['Confirmed By', (string) ($recovery->confirmer?->name ?? 'Pending Confirmation')],
            ['Remarks', (string) ($recovery->remarks ?: 'Physical item inspected and released to verified owner.')],
        ];

        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        foreach ($details as [$label, $value]) {
            $pdf->SetFont('Arial', 'B', 8.5);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell($cols[0], 8.2, '  '.$pdf->encodeText($label), 1, 0, 'L');
            $pdf->SetFont('Arial', '', 8.8);
            $pdf->Cell($cols[1], 8.2, '  '.$pdf->encodeText(Str::limit($value, 72)), 1, 1, 'L');
        }

        $pdf->officeUseBox(
            'Claimant Signature / Date:',
            (string) ($recovery->received_by_name ?? '').'  ('.($recovery->turnover_at?->format('M d, Y') ?? now()->format('M d, Y')).')',
            'Released By / Staff Initials:',
            (string) ($recovery->confirmer?->name ?? $recovery->processor?->name ?? '')
        );

        return $pdf->Output('S');
    }

    public function buildReferenceSlip(ItemReport $report, string $lookupUrl): string
    {
        $pdf = new OfficialDocumentPdf('P', 'mm', 'A4');
        $pdf->AliasNbPages();
        $pdf->SetMargins(15, 14, 15);
        $pdf->SetAutoPageBreak(true, 16);
        $pdf->documentTitle = 'OFFICIAL REFERENCE & TRACKING SLIP';
        $pdf->issuingOffice = (string) ($report->custodyOrganization?->name
            ?? $report->handlingOrganization?->name
            ?? 'Boracay Lost and Found Desk');

        $pdf->AddPage();
        $pageW = $pdf->GetPageWidth() - 30;

        $pdf->SetFont('Arial', '', 8.5);
        $pdf->SetTextColor(25, 25, 25);
        $pdf->MultiCell(
            $pageW,
            4.6,
            $pdf->encodeText('Present this slip together with a valid government-issued ID when claiming an item or confirming turnover at the designated Boracay Police Booth or Police Station.')
        );
        $pdf->Ln(3);

        $custodyStageLabel = $report->effectiveCustodyStage()?->label() ?? ucfirst(str_replace('_', ' ', $report->item_status->value));
        $locationLabel = implode(', ', array_filter([$report->location?->name, $report->location_details])) ?: 'Boracay Island';

        $pdf->underlinedMetaRow([
            ['label' => 'Reference No.:', 'value' => (string) $report->reference_no, 'width' => $pageW * 0.45],
            ['label' => 'Type:', 'value' => strtoupper($report->type->value), 'width' => $pageW * 0.23],
            ['label' => 'Date:', 'value' => (string) ($report->occurred_date?->format('M d, Y') ?? now()->format('M d, Y')), 'width' => $pageW * 0.32],
        ]);
        $pdf->underlinedMetaRow([
            ['label' => 'Custody Desk:', 'value' => (string) ($report->custodyOrganization?->name ?? $report->handlingOrganization?->name ?? 'Boracay L&F Desk'), 'width' => $pageW * 0.62],
            ['label' => 'Issued:', 'value' => now()->format('M d, Y'), 'width' => $pageW * 0.38],
        ]);

        $pdf->thickDivider();

        $topY = $pdf->GetY();
        $qrSize = 36.0;
        $tableW = $pageW - $qrSize - 6.0;
        $cols = [$tableW * 0.36, $tableW * 0.64];

        $this->drawBlackHeader($pdf, ['Field', 'Recorded Details'], $cols, ['L', 'L']);

        $details = [
            ['Reference No.', (string) $report->reference_no],
            ['Item Name', (string) $report->item_name],
            ['Category', (string) ($report->category?->label() ?? '-')],
            ['Location / Area', $locationLabel],
            ['Verification', ucfirst(str_replace('_', ' ', $report->verification_status->value))],
            ['Item Status', ucfirst(str_replace('_', ' ', $report->item_status->value))],
            ['Custody Location', $report->booth_location ? $custodyStageLabel.' ('.$report->booth_location.')' : $custodyStageLabel],
        ];

        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        foreach ($details as [$label, $value]) {
            $pdf->SetFont('Arial', 'B', 8.3);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell($cols[0], 7.8, '  '.$pdf->encodeText($label), 1, 0, 'L');
            $pdf->SetFont('Arial', '', 8.4);
            $pdf->Cell($cols[1], 7.8, '  '.$pdf->encodeText(Str::limit($value, 46)), 1, 1, 'L');
        }

        $afterTableY = $pdf->GetY();

        $qrX = 15 + $tableW + 6.0;
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.25);
        $pdf->Rect($qrX, $topY, $qrSize, $qrSize + 8.0);

        $qrTempFile = $this->fetchQrTempJpeg($lookupUrl);
        if ($qrTempFile !== null) {
            try {
                $pdf->Image($qrTempFile, $qrX + 2.0, $topY + 2.0, $qrSize - 4.0, $qrSize - 4.0, 'JPG');
            } finally {
                @unlink($qrTempFile);
            }
        } else {
            $pdf->SetXY($qrX + 2.0, $topY + 12.0);
            $pdf->SetFont('Arial', 'B', 7.5);
            $pdf->MultiCell($qrSize - 4.0, 4.0, "QR CODE\n".$pdf->encodeText($report->reference_no), 0, 'C');
        }

        $pdf->SetXY($qrX, $topY + $qrSize + 1.0);
        $pdf->SetFont('Arial', '', 7.2);
        $pdf->SetTextColor(40, 40, 40);
        $pdf->Cell($qrSize, 5.5, 'Scan to track status', 0, 0, 'C');

        $pdf->SetY(max($afterTableY, $topY + $qrSize + 10.0) + 4.0);

        $itemPhotoTemp = $this->prepareReportPhotoJpeg($report);
        if ($itemPhotoTemp !== null) {
            try {
                $photoSectionY = $pdf->GetY();
                $pdf->SetFont('Arial', 'B', 8.3);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->Cell($pageW, 5.5, 'UPLOADED ITEM PHOTOGRAPH:', 0, 1, 'L');
                $boxY = $pdf->GetY() + 1.0;
                $boxW = 68.0;
                $boxH = 48.0;
                $pdf->SetDrawColor(0, 0, 0);
                $pdf->SetLineWidth(0.25);
                $pdf->Rect(15, $boxY, $boxW, $boxH);
                $pdf->Image($itemPhotoTemp, 16.5, $boxY + 1.5, $boxW - 3.0, $boxH - 3.0, 'JPG');
                $pdf->SetY($boxY + $boxH + 4.0);
            } finally {
                @unlink($itemPhotoTemp);
            }
        }

        $pdf->SetFont('Arial', '', 7.6);
        $pdf->SetTextColor(60, 60, 60);
        $pdf->Cell($pageW, 4.5, $pdf->encodeText('Tracking Link: '.$lookupUrl), 0, 1, 'L');

        $pdf->officeUseBox(
            'Date Presented / Verified:',
            '',
            'Verified By / Booth Officer Initials:',
            ''
        );

        return $pdf->Output('S');
    }

    private function prepareReportPhotoJpeg(ItemReport $report): ?string
    {
        $report->loadMissing('images');
        $photoPath = app(FacebookPosterService::class)->resolveItemPhotoPath($report, allowPrivateImage: true);
        if ($photoPath === null || ! is_file($photoPath)) {
            return null;
        }

        $bytes = @file_get_contents($photoPath);
        if ($bytes === false) {
            return null;
        }

        $src = @imagecreatefromstring($bytes);
        if (! $src instanceof \GdImage) {
            return null;
        }

        $dstW = 680;
        $dstH = 480;
        $dst = imagecreatetruecolor($dstW, $dstH);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $dstW, $dstH, $white);

        $srcW = imagesx($src);
        $srcH = imagesy($src);
        if ($srcW > 0 && $srcH > 0) {
            $scale = min($dstW / $srcW, $dstH / $srcH);
            $newW = max(1, (int) round($srcW * $scale));
            $newH = max(1, (int) round($srcH * $scale));
            $offsetX = (int) floor(($dstW - $newW) / 2);
            $offsetY = (int) floor(($dstH - $newH) / 2);
            imagecopyresampled($dst, $src, $offsetX, $offsetY, 0, 0, $newW, $newH, $srcW, $srcH);
        }

        imagedestroy($src);

        $tmp = tempnam(sys_get_temp_dir(), 'blf_item_');
        if ($tmp === false) {
            imagedestroy($dst);

            return null;
        }

        $jpgPath = $tmp.'.jpg';
        @rename($tmp, $jpgPath);
        imagejpeg($dst, $jpgPath, 88);
        imagedestroy($dst);

        return $jpgPath;
    }

    private function fetchQrTempJpeg(string $lookupUrl): ?string
    {
        if (app()->runningUnitTests()) {
            return null;
        }

        try {
            $response = Http::timeout(4)->get('https://api.qrserver.com/v1/create-qr-code/', [
                'size' => '240x240',
                'margin' => 6,
                'format' => 'jpg',
                'data' => $lookupUrl,
            ]);

            if (! $response->successful() || strlen($response->body()) < 100) {
                return null;
            }

            $tmp = tempnam(sys_get_temp_dir(), 'blf_qr_');
            if ($tmp === false) {
                return null;
            }

            $jpgPath = $tmp.'.jpg';
            @rename($tmp, $jpgPath);
            file_put_contents($jpgPath, $response->body());

            return $jpgPath;
        } catch (Throwable) {
            return null;
        }
    }

    public function buildAnalyticsReport(
        User $user,
        array $filters,
        array $summary,
        array $barangayBreakdown,
        array $institutionBreakdown,
        Collection $rows,
    ): string {
        $pdf = new OfficialDocumentPdf('L', 'mm', 'A4');
        $pdf->AliasNbPages();
        $pdf->SetMargins(14, 12, 14);
        $pdf->SetAutoPageBreak(true, 16);
        $pdf->documentTitle = 'LOST & FOUND INVENTORY & ANALYTICS LIST';
        $pdf->issuingOffice = $user->isPlatformSuperAdmin()
            ? 'Boracay Island Lost & Found — Island-Wide Console'
            : (string) (Organization::query()->whereKey($user->organizationId())->value('name') ?? 'Institutional Desk');

        $pdf->AddPage();
        $pageW = $pdf->GetPageWidth() - 28;

        $period = (! empty($filters['date_from']) || ! empty($filters['date_to']))
            ? trim(($filters['date_from'] ?? 'Start').' to '.($filters['date_to'] ?? 'Present'))
            : 'All Recorded Dates';

        $pdf->SetFont('Arial', '', 8.5);
        $pdf->SetTextColor(25, 25, 25);
        $pdf->MultiCell(
            $pageW,
            4.5,
            $pdf->encodeText('Official inventory and recovery summary generated from the Boracay Island Lost and Found System. Unclaimed found items held past the 180-day retention period are queued for administrative disposal review.')
        );
        $pdf->Ln(2.5);

        $pdf->underlinedMetaRow([
            ['label' => 'Department / Office:', 'value' => $pdf->issuingOffice, 'width' => $pageW * 0.50],
            ['label' => 'Prepared By:', 'value' => $user->name, 'width' => $pageW * 0.28],
            ['label' => 'Date:', 'value' => now()->format('M d, Y'), 'width' => $pageW * 0.22],
        ]);
        $pdf->underlinedMetaRow([
            ['label' => 'Period:', 'value' => $period, 'width' => $pageW * 0.32],
            ['label' => 'Lost / Found:', 'value' => number_format((int) ($summary['total_lost'] ?? 0)).' Lost  /  '.number_format((int) ($summary['total_found'] ?? 0)).' Found', 'width' => $pageW * 0.36],
            ['label' => 'Recovered:', 'value' => number_format((int) ($summary['recovered_items'] ?? 0)).' ('.((string) ($summary['recovery_rate'] ?? 0)).'%)', 'width' => $pageW * 0.32],
        ]);

        $pdf->thickDivider();

        $rw = [34, 16, 26, 36, 54, 34, 24, $pageW - 224];
        $headers = ['Reference No.:', 'Type:', 'Date Occurred:', 'Location / Area:', 'Item Description:', 'Category:', 'Status:', 'Custody Institution:'];
        $aligns = ['L', 'C', 'C', 'L', 'L', 'L', 'C', 'L'];
        $this->drawBlackHeader($pdf, $headers, $rw, $aligns);

        $pdf->SetFont('Arial', '', 8);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        $pdf->SetTextColor(0, 0, 0);

        if ($rows->isEmpty()) {
            $pdf->Cell($pageW, 10, 'No matching records for the selected filters.', 1, 1, 'C');
        } else {
            foreach ($rows as $row) {
                if ($pdf->GetY() > $pdf->GetPageHeight() - 24) {
                    $pdf->AddPage();
                    $this->drawBlackHeader($pdf, $headers, $rw, $aligns);
                    $pdf->SetFont('Arial', '', 8);
                    $pdf->SetDrawColor(0, 0, 0);
                    $pdf->SetTextColor(0, 0, 0);
                }

                $pdf->Cell($rw[0], 7.2, '  '.$pdf->encodeText($row->reference_no), 1, 0, 'L');
                $pdf->Cell($rw[1], 7.2, strtoupper($row->type->value), 1, 0, 'C');
                $pdf->Cell($rw[2], 7.2, $row->occurred_date?->format('M d, Y') ?? '-', 1, 0, 'C');
                $pdf->Cell($rw[3], 7.2, '  '.$pdf->encodeText(Str::limit((string) ($row->location?->name ?? $row->location_details ?? '-'), 18)), 1, 0, 'L');
                $pdf->Cell($rw[4], 7.2, '  '.$pdf->encodeText(Str::limit($row->item_name, 30)), 1, 0, 'L');
                $pdf->Cell($rw[5], 7.2, '  '.$pdf->encodeText(Str::limit((string) ($row->category?->label() ?? '-'), 18)), 1, 0, 'L');
                $pdf->Cell($rw[6], 7.2, ucfirst(str_replace('_', ' ', $row->item_status->value)), 1, 0, 'C');
                $pdf->Cell($rw[7], 7.2, '  '.$pdf->encodeText(Str::limit((string) ($row->custodyOrganization?->name ?? $row->handlingOrganization?->name ?? '-'), 24)), 1, 1, 'L');
            }
        }

        $pdf->officeUseBox(
            'Date Verified:',
            now()->format('M d, Y'),
            'Received By / Staff Initials:',
            $user->name
        );

        return $pdf->Output('S');
    }

    protected function drawBlackHeader(OfficialDocumentPdf $pdf, array $headers, array $widths, array $aligns): void
    {
        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFillColor(0, 0, 0);
        $pdf->SetDrawColor(0, 0, 0);

        foreach ($headers as $i => $text) {
            $pad = $aligns[$i] === 'R' ? $text.'  ' : ($aligns[$i] === 'C' ? $text : '  '.$text);
            $pdf->Cell($widths[$i], 7.5, $pdf->encodeText($pad), 1, $i === count($headers) - 1 ? 1 : 0, $aligns[$i], true);
        }
    }
}

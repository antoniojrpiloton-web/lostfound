<?php

namespace App\Services\Reports;

use FPDF;

class OfficialDocumentPdf extends FPDF
{
    public string $documentTitle = 'LOST & FOUND INVENTORY REPORT';

    public string $issuingOffice = 'BORACAY ISLAND LOST AND FOUND SYSTEM';

    public function encodeText(?string $text): string
    {
        $clean = trim((string) $text);
        if ($clean === '') {
            return '';
        }

        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $clean);

        return $converted !== false ? $converted : $clean;
    }

    public function Header(): void
    {
        $pageWidth = $this->GetPageWidth() - $this->lMargin - $this->rMargin;
        $logoPath = public_path('images/boracay-lf-logo-pdf.jpg');
        $topY = $this->GetY();

        if (is_file($logoPath)) {
            $this->Image($logoPath, $this->lMargin, $topY - 2, 16, 16);
        }

        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', 'B', 15);
        $this->Cell($pageWidth, 7, strtoupper($this->encodeText($this->documentTitle)), 0, 1, 'R');

        $this->SetFont('Arial', '', 9.5);
        $this->SetTextColor(35, 35, 35);
        $this->Cell($pageWidth, 5, strtoupper($this->encodeText($this->issuingOffice)), 0, 1, 'R');

        $y = max($this->GetY() + 2, $topY + 15);
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.9);
        $this->Line($this->lMargin, $y, $this->GetPageWidth() - $this->rMargin, $y);
        $this->SetLineWidth(0.2);

        $this->SetY($y + 4);
    }

    public function Footer(): void
    {
        $this->SetY(-12);
        $pageWidth = $this->GetPageWidth() - $this->lMargin - $this->rMargin;
        $this->SetFont('Arial', '', 7.5);
        $this->SetTextColor(90, 90, 90);
        $this->Cell($pageWidth * 0.75, 4, 'Boracay Island Lost & Found System  |  Municipality of Malay, Aklan  |  RA 10173 Compliant', 0, 0, 'L');
        $this->Cell($pageWidth * 0.25, 4, 'Page '.$this->PageNo().' of {nb}', 0, 0, 'R');
    }

    public function thickDivider(): void
    {
        $y = $this->GetY() + 2;
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.9);
        $this->Line($this->lMargin, $y, $this->GetPageWidth() - $this->rMargin, $y);
        $this->SetLineWidth(0.2);
        $this->SetY($y + 4);
    }

    public function underlinedMetaRow(array $fields): void
    {
        $y = $this->GetY();
        $x = $this->lMargin;
        $this->SetTextColor(0, 0, 0);
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.25);

        foreach ($fields as $field) {
            $label = $this->encodeText($field['label']).' ';
            $value = $this->encodeText($field['value']);
            $w = $field['width'];

            $this->SetXY($x, $y);
            $this->SetFont('Arial', '', 9);
            $labelW = $this->GetStringWidth($label) + 1;
            $this->Cell($labelW, 6, $label, 0, 0, 'L');

            $valW = max(12, $w - $labelW - 4);
            $this->SetFont('Arial', 'B', 8.8);
            while ($value !== '' && $this->GetStringWidth($value) > ($valW - 1)) {
                $value = substr($value, 0, -1);
            }
            $this->Cell($valW, 6, $value, 'B', 0, 'L');

            $x += $w;
        }

        $this->SetY($y + 8.5);
    }

    public function officeUseBox(string $leftLabel, string $leftValue, string $rightLabel, string $rightValue): void
    {
        $pageWidth = $this->GetPageWidth() - $this->lMargin - $this->rMargin;
        $half = $pageWidth / 2;

        if ($this->GetY() > $this->GetPageHeight() - 36) {
            $this->AddPage();
        }

        $this->Ln(5);
        $this->SetFillColor(0, 0, 0);
        $this->SetDrawColor(0, 0, 0);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell($pageWidth, 6.5, '  For Office Use Only:', 1, 1, 'L', true);

        $x = $this->GetX();
        $y = $this->GetY();
        $boxH = 16;

        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.25);
        $this->Rect($x, $y, $half, $boxH);
        $this->Rect($x + $half, $y, $half, $boxH);

        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 8.5);
        $this->SetXY($x + 2.5, $y + 2.5);
        $this->Cell($half - 5, 4.5, $this->encodeText($leftValue), 0, 1, 'L');
        $this->SetFont('Arial', 'B', 8.5);
        $this->SetXY($x + 2.5, $y + $boxH - 5.5);
        $this->Cell($half - 5, 4.5, $this->encodeText($leftLabel), 0, 0, 'L');

        $this->SetFont('Arial', '', 8.5);
        $this->SetXY($x + $half + 2.5, $y + 2.5);
        $this->Cell($half - 5, 4.5, $this->encodeText($rightValue), 0, 1, 'L');
        $this->SetFont('Arial', 'B', 8.5);
        $this->SetXY($x + $half + 2.5, $y + $boxH - 5.5);
        $this->Cell($half - 5, 4.5, $this->encodeText($rightLabel), 0, 0, 'L');

        $this->SetY($y + $boxH + 2);
    }
}

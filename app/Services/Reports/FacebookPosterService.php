<?php

namespace App\Services\Reports;

use App\Enums\ImageValidationStatus;
use App\Enums\ReportType;
use App\Models\ItemImage;
use App\Models\ItemReport;
use GdImage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class FacebookPosterService
{
    private const WIDTH = 1200;

    private const HEIGHT = 1200;

    public function buildPosterPng(ItemReport $report, bool $allowPrivateImage = false, ?string $lookupUrl = null): string
    {
        $report->loadMissing(['category', 'location', 'handlingOrganization', 'custodyOrganization', 'images']);

        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        $isFound = $report->type === ReportType::Found;

        $canvasBg = imagecolorallocate($canvas, 245, 247, 250);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $border = imagecolorallocate($canvas, 226, 232, 240);
        $shadow = imagecolorallocate($canvas, 229, 234, 242);
        $inkDark = imagecolorallocate($canvas, 15, 23, 42);
        $inkBody = imagecolorallocate($canvas, 51, 65, 85);
        $inkMuted = imagecolorallocate($canvas, 100, 116, 139);

        $accent = $isFound
            ? imagecolorallocate($canvas, 29, 78, 216)
            : imagecolorallocate($canvas, 220, 38, 38);
        $accentSoft = $isFound
            ? imagecolorallocate($canvas, 239, 246, 255)
            : imagecolorallocate($canvas, 254, 242, 242);
        $ctaBg = $isFound
            ? imagecolorallocate($canvas, 15, 41, 74)
            : imagecolorallocate($canvas, 127, 29, 29);
        $ctaSub = $isFound
            ? imagecolorallocate($canvas, 203, 213, 225)
            : imagecolorallocate($canvas, 254, 202, 202);

        $fontRegular = $this->resolveFontPath(false);
        $fontBold = $this->resolveFontPath(true);

        imagefilledrectangle($canvas, 0, 0, self::WIDTH, self::HEIGHT, $canvasBg);

        imagefilledrectangle($canvas, 0, 0, self::WIDTH, 10, $accent);
        imagefilledrectangle($canvas, 0, 10, self::WIDTH, 140, $white);
        imageline($canvas, 0, 140, self::WIDTH, 140, $border);

        $logoPng = public_path('images/boracay-lf-logo.png');
        $logoJpg = public_path('images/boracay-lf-logo-pdf.jpg');
        $logoImg = is_file($logoPng)
            ? @imagecreatefrompng($logoPng)
            : (is_file($logoJpg) ? @imagecreatefromjpeg($logoJpg) : false);

        if ($logoImg instanceof GdImage) {
            imagealphablending($logoImg, true);
            imagecopyresampled($canvas, $logoImg, 54, 24, 0, 0, 96, 96, imagesx($logoImg), imagesy($logoImg));
            imagedestroy($logoImg);
        }

        $this->drawText($canvas, 24, 168, 68, $inkDark, $fontBold, 'BORACAY LOST & FOUND');
        $this->drawText($canvas, 13, 170, 98, $inkMuted, $fontBold, 'OFFICIAL PUBLIC ADVISORY  •  PNP BORACAY & TOURIST DESK');

        $pillText = $isFound ? 'FOUND ITEM NOTICE' : 'LOST ITEM ALERT';
        $this->drawRoundedRect($canvas, 848, 46, 1146, 104, 10, $accent);
        $this->drawCenteredText($canvas, 16, 848, 1146, 83, $white, $fontBold, $pillText);

        $photoCardX = 54;
        $photoCardY = 172;
        $photoCardW = 580;
        $photoCardH = 548;
        $this->drawRoundedRect($canvas, $photoCardX + 4, $photoCardY + 6, $photoCardX + $photoCardW + 4, $photoCardY + $photoCardH + 6, 16, $shadow);
        $this->drawRoundedRect($canvas, $photoCardX, $photoCardY, $photoCardX + $photoCardW, $photoCardY + $photoCardH, 16, $white, $border);

        $imgPad = 14;
        $imgX = $photoCardX + $imgPad;
        $imgY = $photoCardY + $imgPad;
        $imgW = $photoCardW - ($imgPad * 2);
        $imgH = $photoCardH - 74;

        imagefilledrectangle($canvas, $imgX, $imgY, $imgX + $imgW, $imgY + $imgH, $canvasBg);

        $photoPath = $this->resolveItemPhotoPath($report, $allowPrivateImage);
        if ($photoPath !== null) {
            $itemImg = $this->loadGdImage($photoPath);
            if ($itemImg instanceof GdImage) {
                $this->copyCoverCropped($canvas, $itemImg, $imgX, $imgY, $imgW, $imgH);
                imagedestroy($itemImg);
            }
        } else {
            $this->drawCenteredText($canvas, 18, $imgX, $imgX + $imgW, $imgY + (int) ($imgH / 2), $inkMuted, $fontRegular, 'No Item Photograph Attached');
        }
        imagerectangle($canvas, $imgX, $imgY, $imgX + $imgW, $imgY + $imgH, $border);

        $stripY = $imgY + $imgH + 38;
        $this->drawText($canvas, 13, $imgX, $stripY - 12, $inkMuted, $fontBold, 'REFERENCE NO.');
        $this->drawText($canvas, 17, $imgX + 148, $stripY - 10, $inkDark, $fontBold, (string) $report->reference_no);

        $statusChip = strtoupper($report->publicStatusLabel());
        $this->drawRoundedRect($canvas, $imgX + $imgW - 140, $imgY + $imgH + 12, $imgX + $imgW, $imgY + $imgH + 46, 8, $accentSoft);
        $this->drawCenteredText($canvas, 12, $imgX + $imgW - 140, $imgX + $imgW, $imgY + $imgH + 35, $accent, $fontBold, $statusChip);

        $infoX = 662;
        $infoY = 172;
        $infoW = 484;
        $infoH = 548;
        $this->drawRoundedRect($canvas, $infoX + 4, $infoY + 6, $infoX + $infoW + 4, $infoY + $infoH + 6, 16, $shadow);
        $this->drawRoundedRect($canvas, $infoX, $infoY, $infoX + $infoW, $infoY + $infoH, 16, $white, $border);

        $padX = $infoX + 28;
        $maxTextW = $infoW - 56;
        $curY = $infoY + 36;

        $catText = strtoupper((string) ($report->category?->label() ?? 'GENERAL ITEM'));
        $catPillW = min($maxTextW, max(140, strlen($catText) * 11 + 32));
        $this->drawRoundedRect($canvas, $padX, $curY - 14, $padX + $catPillW, $curY + 20, 8, $accentSoft);
        $this->drawCenteredText($canvas, 12, $padX, $padX + $catPillW, $curY + 9, $accent, $fontBold, Str::limit($catText, 26));
        $curY += 54;

        $itemLines = $this->wrapText((string) $report->item_name, 23, $fontBold, $maxTextW, 3);
        foreach ($itemLines as $line) {
            $this->drawText($canvas, 23, $padX, $curY, $inkDark, $fontBold, $line);
            $curY += 34;
        }

        imagefilledrectangle($canvas, $padX, $curY - 14, $padX + 64, $curY - 9, $accent);
        $curY += 20;

        $dateText = $report->occurred_date?->format('F d, Y') ?? now()->format('F d, Y');
        $locationLabel = implode(', ', array_filter([$report->location?->name, $report->location_details])) ?: 'Boracay Island';
        $custodyDesk = (string) ($report->custodyOrganization?->name ?? $report->handlingOrganization?->name ?? 'Boracay Police Desk');
        $stageLabel = $report->effectiveCustodyStage()?->label();
        $custodyValue = $stageLabel
            ? $stageLabel.($report->booth_location ? ' — '.$report->booth_location : '')
            : $custodyDesk;

        $metaBlocks = [
            [$isFound ? 'DATE FOUND' : 'DATE LOST', $dateText, 1],
            [$isFound ? 'WHERE FOUND' : 'WHERE LOST / LAST SEEN', $locationLabel, 2],
            [$isFound ? 'CURRENT CUSTODY' : 'HANDLING DESK', $isFound ? $custodyValue : $custodyDesk, 2],
        ];

        if ($isFound) {
            $metaBlocks[] = ['POLICE / CUSTODY DESK', $custodyDesk, 2];
        } elseif ($report->brand || $report->color) {
            $metaBlocks[] = ['BRAND / COLOR', implode(' · ', array_filter([$report->brand, $report->color])), 1];
        }

        foreach ($metaBlocks as [$label, $value, $maxLines]) {
            if ($curY > $infoY + $infoH - 42) {
                break;
            }
            imagefilledrectangle($canvas, $padX, $curY - 11, $padX + 4, $curY + 2, $accent);
            $this->drawText($canvas, 11, $padX + 12, $curY, $inkMuted, $fontBold, $label);
            $curY += 24;

            $wrappedVal = $this->wrapText($value, 15, $fontBold, $maxTextW - 12, $maxLines);
            foreach ($wrappedVal as $vLine) {
                $this->drawText($canvas, 15, $padX + 12, $curY, $inkDark, $fontBold, $vLine);
                $curY += 22;
            }
            $curY += 12;
        }

        $descX = 54;
        $descY = 744;
        $descW = 1092;
        $descH = 140;
        $this->drawRoundedRect($canvas, $descX, $descY, $descX + $descW, $descY + $descH, 14, $white, $border);
        imagefilledrectangle($canvas, $descX, $descY + 16, $descX + 6, $descY + $descH - 16, $accent);

        $this->drawText($canvas, 12, $descX + 26, $descY + 34, $inkMuted, $fontBold, 'ITEM DESCRIPTION & DETAILS');
        $cleanDesc = trim(preg_replace('/\s+/', ' ', (string) $report->description) ?? '');
        $descLines = $this->wrapText($cleanDesc !== '' ? $cleanDesc : 'Verified item record under the Boracay Lost and Found System.', 15, $fontRegular, $descW - 52, 3);
        $dy = $descY + 64;
        foreach ($descLines as $dLine) {
            $this->drawText($canvas, 15, $descX + 26, $dy, $inkBody, $fontRegular, $dLine);
            $dy += 25;
        }

        $ctaX = 54;
        $ctaY = 908;
        $ctaW = 1092;
        $ctaH = 212;
        $this->drawRoundedRect($canvas, $ctaX, $ctaY, $ctaX + $ctaW, $ctaY + $ctaH, 16, $ctaBg);

        $ctaHeading = $isFound ? 'IS THIS YOUR ITEM?' : 'HAVE YOU SEEN THIS ITEM?';
        $ctaBody1 = $isFound
            ? 'Claim online via the Boracay Lost & Found Portal or visit the'
            : 'Please turn it in at the nearest Boracay Police Beach Booth or';
        $ctaBody2 = $isFound
            ? 'Police Station / designated Police Booth with a valid government ID.'
            : 'Police Station, or report a match online via the QR code.';

        $this->drawText($canvas, 24, $ctaX + 36, $ctaY + 62, $white, $fontBold, $ctaHeading);
        $this->drawText($canvas, 15, $ctaX + 36, $ctaY + 102, $ctaSub, $fontRegular, $ctaBody1);
        $this->drawText($canvas, 15, $ctaX + 36, $ctaY + 128, $ctaSub, $fontRegular, $ctaBody2);

        $portalText = 'Ref: '.$report->reference_no.'  •  Scan QR to view verified status';
        $this->drawText($canvas, 13, $ctaX + 36, $ctaY + 174, $white, $fontBold, $portalText);

        $qrBoxSize = 154;
        $qrBoxX = $ctaX + $ctaW - $qrBoxSize - 32;
        $qrBoxY = $ctaY + 20;
        $this->drawRoundedRect($canvas, $qrBoxX, $qrBoxY, $qrBoxX + $qrBoxSize, $qrBoxY + $qrBoxSize, 12, $white);

        $effectiveUrl = $lookupUrl ?? route('public.items.show', $report->reference_no);
        $qrImg = $this->fetchQrGdImage($effectiveUrl);
        if ($qrImg instanceof GdImage) {
            imagecopyresampled($canvas, $qrImg, $qrBoxX + 10, $qrBoxY + 10, 0, 0, $qrBoxSize - 20, $qrBoxSize - 20, imagesx($qrImg), imagesy($qrImg));
            imagedestroy($qrImg);
        } else {
            $this->drawCenteredText($canvas, 13, $qrBoxX, $qrBoxX + $qrBoxSize, $qrBoxY + 82, $inkDark, $fontBold, 'SCAN QR');
        }
        $this->drawCenteredText($canvas, 11, $qrBoxX - 10, $qrBoxX + $qrBoxSize + 10, $ctaY + 196, $ctaSub, $fontBold, 'SCAN TO TRACK');

        $this->drawCenteredText(
            $canvas,
            12,
            40,
            1160,
            1164,
            $inkMuted,
            $fontRegular,
            'Boracay Island Lost & Found System  •  Personal contact info protected under RA 10173 (Data Privacy Act of 2012)'
        );

        ob_start();
        imagepng($canvas, null, 6);
        $pngBytes = (string) ob_get_clean();
        imagedestroy($canvas);

        return $pngBytes;
    }

    public function buildCaption(ItemReport $report, string $shareUrl): string
    {
        $report->loadMissing(['category', 'location', 'handlingOrganization', 'custodyOrganization']);

        $isFound = $report->type === ReportType::Found;
        $header = $isFound ? 'FOUND ITEM NOTICE — BORACAY ISLAND' : 'LOST ITEM ALERT — BORACAY ISLAND';
        $location = implode(', ', array_filter([$report->location?->name, $report->location_details])) ?: 'Boracay Island';
        $date = $report->occurred_date?->format('F d, Y') ?? now()->format('F d, Y');
        $desk = (string) ($report->custodyOrganization?->name ?? $report->handlingOrganization?->name ?? 'Boracay Police Station');
        $stage = $report->effectiveCustodyStage()?->label();

        $lines = [
            $header,
            '',
            'Item: '.$report->item_name,
            'Reference No.: '.$report->reference_no,
            'Category: '.($report->category?->label() ?? 'General'),
            ($isFound ? 'Where Found: ' : 'Where Lost / Last Remembered: ').$location,
            ($isFound ? 'Date Found: ' : 'Date Lost: ').$date,
        ];

        if ($isFound && $stage) {
            $lines[] = 'Custody Status: '.$stage.($report->booth_location ? ' ('.$report->booth_location.')' : '');
        }

        $lines[] = 'Handling Desk: '.$desk;
        $lines[] = '';
        $lines[] = $isFound
            ? 'Kung ikaw ang may-ari, mag-file ng claim sa link sa ibaba o pumunta sa Police Station / designated Police Booth dala ang valid ID.'
            : 'Kung nakita mo ang item na ito, pakisuli sa pinakamalapit na Boracay Police Beach Booth o Police Station, o mag-report sa link sa ibaba.';
        $lines[] = 'Track / Claim Link: '.$shareUrl;
        $lines[] = '';
        $lines[] = '#BoracayLostAndFound #BoracayIsland #PNPBoracay #LostAndFound';

        return implode("\n", $lines);
    }

    public function resolveItemPhotoPath(ItemReport $report, bool $allowPrivateImage = false): ?string
    {
        $images = $report->images->sortByDesc('is_primary');

        foreach ($images as $image) {
            if (! $image instanceof ItemImage) {
                continue;
            }

            $isPubliclyApproved = in_array($image->validation_status, [ImageValidationStatus::Passed, ImageValidationStatus::Overridden], true);
            if ($image->public_path !== null && $isPubliclyApproved && Storage::disk('public')->exists($image->public_path)) {
                return Storage::disk('public')->path($image->public_path);
            }

            if ($allowPrivateImage && $image->private_path !== null && Storage::disk('local')->exists($image->private_path)) {
                return Storage::disk('local')->path($image->private_path);
            }
        }

        return null;
    }

    private function loadGdImage(string $path): ?GdImage
    {
        $bytes = @file_get_contents($path);
        if ($bytes === false) {
            return null;
        }

        $img = @imagecreatefromstring($bytes);

        return $img instanceof GdImage ? $img : null;
    }

    private function fetchQrGdImage(string $lookupUrl): ?GdImage
    {
        if (app()->runningUnitTests()) {
            return null;
        }

        try {
            $response = Http::timeout(4)->get('https://api.qrserver.com/v1/create-qr-code/', [
                'size' => '220x220',
                'margin' => 4,
                'format' => 'png',
                'data' => $lookupUrl,
            ]);

            if (! $response->successful() || strlen($response->body()) < 100) {
                return null;
            }

            $img = @imagecreatefromstring($response->body());

            return $img instanceof GdImage ? $img : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function copyCoverCropped(GdImage $dst, GdImage $src, int $dstX, int $dstY, int $dstW, int $dstH): void
    {
        $srcW = imagesx($src);
        $srcH = imagesy($src);
        if ($srcW <= 0 || $srcH <= 0) {
            return;
        }

        $srcRatio = $srcW / $srcH;
        $dstRatio = $dstW / $dstH;

        if ($srcRatio > $dstRatio) {
            $cropH = $srcH;
            $cropW = (int) round($srcH * $dstRatio);
            $cropX = (int) floor(($srcW - $cropW) / 2);
            $cropY = 0;
        } else {
            $cropW = $srcW;
            $cropH = (int) round($srcW / $dstRatio);
            $cropX = 0;
            $cropY = (int) floor(($srcH - $cropH) / 2);
        }

        imagecopyresampled($dst, $src, $dstX, $dstY, $cropX, $cropY, $dstW, $dstH, max(1, $cropW), max(1, $cropH));
    }

    private function drawRoundedRect(GdImage $canvas, int $x1, int $y1, int $x2, int $y2, int $r, int $fillColor, ?int $borderColor = null): void
    {
        imagefilledrectangle($canvas, $x1 + $r, $y1, $x2 - $r, $y2, $fillColor);
        imagefilledrectangle($canvas, $x1, $y1 + $r, $x2, $y2 - $r, $fillColor);
        imagefilledellipse($canvas, $x1 + $r, $y1 + $r, $r * 2, $r * 2, $fillColor);
        imagefilledellipse($canvas, $x2 - $r, $y1 + $r, $r * 2, $r * 2, $fillColor);
        imagefilledellipse($canvas, $x1 + $r, $y2 - $r, $r * 2, $r * 2, $fillColor);
        imagefilledellipse($canvas, $x2 - $r, $y2 - $r, $r * 2, $r * 2, $fillColor);

        if ($borderColor !== null) {
            imageline($canvas, $x1 + $r, $y1, $x2 - $r, $y1, $borderColor);
            imageline($canvas, $x1 + $r, $y2, $x2 - $r, $y2, $borderColor);
            imageline($canvas, $x1, $y1 + $r, $x1, $y2 - $r, $borderColor);
            imageline($canvas, $x2, $y1 + $r, $x2, $y2 - $r, $borderColor);
            imagearc($canvas, $x1 + $r, $y1 + $r, $r * 2, $r * 2, 180, 270, $borderColor);
            imagearc($canvas, $x2 - $r, $y1 + $r, $r * 2, $r * 2, 270, 360, $borderColor);
            imagearc($canvas, $x1 + $r, $y2 - $r, $r * 2, $r * 2, 90, 180, $borderColor);
            imagearc($canvas, $x2 - $r, $y2 - $r, $r * 2, $r * 2, 0, 90, $borderColor);
        }
    }

    private function wrapText(string $text, float $size, ?string $fontPath, int $maxWidth, int $maxLines): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        if ($words === []) {
            return [];
        }

        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;
            if ($this->measureTextWidth($candidate, $size, $fontPath) <= $maxWidth) {
                $current = $candidate;

                continue;
            }

            if ($current !== '') {
                $lines[] = $current;
                if (count($lines) >= $maxLines) {
                    $lines[$maxLines - 1] = $this->truncateToWidth($lines[$maxLines - 1].'...', $size, $fontPath, $maxWidth);

                    return $lines;
                }
            }

            $current = $this->truncateToWidth($word, $size, $fontPath, $maxWidth);
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return array_slice($lines, 0, $maxLines);
    }

    private function truncateToWidth(string $text, float $size, ?string $fontPath, int $maxWidth): string
    {
        if ($this->measureTextWidth($text, $size, $fontPath) <= $maxWidth) {
            return $text;
        }

        $trimmed = rtrim($text, '.');
        while (strlen($trimmed) > 3 && $this->measureTextWidth($trimmed.'...', $size, $fontPath) > $maxWidth) {
            $trimmed = substr($trimmed, 0, -1);
        }

        return $trimmed.'...';
    }

    private function measureTextWidth(string $text, float $size, ?string $fontPath): int
    {
        if ($fontPath !== null && function_exists('imagettfbbox')) {
            $box = imagettfbbox($size, 0, $fontPath, $text);

            return is_array($box) ? (int) abs($box[2] - $box[0]) : strlen($text) * 9;
        }

        return strlen($text) * 9;
    }

    private function resolveFontPath(bool $bold): ?string
    {
        $candidates = $bold
            ? ['C:/Windows/Fonts/segoeuib.ttf', 'C:/Windows/Fonts/arialbd.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf']
            : ['C:/Windows/Fonts/segoeui.ttf', 'C:/Windows/Fonts/arial.ttf', '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function drawText(GdImage $canvas, float $size, int $x, int $y, int $color, ?string $fontPath, string $text): void
    {
        if ($fontPath !== null && function_exists('imagettftext')) {
            imagettftext($canvas, $size, 0, $x, $y, $color, $fontPath, $text);

            return;
        }

        imagestring($canvas, 5, $x, max(0, $y - 14), $text, $color);
    }

    private function drawCenteredText(GdImage $canvas, float $size, int $x1, int $x2, int $y, int $color, ?string $fontPath, string $text): void
    {
        $textW = $this->measureTextWidth($text, $size, $fontPath);
        $x = $x1 + (int) floor((($x2 - $x1) - $textW) / 2);

        $this->drawText($canvas, $size, max($x1, $x), $y, $color, $fontPath, $text);
    }
}

<?php

namespace App\Services;

use App\Models\DocumentAccessAudit;
use App\Models\TransactionAttachment;
use App\Models\User;
use App\Models\WatermarkSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use setasign\Fpdi\Tcpdf\Fpdi;
use Throwable;

class DocumentWatermarkService
{
    public function settings(): WatermarkSetting
    {
        return WatermarkSetting::instance();
    }

    public function supports(TransactionAttachment $attachment): bool
    {
        $kind = $attachment->fileKind();

        // View overlay works for any previewed type; burn-in remains PDF/image only.
        return in_array($kind, ['pdf', 'image', 'word', 'excel', 'file'], true);
    }

    public function supportsBurnIn(TransactionAttachment $attachment): bool
    {
        $kind = $attachment->fileKind();

        return $kind === 'pdf' || $kind === 'image';
    }

    /**
     * Build a temporary watermarked copy. Caller must delete the path when done.
     *
     * @return array{path: string, mime: string, extension: string}
     */
    public function createWatermarkedCopy(
        TransactionAttachment $attachment,
        User $user,
        DocumentAccessAudit $audit,
        bool $reduceFeatures = false,
    ): array {
        $sourcePath = $attachment->effectiveFilePath();

        if (! $sourcePath || ! Storage::disk('local')->exists($sourcePath)) {
            throw new RuntimeException('Source file not found.');
        }

        $absoluteSource = Storage::disk('local')->path($sourcePath);
        $kind = $attachment->fileKind();
        $context = $this->buildContext($user, $audit);

        if ($reduceFeatures) {
            $context['show_qr_code'] = false;
            $context['qr_payload'] = null;
        }

        // Shared hosting friendly caps — avoid huge rewrites / double-processing.
        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', '180');
        @set_time_limit(180);

        $dir = storage_path('app/temp/watermarks');
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException('Unable to create watermark temp directory.');
        }

        return match ($kind) {
            'pdf' => $this->watermarkPdf($absoluteSource, $context, $dir),
            'image' => $this->watermarkImage($absoluteSource, $attachment->effectiveMimeType(), $context, $dir),
            default => throw new RuntimeException('Burn-in watermarking is only supported for PDF and image files.'),
        };
    }

    /**
     * @return array{
     *     center_lines: list<string>,
     *     footer: string,
     *     qr_payload: string|null,
     *     qr_svg: string|null,
     *     opacity: float,
     *     font_size: int,
     *     angle: int,
     *     show_center_text: bool,
     *     show_footer: bool,
     *     show_qr_code: bool
     * }
     */
    public function buildContext(User $user, DocumentAccessAudit $audit): array
    {
        $settings = $this->settings();
        $user->loadMissing('department');

        $parts = [];

        if ($settings->show_user_name) {
            $parts[] = $user->name;
        }

        if ($settings->show_user_id) {
            $parts[] = 'ID:'.($user->employee_number ?: $user->id);
        }

        if ($settings->show_department) {
            $parts[] = $user->department?->name ?? ($user->orgBreadcrumb() ?: '—');
        }

        if ($settings->show_datetime) {
            $parts[] = Carbon::parse($audit->created_at)->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        }

        if ($settings->show_action_type) {
            $parts[] = strtoupper($audit->action_type);
        }

        if ($settings->show_transaction_id) {
            $parts[] = $audit->transaction_id;
        }

        // Only force a minimal visible mark when at least one visual element is enabled
        // but no field was selected — still respect element toggles.
        if ($parts === [] && ($settings->show_center_text || $settings->show_footer || $settings->show_qr_code)) {
            $parts[] = $user->name;
        }

        $showCenter = (bool) $settings->show_center_text;
        $showFooter = (bool) $settings->show_footer;
        $showQr = (bool) $settings->show_qr_code && filled($audit->transaction_id);

        return [
            'center_lines' => $showCenter ? $parts : [],
            'footer' => $showFooter ? implode(' | ', $parts) : '',
            'qr_payload' => $showQr ? $audit->transaction_id : null,
            'qr_svg' => null,
            'opacity' => $settings->alpha(),
            'font_size' => max(10, min(72, (int) $settings->font_size)),
            'angle' => max(-90, min(90, (int) $settings->angle)),
            'show_center_text' => $showCenter && $parts !== [],
            'show_footer' => $showFooter && $parts !== [],
            'show_qr_code' => $showQr,
        ];
    }

    /**
     * Attach QR SVG for live HTML overlay rendering.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function withOverlayAssets(array $context): array
    {
        if (! empty($context['show_qr_code']) && filled($context['qr_payload'] ?? null)) {
            try {
                $context['qr_svg'] = (string) (new \SimpleSoftwareIO\QrCode\Generator)
                    ->size(64)
                    ->margin(0)
                    ->generate((string) $context['qr_payload']);
            } catch (Throwable) {
                $context['qr_svg'] = null;
            }
        }

        return $context;
    }

    /**
     * @return array{
     *     center_lines: list<string>,
     *     footer: string,
     *     qr_payload: string|null,
     *     opacity: float,
     *     font_size: int,
     *     angle: int,
     *     show_center_text: bool,
     *     show_footer: bool,
     *     show_qr_code: bool
     * }
     */
    public function buildLiveContext(User $user, string $actionType): array
    {
        $audit = new DocumentAccessAudit([
            'transaction_id' => (string) Str::uuid(),
            'action_type' => $actionType,
            'created_at' => now(),
        ]);

        return $this->buildContext($user, $audit);
    }

    /**
     * Single-pass PDF stamp — keeps page XObjects intact (no chunk/merge rewrite).
     * That avoids the 3–4× size explosion caused by re-importing stamped chunks.
     *
     * @param  array{
     *     center_lines: list<string>,
     *     footer: string,
     *     qr_payload: string|null,
     *     opacity: float,
     *     font_size: int,
     *     angle: int,
     *     show_center_text: bool,
     *     show_footer: bool,
     *     show_qr_code: bool
     * }  $context
     * @return array{path: string, mime: string, extension: string}
     */
    private function watermarkPdf(string $sourcePath, array $context, string $dir): array
    {
        $output = $dir.DIRECTORY_SEPARATOR.Str::uuid().'.pdf';

        try {
            $pdf = $this->makeFpdi();
            $pageCount = $pdf->setSourceFile($sourcePath);

            if ($pageCount < 1) {
                throw new RuntimeException('PDF has no pages to watermark.');
            }

            for ($page = 1; $page <= $pageCount; $page++) {
                $templateId = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($templateId);
                $orientation = $size['orientation'] ?? ($size['width'] > $size['height'] ? 'L' : 'P');
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height'], true);
                $this->stampPdfPage($pdf, (float) $size['width'], (float) $size['height'], $context);

                if (($page % 20) === 0) {
                    gc_collect_cycles();
                }
            }

            $pdf->Output($output, 'F');
            unset($pdf);
        } catch (Throwable $e) {
            if (is_file($output)) {
                @unlink($output);
            }

            throw $e;
        }

        if (! is_file($output) || filesize($output) === 0) {
            throw new RuntimeException('Watermarked PDF was not written.');
        }

        return [
            'path' => $output,
            'mime' => 'application/pdf',
            'extension' => 'pdf',
        ];
    }

    private function makeFpdi(): Fpdi
    {
        $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->SetCreator('ARC');
        $pdf->SetAuthor('ARC');
        $pdf->SetTitle('');
        $pdf->SetSubject('');
        $pdf->SetKeywords('');
        // Subset only glyphs used — large win vs embedding full DejaVu on every download.
        $pdf->setFontSubsetting(true);
        $pdf->SetCompression(true);
        $pdf->setJPEGQuality(85);

        return $pdf;
    }

    /**
     * @param  array{
     *     center_lines: list<string>,
     *     footer: string,
     *     qr_payload: string|null,
     *     opacity: float,
     *     font_size: int,
     *     angle: int,
     *     show_center_text: bool,
     *     show_footer: bool,
     *     show_qr_code: bool
     * }  $context
     */
    private function stampPdfPage(Fpdi $pdf, float $width, float $height, array $context): void
    {
        if ($context['show_center_text'] && $context['center_lines'] !== []) {
            $pdf->SetTextColor(80, 80, 80);
            $this->applyPdfFont($pdf, 'B', max(8, (float) $context['font_size']));
            $this->applyPdfAlpha($pdf, (float) $context['opacity']);

            $text = implode("\n", $context['center_lines']);
            $pdf->StartTransform();
            $pdf->Rotate($context['angle'], $width / 2, $height / 2);
            $pdf->MultiCell(
                $width * 0.75,
                max(4, $context['font_size'] * 0.45),
                $text,
                0,
                'C',
                false,
                1,
                $width * 0.125,
                $height / 2 - (count($context['center_lines']) * $context['font_size'] * 0.25),
                true,
                0,
                false,
                true,
                0,
                'M'
            );
            $pdf->StopTransform();
            $this->applyPdfAlpha($pdf, 1.0);
        }

        if ($context['show_footer'] && $context['footer'] !== '') {
            $this->applyPdfAlpha($pdf, max(0.35, min(0.75, $context['opacity'] + 0.25)));
            $pdf->SetTextColor(40, 40, 40);
            $this->applyPdfFont($pdf, '', 7);
            $footerY = max(4, $height - 10);
            $pdf->SetXY(8, $footerY);
            $pdf->Cell($width - ($context['show_qr_code'] ? 28 : 16), 6, $context['footer'], 0, 0, 'L', false, '', 1);
            $this->applyPdfAlpha($pdf, 1.0);
        }

        if ($context['show_qr_code'] && $context['qr_payload']) {
            try {
                $qrSize = 12.0;
                $style = [
                    'border' => false,
                    'padding' => 0,
                    'fgcolor' => [20, 20, 20],
                    'bgcolor' => [255, 255, 255],
                ];
                $pdf->write2DBarcode(
                    $context['qr_payload'],
                    'QRCODE,L',
                    $width - $qrSize - 6,
                    $height - $qrSize - 6,
                    $qrSize,
                    $qrSize,
                    $style
                );
            } catch (Throwable) {
                // QR optional — text watermark still applies.
            }
        }
    }

    private function applyPdfFont(Fpdi $pdf, string $style, float $size): void
    {
        try {
            $pdf->SetFont('dejavusans', $style, $size, '', true);
        } catch (Throwable) {
            $pdf->SetFont('helvetica', $style === 'B' ? 'B' : '', $size);
        }
    }

    private function applyPdfAlpha(Fpdi $pdf, float $alpha): void
    {
        try {
            $pdf->SetAlpha(max(0.05, min(1.0, $alpha)));
        } catch (Throwable) {
            // Keep going without alpha rather than failing the download.
        }
    }

    /**
     * @param  array{
     *     center_lines: list<string>,
     *     footer: string,
     *     qr_payload: string|null,
     *     opacity: float,
     *     font_size: int,
     *     angle: int,
     *     show_center_text: bool,
     *     show_footer: bool,
     *     show_qr_code: bool
     * }  $context
     * @return array{path: string, mime: string, extension: string}
     */
    private function watermarkImage(string $sourcePath, ?string $mime, array $context, string $dir): array
    {
        if (! function_exists('imagecreatefromstring')) {
            throw new RuntimeException('GD extension is required for image watermarking.');
        }

        $image = match (true) {
            str_contains((string) $mime, 'jpeg'), str_contains((string) $mime, 'jpg') => @imagecreatefromjpeg($sourcePath),
            str_contains((string) $mime, 'png') => @imagecreatefrompng($sourcePath),
            str_contains((string) $mime, 'gif') => @imagecreatefromgif($sourcePath),
            str_contains((string) $mime, 'webp') && function_exists('imagecreatefromwebp') => @imagecreatefromwebp($sourcePath),
            default => false,
        };

        if ($image === false) {
            $binary = file_get_contents($sourcePath);

            if ($binary === false) {
                throw new RuntimeException('Unable to read image source.');
            }

            $image = @imagecreatefromstring($binary);
            unset($binary);
        }

        if ($image === false) {
            throw new RuntimeException('Unsupported or corrupt image.');
        }

        imagesavealpha($image, true);
        imagealphablending($image, true);

        $width = imagesx($image);
        $height = imagesy($image);

        $alpha = (int) round((1 - $context['opacity']) * 127);
        $alpha = max(20, min(110, $alpha));
        $color = imagecolorallocatealpha($image, 60, 60, 60, $alpha);
        $footerColor = imagecolorallocatealpha($image, 30, 30, 30, max(10, $alpha - 30));

        if ($context['show_center_text'] && $context['center_lines'] !== []) {
            $font = $this->resolveTtfFont();
            $fontSize = max(10, (int) round($context['font_size'] * ($width / 800)));
            $angle = (float) $context['angle'];
            $text = implode("\n", $context['center_lines']);

            if ($font) {
                $box = imagettfbbox($fontSize, $angle, $font, $text);
                $textWidth = abs(($box[2] ?? 0) - ($box[0] ?? 0));
                $textHeight = abs(($box[5] ?? 0) - ($box[1] ?? 0));
                $x = (int) (($width - $textWidth) / 2);
                $y = (int) (($height + $textHeight) / 2);
                imagettftext($image, $fontSize, $angle, $x, $y, $color, $font, $text);
            } else {
                $label = implode(' | ', $context['center_lines']);
                imagestring($image, 5, (int) ($width * 0.15), (int) ($height / 2), $label, $color);
            }
        }

        if ($context['show_footer'] && $context['footer'] !== '') {
            $font = $this->resolveTtfFont();
            $footerSize = max(8, (int) round(10 * ($width / 900)));

            if ($font) {
                imagettftext($image, $footerSize, 0, 12, $height - 14, $footerColor, $font, $context['footer']);
            } else {
                imagestring($image, 2, 8, $height - 16, $context['footer'], $footerColor);
            }
        }

        if ($context['show_qr_code'] && $context['qr_payload']) {
            $this->drawQrOnImage($image, $context['qr_payload'], $width, $height);
        }

        $extension = match (true) {
            str_contains((string) $mime, 'png') => 'png',
            str_contains((string) $mime, 'webp') => 'png',
            str_contains((string) $mime, 'gif') => 'png',
            default => 'jpg',
        };

        $output = $dir.DIRECTORY_SEPARATOR.Str::uuid().'.'.$extension;

        $ok = match ($extension) {
            'png' => imagepng($image, $output, 6),
            default => imagejpeg($image, $output, 85),
        };

        imagedestroy($image);

        if (! $ok) {
            throw new RuntimeException('Failed to write watermarked image.');
        }

        return [
            'path' => $output,
            'mime' => $extension === 'png' ? 'image/png' : 'image/jpeg',
            'extension' => $extension,
        ];
    }

    private function drawQrOnImage(\GdImage $image, string $payload, int $width, int $height): void
    {
        try {
            $qrCode = Encoder::encode($payload, ErrorCorrectionLevel::L());
            $matrix = $qrCode->getMatrix();
        } catch (Throwable) {
            return;
        }

        $modules = $matrix->getWidth();
        $qrSize = max(36, (int) round(min($width, $height) * 0.07));
        $moduleSize = max(1, intdiv($qrSize, $modules));
        $drawn = $moduleSize * $modules;
        $dstX = $width - $drawn - 10;
        $dstY = $height - $drawn - 10;

        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 20, 20, 20);
        imagefilledrectangle($image, $dstX - 2, $dstY - 2, $dstX + $drawn + 1, $dstY + $drawn + 1, $white);

        for ($y = 0; $y < $modules; $y++) {
            for ($x = 0; $x < $modules; $x++) {
                if (! $matrix->get($x, $y)) {
                    continue;
                }

                $px = $dstX + ($x * $moduleSize);
                $py = $dstY + ($y * $moduleSize);
                imagefilledrectangle($image, $px, $py, $px + $moduleSize - 1, $py + $moduleSize - 1, $black);
            }
        }
    }

    private function resolveTtfFont(): ?string
    {
        $candidates = [
            storage_path('fonts/DejaVuSans.ttf'),
            resource_path('fonts/DejaVuSans.ttf'),
            'C:\\Windows\\Fonts\\arial.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}

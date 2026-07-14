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
    ): array {
        $sourcePath = $attachment->effectiveFilePath();

        if (! $sourcePath || ! Storage::disk('local')->exists($sourcePath)) {
            throw new RuntimeException('Source file not found.');
        }

        $absoluteSource = Storage::disk('local')->path($sourcePath);
        $kind = $attachment->fileKind();
        $context = $this->buildContext($user, $audit);

        // Burn-in paths (download/print) — page pipeline with higher limits.
        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', '600');

        $dir = storage_path('app/temp/watermarks');
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException('Unable to create watermark temp directory.');
        }

        return match ($kind) {
            'pdf' => $this->watermarkPdf($absoluteSource, $context, $dir),
            'image' => $this->watermarkImage($absoluteSource, $attachment->effectiveMimeType(), $context, $dir),
            default => throw new RuntimeException('Watermarking is only supported for PDF and image files.'),
        };
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

        return [
            'center_lines' => $parts,
            'footer' => implode(' | ', $parts),
            'qr_payload' => $settings->show_qr_code ? $audit->transaction_id : null,
            'opacity' => $settings->alpha(),
            'font_size' => $settings->font_size,
            'angle' => $settings->angle,
            'show_center_text' => $settings->show_center_text,
            'show_footer' => $settings->show_footer,
            'show_qr_code' => $settings->show_qr_code && filled($audit->transaction_id),
        ];
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
    private function watermarkPdf(string $sourcePath, array $context, string $dir): array
    {
        $output = $dir.DIRECTORY_SEPARATOR.Str::uuid().'.pdf';
        $pageDir = $dir.DIRECTORY_SEPARATOR.Str::uuid().'-pages';
        $pageFiles = [];
        $tempMerges = [];

        if (! is_dir($pageDir) && ! mkdir($pageDir, 0755, true) && ! is_dir($pageDir)) {
            throw new RuntimeException('Unable to create watermark page directory.');
        }

        try {
            // Peek page count with a short-lived reader, then free it.
            $reader = new Fpdi;
            $reader->setPrintHeader(false);
            $reader->setPrintFooter(false);
            $pageCount = $reader->setSourceFile($sourcePath);
            unset($reader);

            // Stamp one page at a time and write each to disk (keeps peak memory low).
            for ($page = 1; $page <= $pageCount; $page++) {
                $pagePdf = new Fpdi;
                $pagePdf->setPrintHeader(false);
                $pagePdf->setPrintFooter(false);
                $pagePdf->SetAutoPageBreak(false, 0);
                $pagePdf->SetCreator('ARC Watermark');
                $pagePdf->SetAuthor('ARC');
                $pagePdf->setFontSubsetting(false);
                $pagePdf->setSourceFile($sourcePath);

                $templateId = $pagePdf->importPage($page);
                $size = $pagePdf->getTemplateSize($templateId);
                $orientation = $size['orientation'] ?? ($size['width'] > $size['height'] ? 'L' : 'P');
                $pagePdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pagePdf->useTemplate($templateId);
                $this->stampPdfPage($pagePdf, (float) $size['width'], (float) $size['height'], $context);

                $pageFile = $pageDir.DIRECTORY_SEPARATOR.sprintf('%05d.pdf', $page);
                $pagePdf->Output($pageFile, 'F');
                unset($pagePdf);
                gc_collect_cycles();

                $pageFiles[] = $pageFile;
            }

            if ($pageFiles === []) {
                throw new RuntimeException('PDF has no pages to watermark.');
            }

            if (count($pageFiles) === 1) {
                if (! @rename($pageFiles[0], $output) && ! @copy($pageFiles[0], $output)) {
                    throw new RuntimeException('Unable to finalize watermarked PDF.');
                }
            } else {
                $this->mergePdfFilesPairwise($pageFiles, $output, $dir, $tempMerges);
            }
        } catch (Throwable $e) {
            if (is_file($output)) {
                @unlink($output);
            }

            throw $e;
        } finally {
            foreach ($pageFiles as $pageFile) {
                if (is_file($pageFile)) {
                    @unlink($pageFile);
                }
            }

            foreach ($tempMerges as $mergeFile) {
                if (is_file($mergeFile)) {
                    @unlink($mergeFile);
                }
            }

            if (is_dir($pageDir)) {
                @rmdir($pageDir);
            }
        }

        return [
            'path' => $output,
            'mime' => 'application/pdf',
            'extension' => 'pdf',
        ];
    }

    /**
     * Tournament-style merge: never hold more than two PDFs in memory at once.
     *
     * @param  list<string>  $files
     * @param  list<string>  $tempMerges
     */
    private function mergePdfFilesPairwise(array $files, string $output, string $dir, array &$tempMerges): void
    {
        $queue = array_values($files);

        while (count($queue) > 1) {
            $left = array_shift($queue);
            $right = array_shift($queue);
            $merged = $dir.DIRECTORY_SEPARATOR.Str::uuid().'-merge.pdf';
            $this->mergeTwoPdfFiles($left, $right, $merged);
            $tempMerges[] = $merged;

            if (! in_array($left, $files, true) && is_file($left)) {
                @unlink($left);
            }

            if (! in_array($right, $files, true) && is_file($right)) {
                @unlink($right);
            }

            $queue[] = $merged;
            gc_collect_cycles();
        }

        $final = $queue[0];

        if (! @rename($final, $output) && ! @copy($final, $output)) {
            throw new RuntimeException('Unable to finalize merged watermarked PDF.');
        }

        if (is_file($final) && realpath($final) !== realpath($output)) {
            @unlink($final);
        }
    }

    private function mergeTwoPdfFiles(string $left, string $right, string $destination): void
    {
        $pdf = new Fpdi;
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->setFontSubsetting(false);

        foreach ([$left, $right] as $file) {
            $count = $pdf->setSourceFile($file);

            for ($page = 1; $page <= $count; $page++) {
                $templateId = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($templateId);
                $orientation = $size['orientation'] ?? ($size['width'] > $size['height'] ? 'L' : 'P');
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }
        }

        $pdf->Output($destination, 'F');
        unset($pdf);
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
            $pdf->SetFont('dejavusans', 'B', max(8, $context['font_size']));
            $pdf->SetAlpha($context['opacity']);

            $text = implode("\n", $context['center_lines']);
            $lineHeight = max(4, $context['font_size'] * 0.45);
            $blockHeight = count($context['center_lines']) * $context['font_size'] * 0.5;
            $cellWidth = $width * 0.75;

            // Stamp the same diagonal block in several positions so every page is clearly marked.
            $anchors = [
                [$width / 2, $height / 2],
                [$width * 0.32, $height * 0.28],
                [$width * 0.68, $height * 0.72],
            ];

            foreach ($anchors as [$cx, $cy]) {
                $pdf->StartTransform();
                $pdf->Rotate($context['angle'], $cx, $cy);
                $pdf->MultiCell(
                    $cellWidth,
                    $lineHeight,
                    $text,
                    0,
                    'C',
                    false,
                    1,
                    $cx - ($cellWidth / 2),
                    $cy - ($blockHeight / 2),
                    true,
                    0,
                    false,
                    true,
                    0,
                    'M'
                );
                $pdf->StopTransform();
            }

            $pdf->SetAlpha(1);
        }

        if ($context['show_footer'] && $context['footer'] !== '') {
            $pdf->SetAlpha(max(0.35, min(0.75, $context['opacity'] + 0.25)));
            $pdf->SetTextColor(40, 40, 40);
            $pdf->SetFont('dejavusans', '', 7);
            $footerY = max(4, $height - 10);
            $pdf->SetXY(8, $footerY);
            $pdf->Cell($width - ($context['show_qr_code'] ? 28 : 16), 6, $context['footer'], 0, 0, 'L', false, '', 1);
            $pdf->SetAlpha(1);
        }

        if ($context['show_qr_code'] && $context['qr_payload']) {
            $qrSize = 14.0;
            $style = [
                'border' => false,
                'padding' => 1,
                'fgcolor' => [20, 20, 20],
                'bgcolor' => [255, 255, 255],
            ];
            $pdf->write2DBarcode(
                $context['qr_payload'],
                'QRCODE,M',
                $width - $qrSize - 6,
                $height - $qrSize - 6,
                $qrSize,
                $qrSize,
                $style
            );
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

        // Avoid holding a second full copy of the file in a PHP string when possible.
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
            default => imagejpeg($image, $output, 90),
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
            $qrCode = Encoder::encode($payload, ErrorCorrectionLevel::M());
            $matrix = $qrCode->getMatrix();
        } catch (Throwable) {
            return;
        }

        $modules = $matrix->getWidth();
        $qrSize = max(40, (int) round(min($width, $height) * 0.08));
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
            base_path('vendor/tecnickcom/tcpdf/fonts/dejavusans.ttf'),
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

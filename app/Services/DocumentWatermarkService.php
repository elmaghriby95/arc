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

        // DOWNLOAD / PRINT burn-in — raise limits for multi-page / scanned PDFs.
        @ini_set('memory_limit', '2048M');
        @ini_set('max_execution_time', '600');
        @set_time_limit(600);

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

        // Always keep at least one visible line so every page is stamped.
        if ($parts === []) {
            $parts[] = $user->name;
            $parts[] = $audit->transaction_id;
        }

        return [
            'center_lines' => $parts,
            'footer' => implode(' | ', $parts),
            'qr_payload' => $settings->show_qr_code ? $audit->transaction_id : null,
            'qr_svg' => null,
            'opacity' => $settings->alpha(),
            'font_size' => $settings->font_size,
            'angle' => $settings->angle,
            'show_center_text' => $settings->show_center_text || $parts !== [],
            'show_footer' => $settings->show_footer || $parts !== [],
            'show_qr_code' => $settings->show_qr_code && filled($audit->transaction_id),
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
     * Build watermark context for the current user without persisting an audit row yet.
     *
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
        $workingPath = $sourcePath;
        $tempInputs = [];

        try {
            if (! $this->canFpdiOpen($sourcePath)) {
                $normalized = $this->normalizePdfCompatibility($sourcePath, $dir);

                if (! $normalized || ! $this->canFpdiOpen($normalized)) {
                    if ($normalized && is_file($normalized)) {
                        @unlink($normalized);
                    }

                    throw new RuntimeException(
                        'This PDF cannot be imported for watermarking (unsupported compression or encryption).'
                    );
                }

                $workingPath = $normalized;
                $tempInputs[] = $normalized;
            }

            return $this->watermarkPdfWithFpdi($workingPath, $context, $dir);
        } finally {
            foreach ($tempInputs as $temp) {
                if (is_file($temp)) {
                    @unlink($temp);
                }
            }
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
    private function watermarkPdfWithFpdi(string $sourcePath, array $context, string $dir): array
    {
        $probe = $this->makeFpdi();
        $pageCount = $probe->setSourceFile($sourcePath);
        unset($probe);

        if ($pageCount < 1) {
            throw new RuntimeException('PDF has no pages to watermark.');
        }

        $sourceSize = (int) (@filesize($sourcePath) ?: 0);

        // Small files: one pass. Larger files: stamp in page chunks to avoid OOM.
        if ($pageCount <= 12 && $sourceSize < (8 * 1024 * 1024)) {
            return $this->watermarkPdfRange($sourcePath, 1, $pageCount, $context, $dir);
        }

        $chunkSize = $sourceSize > (15 * 1024 * 1024) ? 2 : 5;
        $chunkPaths = [];

        try {
            for ($start = 1; $start <= $pageCount; $start += $chunkSize) {
                $end = min($pageCount, $start + $chunkSize - 1);
                $chunkPaths[] = $this->watermarkPdfRange($sourcePath, $start, $end, $context, $dir)['path'];
                gc_collect_cycles();
            }

            return $this->mergePdfFiles($chunkPaths, $dir);
        } catch (Throwable $e) {
            foreach ($chunkPaths as $chunkPath) {
                if (is_file($chunkPath)) {
                    @unlink($chunkPath);
                }
            }

            throw $e;
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
    private function watermarkPdfRange(
        string $sourcePath,
        int $fromPage,
        int $toPage,
        array $context,
        string $dir,
    ): array {
        $output = $dir.DIRECTORY_SEPARATOR.Str::uuid().'.pdf';

        try {
            $pdf = $this->makeFpdi();
            $pdf->setSourceFile($sourcePath);

            for ($page = $fromPage; $page <= $toPage; $page++) {
                $templateId = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($templateId);
                $orientation = $size['orientation'] ?? ($size['width'] > $size['height'] ? 'L' : 'P');
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
                $this->stampPdfPage($pdf, (float) $size['width'], (float) $size['height'], $context);
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

    /**
     * @param  list<string>  $paths
     * @return array{path: string, mime: string, extension: string}
     */
    private function mergePdfFiles(array $paths, string $dir): array
    {
        if ($paths === []) {
            throw new RuntimeException('No watermarked PDF chunks to merge.');
        }

        if (count($paths) === 1) {
            return [
                'path' => $paths[0],
                'mime' => 'application/pdf',
                'extension' => 'pdf',
            ];
        }

        $merged = $this->mergePdfFilesWithQpdf($paths, $dir);

        if ($merged !== null) {
            foreach ($paths as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }

            return $merged;
        }

        $output = $dir.DIRECTORY_SEPARATOR.Str::uuid().'.pdf';

        try {
            $pdf = $this->makeFpdi();

            foreach ($paths as $path) {
                $pageCount = $pdf->setSourceFile($path);

                for ($page = 1; $page <= $pageCount; $page++) {
                    $templateId = $pdf->importPage($page);
                    $size = $pdf->getTemplateSize($templateId);
                    $orientation = $size['orientation'] ?? ($size['width'] > $size['height'] ? 'L' : 'P');
                    $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                    $pdf->useTemplate($templateId);
                }
            }

            $pdf->Output($output, 'F');
            unset($pdf);
        } catch (Throwable $e) {
            if (is_file($output)) {
                @unlink($output);
            }

            throw $e;
        } finally {
            foreach ($paths as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }

        if (! is_file($output) || filesize($output) === 0) {
            throw new RuntimeException('Merged watermarked PDF was not written.');
        }

        return [
            'path' => $output,
            'mime' => 'application/pdf',
            'extension' => 'pdf',
        ];
    }

    /**
     * @param  list<string>  $paths
     * @return array{path: string, mime: string, extension: string}|null
     */
    private function mergePdfFilesWithQpdf(array $paths, string $dir): ?array
    {
        $binary = $this->findBinary(['qpdf']);

        if ($binary === null) {
            return null;
        }

        $output = $dir.DIRECTORY_SEPARATOR.Str::uuid().'.pdf';
        $command = array_merge(
            [$binary, '--empty', '--pages'],
            $paths,
            ['--', $output],
        );

        if (! $this->runProcess($command)) {
            if (is_file($output)) {
                @unlink($output);
            }

            return null;
        }

        if (! is_file($output) || filesize($output) === 0) {
            return null;
        }

        return [
            'path' => $output,
            'mime' => 'application/pdf',
            'extension' => 'pdf',
        ];
    }

    private function makeFpdi(): Fpdi
    {
        $pdf = new Fpdi;
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->SetCreator('ARC Watermark');
        $pdf->SetAuthor('ARC');
        $pdf->setFontSubsetting(false);
        $pdf->SetCompression(true);

        return $pdf;
    }

    private function canFpdiOpen(string $path): bool
    {
        try {
            $pdf = $this->makeFpdi();
            $count = $pdf->setSourceFile($path);
            unset($pdf);

            return $count > 0;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Re-save PDF without compressed object/xref streams so free FPDI can import it.
     */
    private function normalizePdfCompatibility(string $sourcePath, string $dir): ?string
    {
        $output = $dir.DIRECTORY_SEPARATOR.Str::uuid().'-norm.pdf';

        $qpdf = $this->findBinary(['qpdf']);
        if ($qpdf !== null) {
            $ok = $this->runProcess([
                $qpdf,
                '--object-streams=disable',
                '--compress-streams=n',
                '--decode-level=generalized',
                $sourcePath,
                $output,
            ]);

            if ($ok && is_file($output) && filesize($output) > 0) {
                return $output;
            }
        }

        if (is_file($output)) {
            @unlink($output);
        }

        $gs = $this->findBinary(['gs', 'gswin64c', 'gswin32c']);
        if ($gs !== null) {
            $ok = $this->runProcess([
                $gs,
                '-dSAFER',
                '-dBATCH',
                '-dNOPAUSE',
                '-sDEVICE=pdfwrite',
                '-dCompatibilityLevel=1.4',
                '-dPDFSETTINGS=/default',
                '-sOutputFile='.$output,
                $sourcePath,
            ]);

            if ($ok && is_file($output) && filesize($output) > 0) {
                return $output;
            }
        }

        if (is_file($output)) {
            @unlink($output);
        }

        return null;
    }

    /**
     * @param  list<string>  $names
     */
    private function findBinary(array $names): ?string
    {
        foreach ($names as $name) {
            if (is_file($name) && is_executable($name)) {
                return $name;
            }

            $finder = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'where' : 'which';
            $nullDevice = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'NUL' : '/dev/null';
            $line = @shell_exec($finder.' '.escapeshellarg($name).' 2>'.$nullDevice);

            if (! is_string($line) || trim($line) === '') {
                continue;
            }

            $candidate = trim(explode("\n", str_replace("\r", '', $line))[0]);

            if ($candidate !== '' && (is_file($candidate) || strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $command
     */
    private function runProcess(array $command): bool
    {
        if ($command === []) {
            return false;
        }

        $escaped = array_map('escapeshellarg', $command);
        $line = implode(' ', $escaped);
        $output = [];
        $exit = 1;
        @exec($line.' 2>&1', $output, $exit);

        return $exit === 0;
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
            } catch (Throwable) {
                // QR is optional; keep text/footer watermark if barcode drawing fails.
            }
        }
    }

    private function applyPdfFont(Fpdi $pdf, string $style, float $size): void
    {
        try {
            $pdf->SetFont('dejavusans', $style, $size);
        } catch (Throwable) {
            $pdf->SetFont('helvetica', $style === 'B' ? 'B' : '', $size);
        }
    }

    private function applyPdfAlpha(Fpdi $pdf, float $alpha): void
    {
        try {
            $pdf->SetAlpha(max(0.05, min(1.0, $alpha)));
        } catch (Throwable) {
            // Some TCPDF builds reject SetAlpha; keep opaque text rather than failing the download.
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

<?php

declare(strict_types=1);

/** Minimal PDF builder that embeds JPEG pages (DCTDecode). */
final class PdfBuilder
{
    /** @param list<string> $jpegPaths */
    public static function fromJpegPages(array $jpegPaths, int $dpi = 200): string
    {
        if ($jpegPaths === []) {
            throw new RuntimeException('No pages to export.');
        }

        $objects = [];
        $pageObjectIds = [];
        $nextId = 1;

        foreach ($jpegPaths as $jpegPath) {
            $jpegData = file_get_contents($jpegPath);

            if ($jpegData === false || $jpegData === '') {
                continue;
            }

            $size = getimagesize($jpegPath);

            if ($size === false) {
                continue;
            }

            [$width, $height] = $size;
            $widthPoints = round($width * 72 / max(1, $dpi), 2);
            $heightPoints = round($height * 72 / max(1, $dpi), 2);

            $imageId = $nextId++;
            $objects[$imageId] = "<< /Type /XObject /Subtype /Image /Width {$width} /Height {$height} "
                ."/ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ".strlen($jpegData)." >>\n"
                ."stream\n{$jpegData}\nendstream";

            $content = sprintf("q %.2F 0 0 %.2F 0 0 cm /Im%d Do Q", $widthPoints, $heightPoints, $imageId);
            $contentId = $nextId++;
            $objects[$contentId] = "<< /Length ".strlen($content)." >>\nstream\n{$content}\nendstream";

            $pageId = $nextId++;
            $pageObjectIds[] = $pageId;
            $objects[$pageId] = "<< /Type /Page /Parent {{PAGES_ID}} 0 R /MediaBox [0 0 {$widthPoints} {$heightPoints}] "
                ."/Contents {$contentId} 0 R /Resources << /XObject << /Im{$imageId} {$imageId} 0 R >> >> >>";
        }

        if ($pageObjectIds === []) {
            throw new RuntimeException('Unable to build PDF from scanned pages.');
        }

        $pagesId = $nextId++;
        $kids = implode(' 0 R ', $pageObjectIds).' 0 R';
        $objects[$pagesId] = "<< /Type /Pages /Kids [ {$kids} ] /Count ".count($pageObjectIds).' >>';

        $catalogId = $nextId++;
        $objects[$catalogId] = "<< /Type /Catalog /Pages {$pagesId} 0 R >>";

        foreach ($objects as $id => $body) {
            if ($id !== $pagesId && str_contains($body, '{{PAGES_ID}}')) {
                $objects[$id] = str_replace('{{PAGES_ID}}', (string) $pagesId, $body);
            }
        }

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0 => 0];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".($nextId)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($id = 1; $id < $nextId; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id] ?? 0);
        }

        $pdf .= "trailer\n<< /Size {$nextId} /Root {$catalogId} 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        $target = self::tempPath('pdf');
        file_put_contents($target, $pdf);

        return $target;
    }

    private static function tempPath(string $extension): string
    {
        $directory = sys_get_temp_dir();

        do {
            $target = $directory.DIRECTORY_SEPARATOR.'arc-scan-'.bin2hex(random_bytes(8)).'.'.$extension;
        } while (file_exists($target));

        return $target;
    }
}

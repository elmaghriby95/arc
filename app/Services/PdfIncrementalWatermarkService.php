<?php

namespace App\Services;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use RuntimeException;

class PdfIncrementalWatermarkService
{
    /**
     * Add printable watermark annotations by appending an incremental PDF update.
     * Original page streams are not decoded or re-encoded, so scanned PDFs keep
     * their original compression and size characteristics.
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
     */
    public function watermark(string $sourcePath, array $context, string $outputPath): void
    {
        $source = file_get_contents($sourcePath);

        if ($source === false || $source === '') {
            throw new RuntimeException('Unable to read source PDF.');
        }

        $objects = $this->parseObjects($source);
        $pages = $this->findPageObjects($objects);

        if ($pages === []) {
            throw new RuntimeException('Unable to locate PDF pages for incremental watermarking.');
        }

        $previousXref = $this->findPreviousXrefOffset($source);
        $rootRef = $this->findRootReference($source, $objects);
        $infoRef = $this->findTrailerReference($source, 'Info');
        $idEntry = $this->findTrailerId($source);
        $maxObjectNumber = max(array_keys($objects));
        $nextObjectNumber = $maxObjectNumber + 1;

        $updates = [];
        $qrAppearanceRef = null;

        if (! empty($context['show_qr_code']) && filled($context['qr_payload'] ?? null)) {
            $qrAppearanceRef = $nextObjectNumber++;
            $updates[$qrAppearanceRef] = $this->buildQrAppearanceObject($qrAppearanceRef, (string) $context['qr_payload']);
        }

        foreach ($pages as $pageNumber => $pageObject) {
            $box = $this->resolvePageBox($pageObject['body'], $objects);
            $width = max(1.0, $box[2] - $box[0]);
            $height = max(1.0, $box[3] - $box[1]);
            $annotationRefs = [];

            if (! empty($context['show_center_text']) && ($context['center_lines'] ?? []) !== []) {
                $centerLines = array_values(array_filter($context['center_lines']));
                $centerFontSize = max(8, (int) round(((int) $context['font_size']) * ($width / 800)));
                $centerRectWidth = $width * 0.75;
                $centerRectHeight = max(
                    $centerFontSize * 2.2,
                    count($centerLines) * $centerFontSize * 1.25,
                );
                $annotationNumber = $nextObjectNumber++;
                $annotationRefs[] = "{$annotationNumber} 0 R";
                $updates[$annotationNumber] = $this->buildFreeTextAnnotationObject(
                    $annotationNumber,
                    implode("\n", $centerLines),
                    [
                        $box[0] + (($width - $centerRectWidth) / 2),
                        $box[1] + (($height - $centerRectHeight) / 2),
                        $box[0] + (($width + $centerRectWidth) / 2),
                        $box[1] + (($height + $centerRectHeight) / 2),
                    ],
                    (float) $context['opacity'],
                    $centerFontSize,
                    (int) $context['angle'],
                    true,
                );
            }

            if (! empty($context['show_footer']) && filled($context['footer'] ?? '')) {
                $annotationNumber = $nextObjectNumber++;
                $annotationRefs[] = "{$annotationNumber} 0 R";
                $updates[$annotationNumber] = $this->buildFreeTextAnnotationObject(
                    $annotationNumber,
                    (string) $context['footer'],
                    [
                        $box[0] + 8,
                        $box[1] + 6,
                        $box[2] - ($qrAppearanceRef ? 56 : 8),
                        $box[1] + 28,
                    ],
                    max(0.35, min(0.75, ((float) $context['opacity']) + 0.25)),
                    8,
                    0,
                    false,
                );
            }

            if ($qrAppearanceRef !== null) {
                $qrSize = min(42.0, max(26.0, $width * 0.08));
                $annotationNumber = $nextObjectNumber++;
                $annotationRefs[] = "{$annotationNumber} 0 R";
                $updates[$annotationNumber] = $this->buildAppearanceAnnotationObject(
                    $annotationNumber,
                    $qrAppearanceRef,
                    [
                        $box[2] - $qrSize - 8,
                        $box[1] + 8,
                        $box[2] - 8,
                        $box[1] + $qrSize + 8,
                    ],
                );
            }

            if ($annotationRefs !== []) {
                $updates[$pageNumber] = $this->buildUpdatedPageObject(
                    $pageNumber,
                    (int) $pageObject['generation'],
                    $pageObject['body'],
                    $annotationRefs,
                    $objects,
                );
            }
        }

        if ($updates === []) {
            throw new RuntimeException('No watermark elements are enabled.');
        }

        $result = $this->appendIncrementalUpdate(
            $source,
            $updates,
            max($nextObjectNumber, $maxObjectNumber + 1),
            $previousXref,
            $rootRef,
            $infoRef,
            $idEntry,
        );

        if (file_put_contents($outputPath, $result) === false) {
            throw new RuntimeException('Unable to write incrementally watermarked PDF.');
        }
    }

    /**
     * @return array<int, array{generation: int, body: string}>
     */
    private function parseObjects(string $source): array
    {
        preg_match_all('/(?:^|[\r\n])(\d+)\s+(\d+)\s+obj\b(.*?)\bendobj/s', $source, $matches, PREG_SET_ORDER);

        $objects = [];

        foreach ($matches as $match) {
            $objects[(int) $match[1]] = [
                'generation' => (int) $match[2],
                'body' => trim($match[3]),
            ];
        }

        if ($objects === []) {
            throw new RuntimeException('PDF object table could not be parsed.');
        }

        return $objects;
    }

    /**
     * @param  array<int, array{generation: int, body: string}>  $objects
     * @return array<int, array{generation: int, body: string}>
     */
    private function findPageObjects(array $objects): array
    {
        $pages = [];

        foreach ($objects as $number => $object) {
            $body = $object['body'];

            if (preg_match('/\/Type\s*\/Page\b/', $body) && ! preg_match('/\/Type\s*\/Pages\b/', $body)) {
                $pages[$number] = $object;
            }
        }

        return $pages;
    }

    /**
     * @param  array<int, array{generation: int, body: string}>  $objects
     * @return array{0: float, 1: float, 2: float, 3: float}
     */
    private function resolvePageBox(string $pageBody, array $objects): array
    {
        $visited = [];
        $body = $pageBody;

        while (true) {
            if ($box = $this->extractBox($body, 'CropBox')) {
                return $box;
            }

            if ($box = $this->extractBox($body, 'MediaBox')) {
                return $box;
            }

            if (! preg_match('/\/Parent\s+(\d+)\s+\d+\s+R/', $body, $match)) {
                break;
            }

            $parentNumber = (int) $match[1];
            if (isset($visited[$parentNumber], $objects[$parentNumber]) || ! isset($objects[$parentNumber])) {
                break;
            }

            $visited[$parentNumber] = true;
            $body = $objects[$parentNumber]['body'];
        }

        return [0.0, 0.0, 595.0, 842.0];
    }

    /**
     * @return array{0: float, 1: float, 2: float, 3: float}|null
     */
    private function extractBox(string $body, string $name): ?array
    {
        if (! preg_match('/\/'.$name.'\s*\[([^\]]+)\]/', $body, $match)) {
            return null;
        }

        preg_match_all('/-?\d+(?:\.\d+)?/', $match[1], $numbers);

        if (count($numbers[0]) < 4) {
            return null;
        }

        return [
            (float) $numbers[0][0],
            (float) $numbers[0][1],
            (float) $numbers[0][2],
            (float) $numbers[0][3],
        ];
    }

    /**
     * @param  array{0: float, 1: float, 2: float, 3: float}  $rect
     */
    private function buildFreeTextAnnotationObject(
        int $number,
        string $text,
        array $rect,
        float $opacity,
        int $fontSize,
        int $rotation,
        bool $centered,
    ): string {
        $color = '0.35 0.35 0.35';
        $flags = 4 | 128;
        $rotationEntry = $rotation !== 0 ? ' /Rotate '.$rotation.' /MK << /R '.$rotation.' >>' : '';
        $alignment = $centered ? 1 : 0;

        return $number." 0 obj\n".
            "<< /Type /Annot /Subtype /FreeText".
            " /Rect ".$this->formatRect($rect).
            " /Contents ".$this->pdfHexString($text).
            " /DA (/Helv ".$fontSize." Tf ".$color." rg)".
            " /Q ".$alignment.
            " /F ".$flags.
            " /CA ".sprintf('%.3F', max(0.05, min(1.0, $opacity))).
            " /Border [0 0 0] /BS << /W 0 >> /C [1 1 1]".
            " /DS ".$this->pdfLiteralString('font-family: Helvetica, Arial, sans-serif; font-size: '.$fontSize.'pt; color: #666666; background-color: transparent;').
            $rotationEntry.
            " >>\nendobj\n";
    }

    private function buildQrAppearanceObject(int $number, string $payload): string
    {
        $commands = ["q", "1 1 1 rg", "0 0 100 100 re f", "0 0 0 rg"];

        try {
            $qrCode = Encoder::encode($payload, ErrorCorrectionLevel::L());
            $matrix = $qrCode->getMatrix();
            $modules = $matrix->getWidth();
            $cell = 92 / $modules;

            for ($y = 0; $y < $modules; $y++) {
                for ($x = 0; $x < $modules; $x++) {
                    if (! $matrix->get($x, $y)) {
                        continue;
                    }

                    $px = 4 + ($x * $cell);
                    $py = 96 - (($y + 1) * $cell);
                    $commands[] = sprintf('%.3F %.3F %.3F %.3F re f', $px, $py, $cell, $cell);
                }
            }
        } catch (\Throwable) {
            $commands[] = '10 10 80 80 re S';
        }

        $commands[] = 'Q';
        $stream = implode("\n", $commands)."\n";

        return $number." 0 obj\n".
            "<< /Type /XObject /Subtype /Form /BBox [0 0 100 100] /Resources << >> /Length ".strlen($stream)." >>\n".
            "stream\n".$stream."endstream\nendobj\n";
    }

    /**
     * @param  array{0: float, 1: float, 2: float, 3: float}  $rect
     */
    private function buildAppearanceAnnotationObject(int $number, int $appearanceRef, array $rect): string
    {
        return $number." 0 obj\n".
            "<< /Type /Annot /Subtype /Stamp".
            " /Rect ".$this->formatRect($rect).
            " /F ".(4 | 128).
            " /Border [0 0 0]".
            " /AP << /N ".$appearanceRef." 0 R >>".
            " >>\nendobj\n";
    }

    /**
     * @param  list<string>  $annotationRefs
     * @param  array<int, array{generation: int, body: string}>  $objects
     */
    private function buildUpdatedPageObject(
        int $pageNumber,
        int $generation,
        string $pageBody,
        array $annotationRefs,
        array $objects,
    ): string {
        $updatedBody = $this->appendAnnotations($pageBody, $annotationRefs, $objects);

        return $pageNumber.' '.$generation." obj\n".$updatedBody."\nendobj\n";
    }

    /**
     * @param  list<string>  $annotationRefs
     * @param  array<int, array{generation: int, body: string}>  $objects
     */
    private function appendAnnotations(string $pageBody, array $annotationRefs, array $objects): string
    {
        $addition = ' '.implode(' ', $annotationRefs);

        if (preg_match('/\/Annots\s+(\d+)\s+\d+\s+R/', $pageBody, $match, PREG_OFFSET_CAPTURE)) {
            $arrayNumber = (int) $match[1][0];
            $existing = $objects[$arrayNumber]['body'] ?? '[]';
            $array = trim($existing);

            if (str_starts_with($array, '[') && str_ends_with($array, ']')) {
                $newArray = rtrim(substr($array, 0, -1)).$addition.' ]';

                return substr_replace(
                    $pageBody,
                    '/Annots '.$newArray,
                    $match[0][1],
                    strlen($match[0][0]),
                );
            }
        }

        $annotsPos = strpos($pageBody, '/Annots');
        if ($annotsPos !== false) {
            $value = $this->readPdfValue($pageBody, $annotsPos + 7);

            if ($value !== null && str_starts_with(trim($value['value']), '[')) {
                $array = trim($value['value']);
                $newArray = rtrim(substr($array, 0, -1)).$addition.' ]';

                return substr_replace($pageBody, '/Annots '.$newArray, $annotsPos, $value['end'] - $annotsPos);
            }
        }

        $insertAt = strrpos($pageBody, '>>');

        if ($insertAt === false) {
            throw new RuntimeException('Page dictionary could not be updated.');
        }

        return substr_replace($pageBody, ' /Annots ['.implode(' ', $annotationRefs).'] ', $insertAt, 0);
    }

    /**
     * @return array{value: string, end: int}|null
     */
    private function readPdfValue(string $body, int $offset): ?array
    {
        $length = strlen($body);

        while ($offset < $length && ctype_space($body[$offset])) {
            $offset++;
        }

        if ($offset >= $length) {
            return null;
        }

        if ($body[$offset] === '[') {
            $depth = 0;

            for ($i = $offset; $i < $length; $i++) {
                if ($body[$i] === '[') {
                    $depth++;
                } elseif ($body[$i] === ']') {
                    $depth--;

                    if ($depth === 0) {
                        return [
                            'value' => substr($body, $offset, $i - $offset + 1),
                            'end' => $i + 1,
                        ];
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $objects
     */
    private function appendIncrementalUpdate(
        string $source,
        array $objects,
        int $size,
        ?int $previousXref,
        ?string $rootRef,
        ?string $infoRef,
        ?string $idEntry,
    ): string {
        ksort($objects);

        $append = "\n";
        $offsets = [];

        foreach ($objects as $number => $object) {
            $offsets[(int) $number] = strlen($source) + strlen($append);
            $append .= $object;

            if (! str_ends_with($append, "\n")) {
                $append .= "\n";
            }
        }

        $xrefOffset = strlen($source) + strlen($append);
        $append .= "xref\n";

        foreach ($this->groupConsecutiveOffsets($offsets) as $start => $group) {
            $append .= $start.' '.count($group)."\n";

            foreach ($group as $objectNumber => $offset) {
                $append .= sprintf("%010d 00000 n \n", $offset);
            }
        }

        $trailer = '<< /Size '.$size;

        if ($rootRef) {
            $trailer .= ' /Root '.$rootRef;
        }

        if ($infoRef) {
            $trailer .= ' /Info '.$infoRef;
        }

        if ($idEntry) {
            $trailer .= ' /ID '.$idEntry;
        }

        if ($previousXref !== null) {
            $trailer .= ' /Prev '.$previousXref;
        }

        $trailer .= ' >>';

        return $source.$append."trailer\n".$trailer."\nstartxref\n".$xrefOffset."\n%%EOF\n";
    }

    /**
     * @param  array<int, int>  $offsets
     * @return array<int, array<int, int>>
     */
    private function groupConsecutiveOffsets(array $offsets): array
    {
        $groups = [];
        $currentStart = null;
        $previous = null;

        foreach ($offsets as $number => $offset) {
            if ($currentStart === null || $previous === null || $number !== $previous + 1) {
                $currentStart = $number;
                $groups[$currentStart] = [];
            }

            $groups[$currentStart][$number] = $offset;
            $previous = $number;
        }

        return $groups;
    }

    private function findPreviousXrefOffset(string $source): ?int
    {
        if (preg_match_all('/startxref\s+(\d+)\s+%%EOF/s', $source, $matches) && $matches[1] !== []) {
            return (int) end($matches[1]);
        }

        return null;
    }

    /**
     * @param  array<int, array{generation: int, body: string}>  $objects
     */
    private function findRootReference(string $source, array $objects): ?string
    {
        $trailerRoot = $this->findTrailerReference($source, 'Root');

        if ($trailerRoot) {
            return $trailerRoot;
        }

        foreach ($objects as $number => $object) {
            if (preg_match('/\/Type\s*\/Catalog\b/', $object['body'])) {
                return $number.' '.$object['generation'].' R';
            }
        }

        return null;
    }

    private function findTrailerReference(string $source, string $name): ?string
    {
        if (preg_match_all('/\/'.$name.'\s+(\d+\s+\d+\s+R)/', $source, $matches) && $matches[1] !== []) {
            return end($matches[1]);
        }

        return null;
    }

    private function findTrailerId(string $source): ?string
    {
        if (preg_match_all('/\/ID\s*(\[[^\]]+\])/', $source, $matches) && $matches[1] !== []) {
            return end($matches[1]);
        }

        return null;
    }

    /**
     * @param  array{0: float, 1: float, 2: float, 3: float}  $rect
     */
    private function formatRect(array $rect): string
    {
        return '['.implode(' ', array_map(fn (float $number): string => sprintf('%.3F', $number), $rect)).']';
    }

    private function pdfHexString(string $value): string
    {
        $encoded = false;

        if (function_exists('mb_convert_encoding')) {
            $encoded = mb_convert_encoding($value, 'UTF-16BE', 'UTF-8');
        }

        if ($encoded === false && function_exists('iconv')) {
            $encoded = iconv('UTF-8', 'UTF-16BE//IGNORE', $value);
        }

        if ($encoded === false) {
            $encoded = $value;
        }

        return '<FEFF'.strtoupper(bin2hex($encoded)).'>';
    }

    private function pdfLiteralString(string $value): string
    {
        return '('.str_replace(
            ["\\", '(', ')', "\r", "\n"],
            ["\\\\", "\(", "\)", '\r', '\n'],
            $value,
        ).')';
    }
}

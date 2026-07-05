<?php

declare(strict_types=1);

require_once __DIR__.'/ScanPostProcessor.php';
require_once __DIR__.'/PdfBuilder.php';

/**
 * WIA scanner bridge for Windows (HP ScanJet and compatible devices).
 */
final class WiaScanner
{
    private const WIA_FORMAT_JPEG = '{B96B3CAE-0728-11D3-9D7B-0000F81EF32E}';
    private const WIA_INTENT_IMAGE_TYPE_GRAYSCALE = 0x00000002;
    private const WIA_INTENT_IMAGE_TYPE_COLOR = 0x00000001;
    private const WIA_DPS_DOCUMENT_HANDLING_SELECT = 3088;
    private const WIA_IPS_PAGES = 3096;
    private const MAX_FEEDER_PAGES = 50;

    /** @return list<array{id: string, name: string}> */
    public static function listDevices(): array
    {
        $manager = new COM('WIA.DeviceManager');
        $devices = [];

        foreach ($manager->DeviceInfos as $info) {
            if ((int) $info->Type !== 1) {
                continue;
            }

            $devices[] = [
                'id' => (string) $info->DeviceID,
                'name' => (string) $info->Properties('Name')->Value,
            ];
        }

        return $devices;
    }

    /**
     * @return array{path: string, mime: string, pages: int}
     */
    public static function scan(array $options = []): array
    {
        $manager = new COM('WIA.DeviceManager');
        $deviceInfo = self::resolveDevice($manager, $options['device'] ?? null);

        if ($deviceInfo === null) {
            throw new RuntimeException('لم يُعثر على ماسح. تحقق من USB وتعريف HP.');
        }

        $device = $deviceInfo->Connect();
        $item = self::resolveScanItem($device);
        $source = strtolower((string) ($options['source'] ?? 'auto'));
        $resolution = max(100, min(300, (int) ($options['resolution'] ?? 120)));
        $quality = max(35, min(75, (int) ($options['quality'] ?? 48)));
        $format = strtolower((string) ($options['format'] ?? 'pdf'));

        if ($format === 'jpeg') {
            $format = 'jpg';
        }

        $rawPages = self::acquirePages($device, $item, $options, $source, $resolution);

        if ($rawPages === []) {
            throw new RuntimeException('لم تُمسح أي صفحة.');
        }

        $optimizedPages = [];

        foreach ($rawPages as $rawPage) {
            $optimizedPages[] = ScanPostProcessor::optimizeJpeg($rawPage, $quality, self::targetWidth($resolution));
            gc_collect_cycles();
        }

        foreach ($rawPages as $rawPage) {
            if (! in_array($rawPage, $optimizedPages, true)) {
                @unlink($rawPage);
            }
        }

        if ($format === 'pdf' || count($optimizedPages) > 1) {
            $pdfPath = ScanPostProcessor::buildPdf($optimizedPages, $resolution);

            foreach ($optimizedPages as $page) {
                @unlink($page);
            }

            return [
                'path' => $pdfPath,
                'mime' => 'application/pdf',
                'pages' => count($optimizedPages),
            ];
        }

        return [
            'path' => $optimizedPages[0],
            'mime' => 'image/jpeg',
            'pages' => 1,
        ];
    }

    /** @return list<string> */
    private static function acquirePages(mixed $device, mixed $item, array $options, string $source, int $resolution): array
    {
        if (in_array($source, ['auto', 'feeder'], true)) {
            $pages = self::scanFromFeeder($device, $item, $options, $resolution);

            if ($pages !== [] || $source === 'feeder') {
                return $pages;
            }
        }

        return self::scanFromFlatbed($device, $item, $options, $resolution);
    }

    /** @return list<string> */
    private static function scanFromFeeder(mixed $device, mixed $item, array $options, int $resolution): array
    {
        self::setDeviceProperty($device, (string) self::WIA_DPS_DOCUMENT_HANDLING_SELECT, 1);
        self::applyProperties($item, $options, $resolution);

        $pages = [];

        for ($index = 0; $index < self::MAX_FEEDER_PAGES; $index++) {
            self::setProperty($item, (string) self::WIA_IPS_PAGES, 1);

            try {
                $image = $item->Transfer(self::WIA_FORMAT_JPEG);
                $path = self::tempPath('jpg');
                $image->SaveFile($path);
                $pages[] = $path;
            } catch (com_exception $exception) {
                if ($index === 0 && self::isPaperEmptyError($exception)) {
                    return [];
                }

                break;
            } catch (Throwable) {
                if ($index === 0) {
                    return [];
                }

                break;
            }
        }

        return $pages;
    }

    /** @return list<string> */
    private static function scanFromFlatbed(mixed $device, mixed $item, array $options, int $resolution): array
    {
        self::setDeviceProperty($device, (string) self::WIA_DPS_DOCUMENT_HANDLING_SELECT, 2);
        self::applyProperties($item, $options, $resolution);
        self::setProperty($item, (string) self::WIA_IPS_PAGES, 1);

        try {
            $image = $item->Transfer(self::WIA_FORMAT_JPEG);
            $path = self::tempPath('jpg');
            $image->SaveFile($path);

            return [$path];
        } catch (com_exception $exception) {
            if (self::isPaperEmptyError($exception)) {
                throw new RuntimeException('لا توجد أوراق. ضعها في الفيدر أو على السطح.');
            }

            throw new RuntimeException($exception->getMessage());
        }
    }

    private static function isPaperEmptyError(com_exception $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'paper empty')
            || str_contains($message, '80210003')
            || str_contains($message, 'no documents')
            || str_contains($message, 'document feeder');
    }

    private static function resolveDevice(COM $manager, ?string $deviceId): mixed
    {
        $fallback = null;

        foreach ($manager->DeviceInfos as $info) {
            if ((int) $info->Type !== 1) {
                continue;
            }

            if ($deviceId !== null && (string) $info->DeviceID === $deviceId) {
                return $info;
            }

            if ($fallback === null) {
                $fallback = $info;
            }
        }

        return $fallback;
    }

    private static function resolveScanItem(mixed $device): mixed
    {
        if ((int) $device->Items->Count >= 1) {
            return $device->Items[1];
        }

        throw new RuntimeException('Scanner has no scannable items.');
    }

    private static function applyProperties(mixed $item, array $options, int $resolution): void
    {
        $mode = strtolower((string) ($options['mode'] ?? 'gray'));

        self::setProperty($item, '6147', $resolution);
        self::setProperty($item, '6148', $resolution);

        if ($mode === 'color') {
            self::setProperty($item, '6146', self::WIA_INTENT_IMAGE_TYPE_COLOR);
        } else {
            self::setProperty($item, '6146', self::WIA_INTENT_IMAGE_TYPE_GRAYSCALE);
        }
    }

    private static function setDeviceProperty(mixed $device, string $propertyId, int $value): void
    {
        try {
            $property = $device->Properties->Item($propertyId);
            $property->Value = $value;
        } catch (Throwable) {
            try {
                if ((int) $device->Items->Count >= 1) {
                    $property = $device->Items[1]->Properties->Item($propertyId);
                    $property->Value = $value;
                }
            } catch (Throwable) {
                // Ignore unsupported properties.
            }
        }
    }

    private static function setProperty(mixed $item, string $propertyId, int $value): void
    {
        try {
            $property = $item->Properties->Item($propertyId);
            $property->Value = $value;
        } catch (Throwable) {
            // Some scanners ignore unsupported properties.
        }
    }

    private static function tempPath(string $extension): string
    {
        $directory = sys_get_temp_dir();

        do {
            $target = $directory.DIRECTORY_SEPARATOR.'arc-scan-'.bin2hex(random_bytes(8)).'.'.$extension;
        } while (file_exists($target));

        return $target;
    }

    private static function targetWidth(int $resolution): int
    {
        return max(900, min(1400, (int) round(8.27 * $resolution)));
    }
}

<?php

declare(strict_types=1);

require_once __DIR__.'/WiaScanner.php';

echo "ARC Scan Agent — Windows test\n\n";

try {
    $devices = WiaScanner::listDevices();

    if ($devices === []) {
        echo "No scanners found.\n";
        exit(1);
    }

    echo "Scanners:\n";

    foreach ($devices as $device) {
        echo " - {$device['name']} ({$device['id']})\n";
    }

    echo "\nScanning (feeder auto, PDF, 200 DPI)...\n";
    echo "Place pages in ADF or on flatbed.\n\n";

    $result = WiaScanner::scan([
        'resolution' => 200,
        'quality' => 78,
        'mode' => 'Gray',
        'source' => 'auto',
        'format' => 'pdf',
    ]);

    $bytes = filesize($result['path']) ?: 0;
    $kb = round($bytes / 1024, 1);

    echo "Pages: {$result['pages']}\n";
    echo "Saved: {$result['path']} ({$result['mime']}, {$kb} KB)\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Error: '.$exception->getMessage().PHP_EOL);
    exit(1);
}

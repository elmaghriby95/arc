<?php

declare(strict_types=1);

@ini_set('memory_limit', '512M');

require_once __DIR__.'/WiaScanner.php';

$payload = json_decode($argv[1] ?? '{}', true);

if (! is_array($payload)) {
    $payload = [];
}

try {
    $result = WiaScanner::scan($payload);

    fwrite(STDOUT, json_encode([
        'ok' => true,
        'path' => $result['path'],
        'mime' => $result['mime'],
        'pages' => $result['pages'],
    ], JSON_UNESCAPED_UNICODE));
} catch (Throwable $exception) {
    fwrite(STDOUT, json_encode([
        'ok' => false,
        'error' => $exception->getMessage(),
    ], JSON_UNESCAPED_UNICODE));
    exit(1);
}

<?php

declare(strict_types=1);

require_once __DIR__.'/WiaScanner.php';
require_once __DIR__.'/ScanPostProcessor.php';
require_once __DIR__.'/PdfBuilder.php';

final class ScanAgentRouter
{
    private const SCAN_TIMEOUT_SECONDS = 180;

    /** @var list<string> */
    private array $allowedOrigins;

    public function __construct()
    {
        $raw = getenv('SCAN_ALLOWED_ORIGINS') ?: '';
        $origins = array_values(array_filter(array_map('trim', explode(',', $raw))));

        $this->allowedOrigins = $origins !== [] ? $origins : [
            'http://localhost',
            'http://127.0.0.1',
            'https://localhost',
            'https://127.0.0.1',
        ];
    }

    public function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $this->sendCorsHeaders();
            http_response_code(204);

            return;
        }

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        try {
            match ($path) {
                '/health' => $this->health(),
                '/devices' => $this->devices(),
                '/scan' => $this->scan(),
                default => $this->notFound(),
            };
        } catch (Throwable $exception) {
            $this->json(['error' => $exception->getMessage()], 500);
        }
    }

    private function health(): void
    {
        $devices = [];

        try {
            $devices = WiaScanner::listDevices();
        } catch (Throwable) {
            // Report unhealthy WIA state below.
        }

        $this->json([
            'ok' => true,
            'platform' => 'windows',
            'wia' => class_exists('COM'),
            'devices_found' => count($devices),
        ]);
    }

    private function devices(): void
    {
        $this->json(['devices' => WiaScanner::listDevices()]);
    }

    private function scan(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json(['error' => 'Method not allowed.'], 405);

            return;
        }

        $payload = json_decode(file_get_contents('php://input') ?: '{}', true);

        if (! is_array($payload)) {
            $payload = [];
        }

        $scanPayload = [
            'device' => $payload['device'] ?? null,
            'resolution' => $payload['resolution'] ?? 120,
            'quality' => $payload['quality'] ?? 48,
            'mode' => $payload['mode'] ?? 'Gray',
            'source' => $payload['source'] ?? 'auto',
            'format' => strtolower((string) ($payload['format'] ?? 'pdf')),
        ];

        $result = $this->runScanWorker($scanPayload);

        if ($result['ok'] !== true) {
            $this->json(['error' => $result['error'] ?? 'Scan failed.'], $result['status'] ?? 500);

            return;
        }

        if (! is_readable($result['path'])) {
            $this->json(['error' => 'Scan output file missing.'], 500);

            return;
        }

        $this->sendCorsHeaders();
        header('Content-Type: '.($result['mime'] ?? 'application/pdf'));
        header('X-Arc-Scan-Pages: '.($result['pages'] ?? 1));
        readfile($result['path']);
        @unlink($result['path']);
    }

    /** @param array<string, mixed> $payload @return array{ok: bool, path?: string, mime?: string, pages?: int, error?: string, status?: int} */
    private function runScanWorker(array $payload): array
    {
        $phpBinary = PHP_BINARY ?: 'php';
        $worker = __DIR__.'/scan-worker.php';
        $command = escapeshellarg($phpBinary).' '.escapeshellarg($worker).' '.escapeshellarg(json_encode($payload));

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, __DIR__);

        if (! is_resource($process)) {
            return ['ok' => false, 'error' => 'Unable to start scan worker.', 'status' => 500];
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $startedAt = time();

        while (true) {
            $stdout .= stream_get_contents($pipes[1]) ?: '';
            $stderr .= stream_get_contents($pipes[2]) ?: '';

            $status = proc_get_status($process);

            if (! $status['running']) {
                break;
            }

            if ((time() - $startedAt) >= self::SCAN_TIMEOUT_SECONDS) {
                proc_terminate($process);
                proc_close($process);

                return [
                    'ok' => false,
                    'error' => 'انتهت مهلة المسح. ضع عدداً أقل من الأوراق في الفيدر وحاول مجدداً.',
                    'status' => 504,
                ];
            }

            usleep(200000);
        }

        $stdout .= stream_get_contents($pipes[1]) ?: '';
        $stderr .= stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        $decoded = json_decode(trim($stdout), true);

        if (is_array($decoded) && ($decoded['ok'] ?? false) === true) {
            return $decoded;
        }

        $message = is_array($decoded) ? ($decoded['error'] ?? null) : null;
        $message = $message ?: trim($stderr) ?: trim($stdout) ?: 'Scan failed.';

        return ['ok' => false, 'error' => $message, 'status' => 500];
    }

    private function notFound(): void
    {
        $this->json(['error' => 'Not found.'], 404);
    }

    /** @param array<string, mixed> $payload */
    private function json(array $payload, int $status = 200): void
    {
        $this->sendCorsHeaders();
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    private function sendCorsHeaders(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;

        if ($origin !== null && $this->originAllowed($origin)) {
            header('Access-Control-Allow-Origin: '.$origin);
            header('Vary: Origin');
        }

        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Access-Control-Allow-Private-Network: true');
    }

    private function originAllowed(string $origin): bool
    {
        if (preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#', $origin) === 1) {
            return true;
        }

        foreach ($this->allowedOrigins as $allowed) {
            if ($origin === $allowed || str_starts_with($origin, rtrim($allowed, '/'))) {
                return true;
            }
        }

        return false;
    }
}

(new ScanAgentRouter())->handle();

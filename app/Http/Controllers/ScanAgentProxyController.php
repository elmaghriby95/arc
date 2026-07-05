<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class ScanAgentProxyController extends Controller
{
    public function health(): Response
    {
        return $this->forward('GET', '/health', acceptsJson: true);
    }

    public function devices(): Response
    {
        return $this->forward('GET', '/devices', acceptsJson: true);
    }

    public function scan(Request $request): Response
    {
        return $this->forward(
            'POST',
            '/scan',
            $request->getContent() ?: '{}',
            $request->header('Content-Type', 'application/json'),
        );
    }

    private function forward(
        string $method,
        string $path,
        ?string $body = null,
        ?string $contentType = null,
        bool $acceptsJson = false,
    ): Response {
        $url = rtrim(config('scan.internal_agent_url'), '/').$path;

        try {
            $pending = Http::timeout((int) config('scan.timeout', 180))
                ->withHeaders(array_filter([
                    'Content-Type' => $contentType,
                    'Accept' => $acceptsJson ? 'application/json' : null,
                ]));

            $response = match (strtoupper($method)) {
                'GET' => $pending->get($url),
                'POST' => $pending->withBody($body ?? '', $contentType ?? 'application/json')->post($url),
                default => null,
            };
        } catch (ConnectionException) {
            return response()->json([
                'error' => 'Scan agent is not running on the server. Install and start arc-scan-agent.',
            ], 503);
        }

        if ($response === null) {
            return response()->json(['error' => 'Unsupported method.'], 405);
        }

        if ($response->failed() && $response->header('Content-Type') !== 'application/pdf') {
            return response()->json(
                $response->json() ?? ['error' => 'Scan failed.'],
                $response->status()
            );
        }

        $headers = array_filter([
            'Content-Type' => $response->header('Content-Type'),
            'X-Arc-Scan-Pages' => $response->header('X-Arc-Scan-Pages'),
        ]);

        return response($response->body(), $response->status(), $headers);
    }
}

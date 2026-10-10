<?php

namespace App\Services;

use App\Models\HanelStorageSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class HanelStorageClient
{
    /** Hänel telegrams use a non-breaking space between command parts (e.g. $U XR). */
    private const TELEGRAM_SEPARATOR = "\xC2\xA0";

    public function __construct(
        private readonly HanelStorageSetting $settings,
    ) {}

    public static function fromSettings(?HanelStorageSetting $settings = null): self
    {
        return new self($settings ?? HanelStorageSetting::instance());
    }

    /** @return array{ok: bool, status: string, message: string, details: array<string, mixed>} */
    public function testConnection(): array
    {
        $details = [
            'http' => null,
            'tcp' => null,
            'soap' => null,
        ];

        $http = $this->pingHttp();
        $details['http'] = $http;

        if (! $http['ok']) {
            return $this->result(false, 'failed', __('messages.hanel.http_unreachable', ['error' => $http['message']]), $details);
        }

        if ($this->settings->protocol === HanelStorageSetting::PROTOCOL_HOST_COM) {
            $tcp = $this->readStatus();
            $details['tcp'] = $tcp;

            if ($tcp['ok']) {
                return $this->result(true, 'connected', __('messages.hanel.tcp_ok'), $details);
            }

            if (($tcp['code'] ?? null) === 'port_closed') {
                return $this->result(
                    false,
                    'partial',
                    __('messages.hanel.tcp_port_closed', ['port' => $this->settings->tcp_port]),
                    $details,
                );
            }

            return $this->result(false, 'partial', $tcp['message'], $details);
        }

        $details['macro'] = ['available' => $this->isMacroServiceAvailable()];
        $details['rest'] = ['available' => $this->isRestServiceAvailable()];

        $soap = $this->pingSoap();
        $details['soap'] = $soap;

        if ($soap['ok']) {
            return $this->result(
                true,
                'connected',
                $details['macro']['available'] || $details['rest']['available']
                    ? __('messages.hanel.soap_ok')
                    : __('messages.hanel.soap_ok_pick_job_fallback'),
                $details,
            );
        }

        return $this->result(
            false,
            'partial',
            __('messages.hanel.soap_unavailable').' '.$soap['message'],
            $details,
        );
    }

    /** @return array{ok: bool, message: string, status_code?: int|null} */
    public function pingHttp(): array
    {
        try {
            $response = Http::timeout($this->settings->timeout_seconds)
                ->withOptions(['verify' => false])
                ->get($this->settings->baseUrl().'/info/home.pc');

            if ($response->successful()) {
                return [
                    'ok' => true,
                    'message' => __('messages.hanel.http_ok'),
                    'status_code' => $response->status(),
                ];
            }

            return [
                'ok' => false,
                'message' => __('messages.hanel.http_bad_status', ['code' => $response->status()]),
                'status_code' => $response->status(),
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'message' => $exception->getMessage(),
                'status_code' => null,
            ];
        }
    }

    /** @return array{ok: bool, message: string, code?: string, status_telegram?: string|null, response_telegram?: string|null, port?: int} */
    public function readStatus(?int $sequence = null): array
    {
        $response = $this->sendMacroViaTcp('read_status', [], $sequence);

        if (($response['code'] ?? null) === 'busy') {
            return [
                'ok' => true,
                'message' => __('messages.hanel.read_status_e01'),
                'status_telegram' => $response['status_telegram'] ?? null,
                'response_telegram' => $response['response_telegram'] ?? null,
                'port' => $response['port'] ?? null,
            ];
        }

        if ($response['ok']) {
            return [
                'ok' => true,
                'message' => __('messages.hanel.read_status_ok'),
                'status_telegram' => $response['status_telegram'] ?? null,
                'response_telegram' => $response['response_telegram'] ?? null,
                'port' => $response['port'] ?? null,
            ];
        }

        return $response;
    }

    /** @return array{ok: bool, status: string, message: string, details: array<string, mixed>} */
    public function getShelfViaTcp(int $shelfNumber, ?int $compartmentNumber = null, ?int $compartmentDepth = null): array
    {
        $compartmentNumber ??= 1;
        $compartmentDepth ??= 1;

        $parameters = [
            'PM01' => $shelfNumber,
            'PM02' => $compartmentNumber,
            'PM03' => $compartmentDepth,
        ];

        $response = $this->sendMacroViaTcp('get_shelf', $parameters);
        $details = array_merge($response, ['parameters' => $parameters, 'shelf_number' => $shelfNumber]);

        if ($response['ok']) {
            return $this->result(
                true,
                'success',
                __('messages.hanel.command_get_shelf_ok', ['shelf' => $shelfNumber]),
                $details,
            );
        }

        if (($response['code'] ?? null) === 'busy') {
            return $this->result(false, 'partial', __('messages.hanel.read_status_e01'), $details);
        }

        return $this->result(
            false,
            'failed',
            __('messages.hanel.hostcom_required'),
            $details,
        );
    }

    /** @param  array<string, int|string>  $parameters */
    /** @return array{ok: bool, message: string, code?: string, status_telegram?: string|null, response_telegram?: string|null, port?: int, macro?: string} */
    private function sendMacroViaTcp(string $macro, array $parameters = [], ?int $sequence = null): array
    {
        $sequence ??= 1;
        $lastResult = [
            'ok' => false,
            'message' => __('messages.hanel.tcp_connect_failed'),
            'macro' => $macro,
        ];

        foreach ($this->tcpPortCandidates($macro) as $port) {
            $result = $this->sendMacroViaTcpOnPort($port, $macro, $sequence, $parameters);
            $lastResult = $result;

            if ($result['ok']) {
                return $result;
            }

            if (in_array($result['code'] ?? '', ['busy', 'macro_error', 'unexpected_response'], true)) {
                return $result;
            }
        }

        return $lastResult;
    }

    /** @return list<int> */
    private function tcpPortCandidates(?string $macro = null): array
    {
        if (in_array($macro, ['get_shelf', 'position_shelf'], true)) {
            return array_values(array_unique(array_filter(
                config('hanel.tcp_hostcom_ports', [2200]),
            )));
        }

        return array_values(array_unique(array_filter([
            ...config('hanel.tcp_port_fallbacks', [3500, 2200]),
            $this->settings->tcp_port,
        ])));
    }

    /** @param  array<string, int|string>  $parameters */
    /** @return array{ok: bool, message: string, code?: string, status_telegram?: string|null, response_telegram?: string|null, port?: int, macro?: string} */
    private function sendMacroViaTcpOnPort(int $port, string $macro, int $sequence, array $parameters): array
    {
        $connectTimeout = config('hanel.tcp_connect_timeout', 1);
        $readTimeout = config('hanel.tcp_read_timeout', 3);
        $previousSocketTimeout = ini_get('default_socket_timeout');
        ini_set('default_socket_timeout', (string) $connectTimeout);

        try {
            $socket = @fsockopen($this->settings->host, $port, $errorCode, $errorMessage, $connectTimeout);

            if ($socket === false) {
                $code = str_contains(strtolower((string) $errorMessage), 'refused') ? 'port_closed' : 'connection_failed';

                return [
                    'ok' => false,
                    'code' => $code,
                    'message' => $errorMessage !== '' ? $errorMessage : __('messages.hanel.tcp_connect_failed'),
                    'port' => $port,
                    'macro' => $macro,
                ];
            }

            stream_set_timeout($socket, $readTimeout);

            $request = $this->buildMacroTelegram($macro, $sequence, $parameters);
            fwrite($socket, $request);

            $buffer = '';
            $deadline = microtime(true) + $readTimeout;

            while (microtime(true) < $deadline) {
                $chunk = fread($socket, 4096);

                if ($chunk === false) {
                    break;
                }

                if ($chunk !== '') {
                    $buffer .= $chunk;
                }

                if ($this->hasCompleteTelegram($buffer)) {
                    break;
                }

                $meta = stream_get_meta_data($socket);

                if ($meta['timed_out'] ?? false) {
                    break;
                }

                if ($chunk === '' && ! ($meta['unread_bytes'] ?? 0)) {
                    usleep(100_000);
                }
            }

            fclose($socket);

            if ($buffer === '') {
                return [
                    'ok' => false,
                    'code' => 'no_response',
                    'message' => __('messages.hanel.tcp_no_response'),
                    'port' => $port,
                    'macro' => $macro,
                ];
            }

            $statusTelegram = $this->extractTelegram($buffer, '$V'.self::TELEGRAM_SEPARATOR.'XS');
            $responseTelegram = $this->extractTelegram($buffer, '$V'.self::TELEGRAM_SEPARATOR.'XA');

            if ($statusTelegram !== null && str_contains($statusTelegram, '$E00')) {
                return [
                    'ok' => true,
                    'message' => __('messages.hanel.tcp_macro_ok', ['macro' => $macro]),
                    'status_telegram' => trim($statusTelegram),
                    'response_telegram' => $responseTelegram !== null ? trim($responseTelegram) : null,
                    'port' => $port,
                    'macro' => $macro,
                ];
            }

            if ($statusTelegram !== null && str_contains($statusTelegram, '$E01')) {
                return [
                    'ok' => false,
                    'code' => 'busy',
                    'message' => __('messages.hanel.read_status_e01'),
                    'status_telegram' => trim($statusTelegram),
                    'response_telegram' => $responseTelegram !== null ? trim($responseTelegram) : null,
                    'port' => $port,
                    'macro' => $macro,
                ];
            }

            if ($statusTelegram !== null && preg_match('/\$E(\d{2})/', $statusTelegram, $match) === 1 && $match[1] !== '00') {
                return [
                    'ok' => false,
                    'code' => 'macro_error',
                    'message' => __('messages.hanel.tcp_macro_error', ['code' => $match[1], 'telegram' => trim($statusTelegram)]),
                    'status_telegram' => trim($statusTelegram),
                    'response_telegram' => $responseTelegram !== null ? trim($responseTelegram) : null,
                    'port' => $port,
                    'macro' => $macro,
                ];
            }

            return [
                'ok' => false,
                'code' => 'unexpected_response',
                'message' => __('messages.hanel.tcp_unexpected_response'),
                'status_telegram' => $statusTelegram,
                'response_telegram' => $responseTelegram,
                'raw' => $this->truncateBody($buffer, 1000),
                'port' => $port,
                'macro' => $macro,
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'code' => 'exception',
                'message' => $exception->getMessage(),
                'port' => $port,
                'macro' => $macro,
            ];
        } finally {
            if ($previousSocketTimeout !== false) {
                ini_set('default_socket_timeout', (string) $previousSocketTimeout);
            }
        }
    }

    /** @return array{ok: bool, message: string, url?: string, tried?: list<string>} */
    public function pingSoap(): array
    {
        $tried = [];
        $lastMessage = __('messages.hanel.soap_not_found', ['url' => '']);

        foreach ($this->settings->soapWsdlCandidates() as $url) {
            $tried[] = $url;

            try {
                $response = Http::timeout($this->settings->timeout_seconds)
                    ->withOptions(['verify' => false])
                    ->get($url);

                if ($response->successful() && str_contains($response->body(), 'wsdl:definitions')) {
                    return [
                        'ok' => true,
                        'message' => __('messages.hanel.soap_ok'),
                        'url' => $url,
                        'tried' => $tried,
                    ];
                }

                $lastMessage = __('messages.hanel.soap_not_found', ['url' => $url]);
            } catch (Throwable $exception) {
                $lastMessage = $exception->getMessage();
            }
        }

        return [
            'ok' => false,
            'message' => $lastMessage,
            'url' => $tried !== [] ? $tried[array_key_last($tried)] : null,
            'tried' => $tried,
        ];
    }

    /** @return array{ok: bool, status: string, message: string, details: array<string, mixed>} */
    public function executeCommand(string $command, array $params = []): array
    {
        return match ($command) {
            'read_articles' => $this->readAllArticles(),
            'read_status' => $this->readStatusV02(),
            'get_shelf' => $this->moveShelf(
                (int) ($params['shelf_number'] ?? $this->settings->lift_number),
                isset($params['compartment_number']) ? (int) $params['compartment_number'] : null,
                isset($params['compartment_depth']) ? (int) $params['compartment_depth'] : null,
                isset($params['article_number']) ? (string) $params['article_number'] : null,
            ),
            'send_pick_job' => $this->sendPickJob(
                (string) ($params['article_number'] ?? ''),
                (string) ($params['operation'] ?? '+'),
                (string) ($params['quantity'] ?? '1'),
                isset($params['job_number']) ? (string) $params['job_number'] : null,
            ),
            default => $this->result(false, 'failed', __('messages.hanel.command_unknown'), ['command' => $command]),
        };
    }

    /** @return array{ok: bool, status: string, message: string, details: array<string, mixed>} */
    public function readAllArticles(): array
    {
        $ns = config('hanel.soap_namespaces.com');
        $response = $this->postSoap(
            $this->comSoapEndpoint(),
            '<ns:readAllAMDReqV01 xmlns:ns="'.htmlspecialchars($ns, ENT_XML1).'"/>',
        );

        if (! $response['ok']) {
            return $this->result(false, 'failed', $response['message'], $response);
        }

        $parsed = $response['parsed'];

        if ((int) ($parsed['returnValue'] ?? -1) !== 0) {
            $articles = $this->parseArticlesFromSoapBody($response['body'] ?? '');

            if ($articles !== []) {
                return $this->result(
                    true,
                    'success',
                    __('messages.hanel.command_read_articles_ok', ['count' => count($articles)]),
                    array_merge($response, ['articles' => array_slice($articles, 0, 10), 'article_count' => count($articles)]),
                );
            }

            return $this->result(
                false,
                'failed',
                $this->formatReturnValueMessage($parsed),
                $response,
            );
        }

        $articles = $this->parseArticlesFromSoapBody($response['body'] ?? '');

        return $this->result(
            true,
            'success',
            __('messages.hanel.command_read_articles_ok', ['count' => count($articles)]),
            array_merge($response, ['articles' => array_slice($articles, 0, 10), 'article_count' => count($articles)]),
        );
    }

    /** @return array{ok: bool, status: string, message: string, details: array<string, mixed>} */
    public function readStatusV02(): array
    {
        $ns = config('hanel.soap_namespaces.macro');
        $response = $this->postSoap(
            $this->macroSoapEndpoint(),
            '<q0:readStatusV02 xmlns:q0="'.htmlspecialchars($ns, ENT_XML1).'"/>',
        );

        if (! $response['ok']) {
            if ($this->isMacroServiceFailure($response)) {
                return $this->result(false, 'failed', __('messages.hanel.macro_unavailable'), $response);
            }

            return $this->result(false, 'failed', $response['message'], $response);
        }

        $parsed = $response['parsed'];
        $returnValue = $parsed['returnValue'] ?? null;

        if ($returnValue === 1) {
            return $this->result(
                false,
                'partial',
                __('messages.hanel.read_status_e01'),
                $response,
            );
        }

        if ($returnValue !== 0) {
            return $this->result(false, 'failed', $this->formatReturnValueMessage($parsed), $response);
        }

        return $this->result(
            true,
            'success',
            __('messages.hanel.command_read_status_ok', [
                'lift' => $parsed['liftNumber'] ?? '?',
                'shelf' => $parsed['shelfInAccess'] ?? '?',
            ]),
            $response,
        );
    }

    /** @return array{ok: bool, status: string, message: string, details: array<string, mixed>} */
    public function getShelf(int $shelfNumber, ?int $compartmentNumber = null, ?int $compartmentDepth = null): array
    {
        $ns = config('hanel.soap_namespaces.macro');
        $xsd = config('hanel.soap_namespaces.macro_xsd');

        $params = '<q1:pm01_shelfNumber xmlns:q1="'.htmlspecialchars($xsd, ENT_XML1).'">'.$shelfNumber.'</q1:pm01_shelfNumber>';

        if ($compartmentNumber !== null) {
            $params .= '<q1:pm02_compartmentNumber xmlns:q1="'.htmlspecialchars($xsd, ENT_XML1).'">'.$compartmentNumber.'</q1:pm02_compartmentNumber>';
        }

        if ($compartmentDepth !== null) {
            $params .= '<q1:pm03_compartmentDepthNumber xmlns:q1="'.htmlspecialchars($xsd, ENT_XML1).'">'.$compartmentDepth.'</q1:pm03_compartmentDepthNumber>';
        }

        $body = '<q0:get_shelf xmlns:q0="'.htmlspecialchars($ns, ENT_XML1).'"><q0:param>'.$params.'</q0:param></q0:get_shelf>';

        $response = $this->postSoap($this->macroSoapEndpoint(), $body);

        if (! $response['ok']) {
            if ($this->isMacroServiceFailure($response)) {
                return $this->result(false, 'failed', __('messages.hanel.macro_unavailable'), $response);
            }

            return $this->result(false, 'failed', $response['message'], $response);
        }

        $parsed = $response['parsed'];
        $returnValue = $parsed['returnValue'] ?? null;

        if ($returnValue === 1) {
            return $this->result(false, 'partial', __('messages.hanel.read_status_e01'), $response);
        }

        if ((int) ($parsed['returnValue'] ?? -1) !== 0) {
            return $this->result(false, 'failed', $this->formatReturnValueMessage($parsed), $response);
        }

        return $this->result(
            true,
            'success',
            __('messages.hanel.command_get_shelf_ok', ['shelf' => $shelfNumber]),
            $response,
        );
    }

    /** @return array{ok: bool, status: string, message: string, details: array<string, mixed>} */
    public function moveShelf(int $shelfNumber, ?int $compartmentNumber = null, ?int $compartmentDepth = null, ?string $articleNumber = null): array
    {
        if ($this->isMacroServiceAvailable()) {
            return $this->getShelf($shelfNumber, $compartmentNumber, $compartmentDepth);
        }

        $rest = $this->getShelfViaRest($shelfNumber, $compartmentNumber, $compartmentDepth);

        if ($rest['ok'] || ($rest['details']['code'] ?? null) !== 'service_unavailable') {
            return $rest;
        }

        if ($this->settings->protocol === HanelStorageSetting::PROTOCOL_HOST_COM) {
            $tcp = $this->getShelfViaTcp($shelfNumber, $compartmentNumber, $compartmentDepth);

            if ($tcp['ok']) {
                return $tcp;
            }
        }

        return $this->moveShelfViaPickJob($shelfNumber, $articleNumber);
    }

    /** @return array{ok: bool, status: string, message: string, details: array<string, mixed>} */
    public function moveShelfViaPickJob(int $shelfNumber, ?string $articleNumber = null): array
    {
        $articleNumber = $articleNumber !== null && trim($articleNumber) !== ''
            ? trim($articleNumber)
            : $this->findFirstArticleOnShelf($shelfNumber);

        if ($articleNumber === null) {
            return $this->result(
                false,
                'failed',
                __('messages.hanel.no_article_on_shelf', ['shelf' => $shelfNumber]),
                ['shelf_number' => $shelfNumber, 'method' => 'pick_job'],
            );
        }

        $jobNumber = $this->generateShortJobNumber();
        $result = $this->sendPickJob($articleNumber, '+', '1', $jobNumber);

        if (! $result['ok']) {
            return $result;
        }

        return $this->result(
            true,
            'pending',
            __('messages.hanel.command_move_shelf_via_job_ok', [
                'shelf' => $shelfNumber,
                'article' => $articleNumber,
                'job' => $jobNumber,
            ]),
            array_merge($result['details'] ?? [], [
                'method' => 'pick_job',
                'shelf_number' => $shelfNumber,
                'article_number' => $articleNumber,
                'job_number' => $jobNumber,
            ]),
        );
    }

    public function isMacroServiceAvailable(): bool
    {
        try {
            $response = Http::timeout(min(3, $this->settings->timeout_seconds))
                ->withOptions(['verify' => false])
                ->get($this->settings->baseUrl().'/jwsmacro/services/Macro?wsdl');

            return $response->successful() && str_contains($response->body(), 'wsdl:definitions');
        } catch (Throwable) {
            return false;
        }
    }

    public function isRestServiceAvailable(): bool
    {
        try {
            $response = Http::timeout(min(3, $this->settings->timeout_seconds))
                ->withOptions(['verify' => false])
                ->get($this->settings->baseUrl().'/jrs/openapi.json');

            return $response->successful() && str_contains($response->body(), 'Haenel REST API');
        } catch (Throwable) {
            return false;
        }
    }

    /** @return array{ok: bool, status: string, message: string, details: array<string, mixed>} */
    public function getShelfViaRest(int $shelfNumber, ?int $compartmentNumber = null, ?int $compartmentDepth = null): array
    {
        if (! $this->isRestServiceAvailable()) {
            return $this->result(false, 'failed', __('messages.hanel.lift_service_required'), [
                'code' => 'service_unavailable',
                'transport' => 'rest',
            ]);
        }

        $payload = ['shelfNumber' => $shelfNumber];

        if ($compartmentNumber !== null) {
            $payload['compartmentNumber'] = $compartmentNumber;
        }

        if ($compartmentDepth !== null) {
            $payload['compartmentDepthNumber'] = $compartmentDepth;
        }

        try {
            $response = Http::timeout(max(30, $this->settings->timeout_seconds))
                ->withOptions(['verify' => false])
                ->acceptJson()
                ->put($this->settings->baseUrl().'/jrs/shelfs/'.$shelfNumber, $payload);

            if ($response->failed()) {
                return $this->result(false, 'failed', __('messages.hanel.rest_call_failed', [
                    'code' => $response->status(),
                ]), [
                    'transport' => 'rest',
                    'status_code' => $response->status(),
                    'body' => $this->truncateBody($response->body()),
                ]);
            }

            $body = $response->json();
            $data = is_array($body['data'] ?? null) ? $body['data'] : [];
            $returnValue = (int) ($data['returnValue'] ?? $body['code'] ?? -1);

            if ($returnValue === 1) {
                return $this->result(false, 'partial', __('messages.hanel.read_status_e01'), [
                    'transport' => 'rest',
                    'response' => $body,
                ]);
            }

            if ($returnValue !== 0 && $returnValue !== 200) {
                return $this->result(false, 'failed', __('messages.hanel.soap_return_code', ['code' => $returnValue]), [
                    'transport' => 'rest',
                    'response' => $body,
                ]);
            }

            return $this->result(
                true,
                'success',
                __('messages.hanel.command_get_shelf_ok', ['shelf' => $shelfNumber]),
                ['transport' => 'rest', 'response' => $body],
            );
        } catch (Throwable $exception) {
            return $this->result(false, 'failed', $exception->getMessage(), [
                'transport' => 'rest',
            ]);
        }
    }

    /** @param  array<string, mixed>  $response */
    private function isMacroServiceFailure(array $response): bool
    {
        if (($response['status_code'] ?? null) === 404) {
            return true;
        }

        $message = strtolower((string) ($response['message'] ?? ''));
        $endpoint = strtolower((string) ($response['endpoint'] ?? ''));

        if ($endpoint !== '' && str_contains($endpoint, 'jwsmacro')) {
            return str_contains($message, 'timed out')
                || str_contains($message, 'timeout')
                || str_contains($message, 'curl error 28')
                || str_contains($message, 'connection refused')
                || str_contains($message, 'could not resolve')
                || str_contains($message, 'failed to connect');
        }

        return str_contains($message, 'timed out')
            || str_contains($message, 'timeout')
            || str_contains($message, 'curl error 28')
            || str_contains($message, '404');
    }

    private function findFirstArticleOnShelf(int $shelfNumber): ?string
    {
        foreach ($this->fetchArticlesFromUnit() as $article) {
            if ($this->articleShelfNumber($article) === $shelfNumber) {
                $number = trim((string) ($article['articleNumber'] ?? $article['articleNo'] ?? ''));

                if ($number !== '') {
                    return $number;
                }
            }
        }

        return null;
    }

    /** @param  array<string, string>  $article */
    private function articleShelfNumber(array $article): ?int
    {
        foreach (['shelfNumber', 'shelfNo', 'storageShelfNumber', 'shelf'] as $field) {
            if (isset($article[$field]) && $article[$field] !== '') {
                return (int) $article[$field];
            }
        }

        return null;
    }

    /** @return list<array<string, string>> */
    private function fetchArticlesFromUnit(): array
    {
        $ns = config('hanel.soap_namespaces.com');
        $response = $this->postSoap(
            $this->comSoapEndpoint(),
            '<ns:readAllAMDReqV01 xmlns:ns="'.htmlspecialchars($ns, ENT_XML1).'"/>',
        );

        if (! $response['ok']) {
            return [];
        }

        $articles = $this->parseArticlesFromSoapBody($response['body'] ?? '');

        if ($articles !== []) {
            return $articles;
        }

        if ((int) ($response['parsed']['returnValue'] ?? -1) !== 0) {
            return [];
        }

        return $articles;
    }

    /** @return list<array<string, string>> */
    private function parseArticlesFromSoapBody(string $xml): array
    {
        foreach (['article', 'AMD', 'Article'] as $tag) {
            $items = $this->parseXmlElements($xml, $tag);

            if ($items !== []) {
                return $items;
            }
        }

        return $this->parseArticlePairsFromXml($xml);
    }

    /** @return list<array<string, string>> */
    private function parseArticlePairsFromXml(string $xml): array
    {
        preg_match_all(
            '/<(?:[\w]+:)?articleNumber>([^<]*)<\/(?:[\w]+:)?articleNumber>.*?<(?:[\w]+:)?shelfNumber>([^<]*)<\/(?:[\w]+:)?shelfNumber>/s',
            $xml,
            $matches,
            PREG_SET_ORDER,
        );

        $items = [];

        foreach ($matches as $match) {
            $items[] = [
                'articleNumber' => html_entity_decode($match[1], ENT_XML1),
                'shelfNumber' => html_entity_decode($match[2], ENT_XML1),
            ];
        }

        return $items;
    }

    /** @return list<array<string, string>> */
    public function fetchArticlesFromUnitPublic(): array
    {
        return $this->fetchArticlesFromUnit();
    }

    /** @return array{ok: bool, status: string, message: string, details: array<string, mixed>} */
    public function syncStorageArticle(
        string $articleNumber,
        string $articleName,
        int $shelfNumber,
        ?int $compartmentNumber = null,
        ?int $compartmentDepth = null,
    ): array {
        $existing = $this->findUnitArticle($articleNumber);

        if ($existing !== null) {
            $unitShelf = (int) ($existing['shelfNumber'] ?? 0);
            $unitCompartment = (int) ($existing['compartmentNumber'] ?? 0);
            $unitDepth = (int) ($existing['compartmentDepthNumber'] ?? 1);

            return $this->result(
                true,
                'success',
                __('messages.hanel.article_linked_on_unit', [
                    'article' => $articleNumber,
                    'shelf' => $unitShelf,
                ]),
                [
                    'unit_article' => $existing,
                    'method' => 'verify',
                    'unit_shelf' => $unitShelf,
                    'unit_compartment' => $unitCompartment,
                    'unit_depth' => $unitDepth,
                ],
            );
        }

        $write = $this->registerStorageArticle(
            $articleNumber,
            $articleName,
            $shelfNumber,
            $compartmentNumber,
            $compartmentDepth,
        );

        if ($write['ok']) {
            return $write;
        }

        if ($this->isHostWriteBlockedError($write)) {
            return $this->result(
                false,
                'failed',
                __('messages.hanel.article_must_exist_on_unit', ['article' => $articleNumber]),
                $write['details'] ?? [],
            );
        }

        return $write;
    }

    public function articleExistsOnUnit(string $articleNumber): bool
    {
        return $this->findUnitArticle($articleNumber) !== null;
    }

    /** @return list<string> */
    public function listUnitArticleNumbers(): array
    {
        return array_values(array_map(
            fn (array $article) => (string) ($article['articleNumber'] ?? ''),
            $this->fetchArticlesFromUnitPublic(),
        ));
    }

    /** @return array<string, string>|null */
    public function findUnitArticle(string $articleNumber): ?array
    {
        $articleNumber = trim($articleNumber);

        foreach ($this->fetchArticlesFromUnit() as $article) {
            if (($article['articleNumber'] ?? '') === $articleNumber) {
                return $article;
            }
        }

        return null;
    }

    /** @return array{ok: bool, status: string, message: string, details: array<string, mixed>} */
    public function registerStorageArticle(
        string $articleNumber,
        string $articleName,
        int $shelfNumber,
        ?int $compartmentNumber = null,
        ?int $compartmentDepth = null,
        float $quantity = 1,
    ): array {
        $articleNumber = trim($articleNumber);
        $articleName = mb_substr(trim($articleName), 0, 40);

        if ($articleNumber === '' || ! preg_match('/^[A-Za-z0-9]+$/', $articleNumber)) {
            return $this->result(false, 'failed', __('messages.hanel.article_number_invalid'), []);
        }

        if ($articleName === '') {
            $articleName = $articleNumber;
        }

        $master = $this->sendArticleMasterData($articleNumber, $articleName);

        if (! $master['ok'] && ! $this->isApdNotAvailableError($master) && ! $this->isDuplicateArticleError($master)) {
            return $master;
        }

        return $this->sendArticleLocationData(
            $articleNumber,
            $articleName,
            $shelfNumber,
            $compartmentNumber,
            $compartmentDepth,
            $quantity,
        );
    }

    /** @return array{ok: bool, status: string, message: string, details: array<string, mixed>} */
    private function sendArticleMasterData(string $articleNumber, string $articleName): array
    {
        $lastResponse = null;

        foreach ($this->buildSendApdRequestBodies($articleNumber, $articleName) as $variant => $body) {
            $response = $this->postSoap($this->comSoapEndpoint(), $body, true);
            $lastResponse = array_merge($response, ['soap_variant' => $variant]);

            if (! $response['ok']) {
                if (! $this->isRetryableSendJobsFault($response)) {
                    break;
                }

                continue;
            }

            if ((int) ($response['parsed']['returnValue'] ?? -1) === 0) {
                return $this->result(true, 'success', __('messages.hanel.article_master_synced'), $lastResponse);
            }

            if ($this->isRetryableApplicationError($lastResponse['parsed'] ?? [])) {
                continue;
            }

            break;
        }

        if ($lastResponse === null) {
            return $this->result(false, 'failed', __('messages.hanel.article_sync_failed'), []);
        }

        if (($lastResponse['ok'] ?? false) && isset($lastResponse['parsed'])) {
            return $this->result(false, 'failed', $this->formatReturnValueMessage($lastResponse['parsed']), $lastResponse);
        }

        return $this->result(false, 'failed', $lastResponse['message'] ?? __('messages.hanel.article_sync_failed'), $lastResponse);
    }

    /** @return array{ok: bool, status: string, message: string, details: array<string, mixed>} */
    private function sendArticleLocationData(
        string $articleNumber,
        string $articleName,
        int $shelfNumber,
        ?int $compartmentNumber,
        ?int $compartmentDepth,
        float $quantity,
    ): array {
        $lastResponse = null;
        $containerSize = $this->guessContainerSizeForShelf($shelfNumber);

        foreach ($this->buildSendAmdRequestBodies(
            $articleNumber,
            $articleName,
            $shelfNumber,
            $compartmentNumber,
            $compartmentDepth,
            $quantity,
            $containerSize,
        ) as $variant => $body) {
            $response = $this->postSoap($this->comSoapEndpoint(), $body, true);
            $lastResponse = array_merge($response, ['soap_variant' => $variant]);

            if (! $response['ok']) {
                if (! $this->isRetryableSendJobsFault($response)) {
                    break;
                }

                continue;
            }

            if ((int) ($response['parsed']['returnValue'] ?? -1) === 0) {
                return $this->result(
                    true,
                    'success',
                    __('messages.hanel.article_location_synced', ['article' => $articleNumber, 'shelf' => $shelfNumber]),
                    $lastResponse,
                );
            }

            if ($this->isRetryableApplicationError($lastResponse['parsed'] ?? [])) {
                continue;
            }

            break;
        }

        foreach ($this->buildMergedAmdRequestBodies(
            $articleNumber,
            $articleName,
            $shelfNumber,
            $compartmentNumber,
            $compartmentDepth,
            $quantity,
            $containerSize,
        ) as $variant => $body) {
            $response = $this->postSoap($this->comSoapEndpoint(), $body, true);
            $lastResponse = array_merge($response, ['soap_variant' => $variant]);

            if (! $response['ok']) {
                if (! $this->isRetryableSendJobsFault($response)) {
                    break;
                }

                continue;
            }

            if ((int) ($response['parsed']['returnValue'] ?? -1) === 0) {
                return $this->result(
                    true,
                    'success',
                    __('messages.hanel.article_location_synced', ['article' => $articleNumber, 'shelf' => $shelfNumber]),
                    $lastResponse,
                );
            }

            break;
        }

        if ($lastResponse === null) {
            return $this->result(false, 'failed', __('messages.hanel.article_sync_failed'), []);
        }

        if (($lastResponse['ok'] ?? false) && isset($lastResponse['parsed'])) {
            return $this->result(false, 'failed', $this->formatReturnValueMessage($lastResponse['parsed']), $lastResponse);
        }

        return $this->result(false, 'failed', $lastResponse['message'] ?? __('messages.hanel.article_sync_failed'), $lastResponse);
    }

    /** @return array<string, string> */
    private function buildSendApdRequestBodies(string $articleNumber, string $articleName): array
    {
        $ns = config('hanel.soap_namespaces.com');
        $xsd = config('hanel.soap_namespaces.com_xsd');
        $articleNumberXml = htmlspecialchars($articleNumber, ENT_XML1);
        $articleNameXml = htmlspecialchars($articleName, ENT_XML1);

        return [
            'apd_req_v1' => <<<XML
<ns:sendAPDReqV01 xmlns:ns="{$ns}" xmlns:xsd="{$xsd}">
  <ns:param>
    <xsd:articlePoolDataRecord>
      <xsd:articleNumber>{$articleNumberXml}</xsd:articleNumber>
      <xsd:articleName>{$articleNameXml}</xsd:articleName>
    </xsd:articlePoolDataRecord>
  </ns:param>
</ns:sendAPDReqV01>
XML,
            'apd_req_v1_default_ns' => <<<XML
<ns:sendAPDReqV01 xmlns:ns="{$ns}">
  <ns:param>
    <articlePoolDataRecord xmlns="{$xsd}">
      <articleNumber>{$articleNumberXml}</articleNumber>
      <articleName>{$articleNameXml}</articleName>
    </articlePoolDataRecord>
  </ns:param>
</ns:sendAPDReqV01>
XML,
        ];
    }

    /** @return array<string, string> */
    private function buildSendAmdRequestBodies(
        string $articleNumber,
        string $articleName,
        int $shelfNumber,
        ?int $compartmentNumber,
        ?int $compartmentDepth,
        float $quantity,
        int $containerSize = 101,
    ): array {
        $ns = config('hanel.soap_namespaces.com');
        $xsd = config('hanel.soap_namespaces.com_xsd');
        $articleNumberXml = htmlspecialchars($articleNumber, ENT_XML1);
        $articleNameXml = htmlspecialchars($articleName, ENT_XML1);
        $liftNumber = (int) $this->settings->lift_number;
        $compartmentNumber = max(1, (int) ($compartmentNumber ?? 1));
        $compartmentDepth = max(1, (int) ($compartmentDepth ?? 1));
        $quantityXml = htmlspecialchars((string) max(1, (int) round($quantity)), ENT_XML1);

        $articleBlock = <<<XML
      <xsd:articleNumber>{$articleNumberXml}</xsd:articleNumber>
      <xsd:articleName>{$articleNameXml}</xsd:articleName>
      <xsd:liftNumber>{$liftNumber}</xsd:liftNumber>
      <xsd:shelfNumber>{$shelfNumber}</xsd:shelfNumber>
      <xsd:compartmentNumber>{$compartmentNumber}</xsd:compartmentNumber>
      <xsd:compartmentDepthNumber>{$compartmentDepth}</xsd:compartmentDepthNumber>
      <xsd:containerSize>{$containerSize}</xsd:containerSize>
      <xsd:fifo>1</xsd:fifo>
      <xsd:inventoryAtStorageLocation>{$quantityXml}</xsd:inventoryAtStorageLocation>
      <xsd:minimumInventory>0</xsd:minimumInventory>
XML;

        $articleBlockDefaultNs = <<<XML
      <articleNumber>{$articleNumberXml}</articleNumber>
      <articleName>{$articleNameXml}</articleName>
      <liftNumber>{$liftNumber}</liftNumber>
      <shelfNumber>{$shelfNumber}</shelfNumber>
      <compartmentNumber>{$compartmentNumber}</compartmentNumber>
      <compartmentDepthNumber>{$compartmentDepth}</compartmentDepthNumber>
      <containerSize>{$containerSize}</containerSize>
      <fifo>1</fifo>
      <inventoryAtStorageLocation>{$quantityXml}</inventoryAtStorageLocation>
      <minimumInventory>0</minimumInventory>
XML;

        return [
            'amd_all_req_v1' => <<<XML
<ns:sendAllAMDReqV01 xmlns:ns="{$ns}" xmlns:xsd="{$xsd}">
  <ns:param>
    <xsd:article>
{$articleBlock}
    </xsd:article>
  </ns:param>
</ns:sendAllAMDReqV01>
XML,
            'amd_all_req_v1_default_ns' => <<<XML
<ns:sendAllAMDReqV01 xmlns:ns="{$ns}">
  <ns:param>
    <article xmlns="{$xsd}">
{$articleBlockDefaultNs}
    </article>
  </ns:param>
</ns:sendAllAMDReqV01>
XML,
        ];
    }

    /** @return array<string, string> */
    private function buildMergedAmdRequestBodies(
        string $articleNumber,
        string $articleName,
        int $shelfNumber,
        ?int $compartmentNumber,
        ?int $compartmentDepth,
        float $quantity,
        int $containerSize,
    ): array {
        $articles = $this->fetchArticlesFromUnitPublic();
        $knownNumbers = array_map(fn (array $a) => (string) ($a['articleNumber'] ?? ''), $articles);

        if (! in_array($articleNumber, $knownNumbers, true)) {
            $articles[] = [
                'articleNumber' => $articleNumber,
                'articleName' => $articleName,
                'liftNumber' => (string) $this->settings->lift_number,
                'shelfNumber' => (string) $shelfNumber,
                'compartmentNumber' => (string) max(1, (int) ($compartmentNumber ?? 1)),
                'compartmentDepthNumber' => (string) max(1, (int) ($compartmentDepth ?? 1)),
                'containerSize' => (string) $containerSize,
                'fifo' => '1',
                'inventoryAtStorageLocation' => (string) max(1, (int) round($quantity)),
                'minimumInventory' => '0',
            ];
        }

        $ns = config('hanel.soap_namespaces.com');
        $xsd = config('hanel.soap_namespaces.com_xsd');
        $blocks = '';
        $blocksDefault = '';

        foreach ($articles as $article) {
            $blocks .= '<xsd:article>'.$this->amdArticleXml($article, 'xsd:').'</xsd:article>';
            $blocksDefault .= '<article xmlns="'.$xsd.'">'.$this->amdArticleXml($article, '').'</article>';
        }

        return [
            'amd_merged_v1' => <<<XML
<ns:sendAllAMDReqV01 xmlns:ns="{$ns}" xmlns:xsd="{$xsd}">
  <ns:param>
{$blocks}
  </ns:param>
</ns:sendAllAMDReqV01>
XML,
            'amd_merged_v1_default_ns' => <<<XML
<ns:sendAllAMDReqV01 xmlns:ns="{$ns}">
  <ns:param>
{$blocksDefault}
  </ns:param>
</ns:sendAllAMDReqV01>
XML,
        ];
    }

    /** @param  array<string, string>  $article */
    private function amdArticleXml(array $article, string $prefix): string
    {
        $xml = '';
        $fields = [
            'articleNumber', 'articleName', 'liftNumber', 'shelfNumber',
            'compartmentNumber', 'compartmentDepthNumber', 'containerSize',
            'fifo', 'inventoryAtStorageLocation', 'minimumInventory',
        ];

        foreach ($fields as $field) {
            $value = $article[$field] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $tag = $prefix.$field;
            $xml .= '<'.$tag.'>'.htmlspecialchars((string) $value, ENT_XML1).'</'.$tag.'>';
        }

        return $xml;
    }

    private function guessContainerSizeForShelf(int $shelfNumber): int
    {
        foreach ($this->fetchArticlesFromUnitPublic() as $article) {
            if ((int) ($article['shelfNumber'] ?? 0) === $shelfNumber) {
                $size = (int) ($article['containerSize'] ?? 0);

                if ($size > 0) {
                    return $size;
                }
            }
        }

        return 101;
    }

    /** @return array{ok: bool, status: string, message: string, details: array<string, mixed>} */
    public function sendPickJob(string $articleNumber, string $operation = '+', string $quantity = '1', ?string $jobNumber = null): array
    {
        $articleNumber = trim($articleNumber);

        if ($articleNumber === '') {
            return $this->result(false, 'failed', __('messages.hanel.command_article_required'), []);
        }

        $jobNumber = $this->normalizeJobNumber($jobNumber) ?? $this->generateShortJobNumber();
        $lastResponse = null;

        foreach ($this->buildSendJobsRequestBodies($jobNumber, $articleNumber, $operation, $quantity) as $variant => $body) {
            $response = $this->postSoap($this->comSoapEndpoint(), $body);
            $lastResponse = array_merge($response, ['soap_variant' => $variant]);

            if (! $response['ok']) {
                if (! $this->isRetryableSendJobsFault($response)) {
                    break;
                }

                continue;
            }

            $parsed = $response['parsed'];

            if ((int) ($parsed['returnValue'] ?? -1) === 0) {
                return $this->result(
                    true,
                    'success',
                    __('messages.hanel.command_send_job_ok', ['job' => $jobNumber, 'article' => $articleNumber]),
                    array_merge($lastResponse, ['job_number' => $jobNumber]),
                );
            }

            break;
        }

        if ($lastResponse === null) {
            return $this->result(false, 'failed', __('messages.hanel.command_send_job_failed'), []);
        }

        if (($lastResponse['ok'] ?? false) && isset($lastResponse['parsed'])) {
            return $this->result(
                false,
                'failed',
                $this->formatReturnValueMessage($lastResponse['parsed']),
                $lastResponse,
            );
        }

        return $this->result(false, 'failed', $lastResponse['message'] ?? __('messages.hanel.command_send_job_failed'), $lastResponse);
    }

    /** @return array<string, string> */
    private function buildSendJobsRequestBodies(string $jobNumber, string $articleNumber, string $operation, string $quantity): array
    {
        $ns = config('hanel.soap_namespaces.com');
        $xsd = config('hanel.soap_namespaces.com_xsd');

        $articleXml = htmlspecialchars($articleNumber, ENT_XML1);
        $operationXml = htmlspecialchars($operation, ENT_XML1);
        $quantityXml = htmlspecialchars($quantity, ENT_XML1);
        $jobNumberXml = htmlspecialchars($jobNumber, ENT_XML1);

        return [
            'v2_prefixed' => <<<XML
<ns:sendJobsReqV02 xmlns:ns="{$ns}" xmlns:xsd="{$xsd}">
  <ns:param>
    <xsd:job>
      <xsd:jobNumber>{$jobNumberXml}</xsd:jobNumber>
      <xsd:JobPosition>
        <xsd:articleNumber>{$articleXml}</xsd:articleNumber>
        <xsd:operation>{$operationXml}</xsd:operation>
        <xsd:nominalQuantity>{$quantityXml}</xsd:nominalQuantity>
      </xsd:JobPosition>
    </xsd:job>
  </ns:param>
</ns:sendJobsReqV02>
XML,
            'v2_default_ns' => <<<XML
<ns:sendJobsReqV02 xmlns:ns="{$ns}">
  <ns:param>
    <job xmlns="{$xsd}">
      <jobNumber>{$jobNumberXml}</jobNumber>
      <JobPosition>
        <articleNumber>{$articleXml}</articleNumber>
        <operation>{$operationXml}</operation>
        <nominalQuantity>{$quantityXml}</nominalQuantity>
      </JobPosition>
    </job>
  </ns:param>
</ns:sendJobsReqV02>
XML,
            'v2_lowercase_position' => <<<XML
<ns:sendJobsReqV02 xmlns:ns="{$ns}" xmlns:xsd="{$xsd}">
  <ns:param>
    <xsd:job>
      <xsd:jobNumber>{$jobNumberXml}</xsd:jobNumber>
      <xsd:jobPosition>
        <xsd:articleNumber>{$articleXml}</xsd:articleNumber>
        <xsd:operation>{$operationXml}</xsd:operation>
        <xsd:nominalQuantity>{$quantityXml}</xsd:nominalQuantity>
      </xsd:jobPosition>
    </xsd:job>
  </ns:param>
</ns:sendJobsReqV02>
XML,
        ];
    }

    /** @param  array<string, mixed>  $response */
    private function isRetryableSendJobsFault(array $response): bool
    {
        $message = strtolower((string) ($response['message'] ?? ''));

        return str_contains($message, 'unexpected subelement')
            || str_contains($message, 'unexpected element')
            || str_contains($message, 'soap fault')
            || str_contains($message, 'soap_call_failed')
            || (($response['status_code'] ?? null) === 500);
    }

    /** @param  array<string, mixed>  $parsed */
    private function isRetryableApplicationError(array $parsed): bool
    {
        return false;
    }

    /** @param  array{ok: bool, status: string, message: string, details?: array<string, mixed>}  $result */
    private function isDuplicateArticleError(array $result): bool
    {
        $message = strtolower($result['message'] ?? '');
        $details = strtolower(json_encode($result['details'] ?? []));

        return str_contains($message, 'exist')
            || str_contains($details, 'exist')
            || (($result['details']['parsed']['returnValue'] ?? null) === '2');
    }

    public function comSoapEndpoint(): string
    {
        return $this->settings->baseUrl().config('hanel.soap_endpoints.com');
    }

    public function macroSoapEndpoint(): string
    {
        return $this->settings->baseUrl().config('hanel.soap_endpoints.macro');
    }

    /** @return array{ok: bool, message: string, status_code?: int|null, body?: string, parsed?: array<string, mixed>, endpoint?: string} */
    private function postSoap(string $endpoint, string $bodyInner, bool $extendedTimeout = false): array
    {
        $envelope = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">'
            .'<soapenv:Body>'.$bodyInner.'</soapenv:Body>'
            .'</soapenv:Envelope>';

        $timeout = $this->soapTimeout($extendedTimeout);

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout(min(10, $timeout))
                ->withOptions(['verify' => false])
                ->withHeaders(['Content-Type' => 'text/xml; charset=utf-8'])
                ->withBody($envelope, 'text/xml; charset=utf-8')
                ->post($endpoint);

            $body = $response->body();

            if ($response->failed()) {
                $fault = $this->parseXmlField($body, 'faultstring');

                return [
                    'ok' => false,
                    'message' => $fault ?: __('messages.hanel.soap_call_failed', [
                        'code' => $response->status(),
                        'endpoint' => $endpoint,
                    ]),
                    'status_code' => $response->status(),
                    'body' => $this->truncateBody($body),
                    'parsed' => $this->parseSoapFields($body),
                    'endpoint' => $endpoint,
                ];
            }

            if (str_contains($body, 'soapenv:Fault') || str_contains($body, 'Fault>')) {
                $fault = $this->parseXmlField($body, 'faultstring');

                return [
                    'ok' => false,
                    'message' => $fault ?: __('messages.hanel.soap_fault'),
                    'status_code' => $response->status(),
                    'body' => $this->truncateBody($body),
                    'parsed' => $this->parseSoapFields($body),
                    'endpoint' => $endpoint,
                ];
            }

            return [
                'ok' => true,
                'message' => __('messages.hanel.soap_call_ok'),
                'status_code' => $response->status(),
                'body' => $this->truncateBody($body),
                'parsed' => $this->parseSoapFields($body),
                'endpoint' => $endpoint,
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'message' => $this->formatConnectionError($exception->getMessage()),
                'endpoint' => $endpoint,
            ];
        }
    }

    private function soapTimeout(bool $extended = false): int
    {
        $base = max(10, (int) $this->settings->timeout_seconds);

        return $extended ? max(30, $base) : $base;
    }

    private function formatConnectionError(string $message): string
    {
        $lower = strtolower($message);

        if (str_contains($lower, 'timed out')
            || str_contains($lower, 'timeout')
            || str_contains($lower, 'curl error 28')) {
            return __('messages.hanel.connection_timeout', [
                'host' => $this->settings->host,
                'timeout' => $this->soapTimeout(true),
            ]);
        }

        if (str_contains($lower, 'could not resolve')
            || str_contains($lower, 'failed to connect')
            || str_contains($lower, 'connection refused')) {
            return __('messages.hanel.connection_unreachable', [
                'host' => $this->settings->host,
            ]);
        }

        return $message;
    }

    public function formatConnectionErrorPublic(string $message): string
    {
        return $this->formatConnectionError($message);
    }

    /** @return array<string, string> */
    private function parseSoapFields(string $xml): array
    {
        $fields = [
            'returnValue',
            'returnErrorNumber',
            'returnErrorMessage',
            'returnErrorIndex',
            'liftNumber',
            'accessNumber',
            'shelfInAccess',
            'articleNumber',
            'shelfNumber',
        ];

        $parsed = [];

        foreach ($fields as $field) {
            $value = $this->parseXmlField($xml, $field);

            if ($value !== null) {
                $parsed[$field] = $value;
            }
        }

        if (! isset($parsed['returnValue'])) {
            $returnBlock = $this->parseXmlField($xml, 'return');

            if ($returnBlock !== null && preg_match('/<(?:[\w]+:)?returnValue\b[^>]*>([^<]*)<\//', $returnBlock, $match) === 1) {
                $parsed['returnValue'] = html_entity_decode($match[1], ENT_XML1);
            }
        }

        if (! isset($parsed['returnValue']) && preg_match('/<(?:[\w]+:)?returnValue\b[^>]*>([^<]*)<\//', $xml, $match) === 1) {
            $parsed['returnValue'] = html_entity_decode($match[1], ENT_XML1);
        }

        return $parsed;
    }

    /** @return list<array<string, string>> */
    private function parseXmlElements(string $xml, string $elementName): array
    {
        $escaped = preg_quote($elementName, '/');
        $pattern = '/<(?:[\w]+:)?'.$escaped.'(?:\s[^>]*)?>(.*?)<\/(?:[\w]+:)?'.$escaped.'>/s';
        preg_match_all($pattern, $xml, $matches);

        $items = [];

        foreach ($matches[1] as $chunk) {
            $item = $this->parseXmlChildFields($chunk);

            if ($item !== []) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /** @return array<string, string> */
    private function parseXmlChildFields(string $xml): array
    {
        $item = [];
        preg_match_all('/<(?:[\w]+:)?([a-zA-Z0-9_]+)[^>]*>([^<]*)<\/(?:[\w]+:)?\1>/', $xml, $fields, PREG_SET_ORDER);

        foreach ($fields as $field) {
            $item[$field[1]] = html_entity_decode($field[2], ENT_XML1);
        }

        return $item;
    }

    private function parseXmlField(string $xml, string $field): ?string
    {
        if (preg_match('/<(?:[\w]+:)?'.$field.'>([^<]*)<\//', $xml, $match) === 1) {
            return html_entity_decode($match[1], ENT_XML1);
        }

        return null;
    }

    /** @param  array<string, mixed>  $parsed */
    private function formatReturnValueMessage(array $parsed): string
    {
        $code = $parsed['returnValue'] ?? null;
        $error = $parsed['returnErrorMessage'] ?? null;
        $errorNumber = $parsed['returnErrorNumber'] ?? null;

        if ($error) {
            return __('messages.hanel.soap_return_error', [
                'code' => $errorNumber ?: $code,
                'error' => $error,
            ]);
        }

        return __('messages.hanel.soap_return_code', ['code' => $code ?? '?']);
    }

    /** @param  array<string, mixed>  $response */
    private function extractSoapErrorMessage(array $response): string
    {
        $parsed = $response['parsed'] ?? [];

        if ($parsed !== []) {
            return $this->formatReturnValueMessage($parsed);
        }

        return (string) ($response['message'] ?? __('messages.hanel.article_sync_failed'));
    }

    /** @param  array{ok: bool, status: string, message: string, details?: array<string, mixed>}  $result */
    private function isApdNotAvailableError(array $result): bool
    {
        $details = strtolower(json_encode($result['details'] ?? []));

        return str_contains($details, 'article pool data not available')
            || str_contains($details, '"returnerrornumber":"403"')
            || str_contains($details, 'returnerrornumber>403');
    }

    /** @param  array{ok: bool, status: string, message: string, details?: array<string, mixed>}  $result */
    private function isHostWriteBlockedError(array $result): bool
    {
        $details = strtolower(json_encode($result['details'] ?? []));

        return str_contains($details, 'article memory not empty')
            || str_contains($details, 'storage location for container type already assigned')
            || str_contains($details, 'article pool data not available')
            || str_contains($details, '"returnvalue":"4"')
            || str_contains($details, '"returnvalue":"7"')
            || str_contains($details, 'returnvalue>4<')
            || str_contains($details, 'returnvalue>7<');
    }

    private function generateShortJobNumber(): string
    {
        $next = (int) Cache::increment('hanel.job_number_seq');

        if ($next < 1 || $next > 999) {
            Cache::put('hanel.job_number_seq', 1);
            $next = 1;
        }

        return (string) $next;
    }

    private function normalizeJobNumber(?string $jobNumber): ?string
    {
        $jobNumber = trim((string) $jobNumber);

        if ($jobNumber === '' || ! preg_match('/^\d{1,3}$/', $jobNumber)) {
            return null;
        }

        return (string) ((int) $jobNumber);
    }

    private function truncateBody(string $body, int $limit = 4000): string
    {
        if (strlen($body) <= $limit) {
            return $body;
        }

        return substr($body, 0, $limit).'...';
    }

    public function buildMacroTelegram(string $macro, int $sequence, array $parameters = []): string
    {
        $lift = str_pad((string) $this->settings->lift_number, 3, '0', STR_PAD_LEFT);
        $accessPoint = (string) $this->settings->access_point;
        $sequenceNumber = str_pad((string) ($sequence % 1000), 3, '0', STR_PAD_LEFT);

        $telegram = "*G{$lift}{$accessPoint}:2301\$U".self::TELEGRAM_SEPARATOR."XR\${$sequenceNumber}\$macro={$macro}";

        foreach ($parameters as $key => $value) {
            $telegram .= '$'.strtoupper((string) $key).'='.(string) $value;
        }

        return $telegram."\$\r\n";
    }

    private function hasCompleteTelegram(string $buffer): bool
    {
        return str_contains($buffer, '$V'.self::TELEGRAM_SEPARATOR.'XA')
            || preg_match('/\$E\d{2}/', $buffer) === 1;
    }

    private function extractTelegram(string $buffer, string $marker): ?string
    {
        $start = strpos($buffer, $marker);

        if ($start === false) {
            return null;
        }

        $lineEnd = strpos($buffer, "\r\n", $start);

        if ($lineEnd === false) {
            return substr($buffer, $start);
        }

        return substr($buffer, $start, $lineEnd - $start);
    }

    /** @param  array<string, mixed>  $details */
    private function result(bool $ok, string $status, string $message, array $details): array
    {
        return [
            'ok' => $ok,
            'status' => $status,
            'message' => $message,
            'details' => $details,
        ];
    }
}

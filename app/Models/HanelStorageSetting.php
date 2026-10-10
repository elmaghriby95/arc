<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HanelStorageSetting extends Model
{
    public const PROTOCOL_HOST_COM = 'host_com';

    public const PROTOCOL_HOST_WEB = 'host_web';

    public const PROTOCOL_HOST_DATA = 'host_data';

    /** @var list<string> */
    public const PROTOCOLS = [
        self::PROTOCOL_HOST_COM,
        self::PROTOCOL_HOST_WEB,
        self::PROTOCOL_HOST_DATA,
    ];

    protected $fillable = [
        'is_enabled',
        'protocol',
        'host',
        'tcp_port',
        'http_port',
        'use_https',
        'lift_number',
        'access_point',
        'total_shelves',
        'compartments_per_shelf',
        'shelf_layout',
        'layout_synced_at',
        'timeout_seconds',
        'last_test_status',
        'last_test_message',
        'last_tested_at',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'use_https' => 'boolean',
            'tcp_port' => 'integer',
            'http_port' => 'integer',
            'lift_number' => 'integer',
            'access_point' => 'integer',
            'total_shelves' => 'integer',
            'compartments_per_shelf' => 'integer',
            'shelf_layout' => 'array',
            'layout_synced_at' => 'datetime',
            'timeout_seconds' => 'integer',
            'last_tested_at' => 'datetime',
        ];
    }

    public static function instance(): self
    {
        return static::query()->firstOrCreate([], [
            'is_enabled' => false,
            'protocol' => self::PROTOCOL_HOST_WEB,
            'host' => config('hanel.default_host', '172.16.1.1'),
            'tcp_port' => config('hanel.default_tcp_port', 2200),
            'http_port' => config('hanel.default_http_port', 80),
            'use_https' => false,
            'lift_number' => 1,
            'access_point' => 1,
            'total_shelves' => config('hanel.default_total_shelves', 8),
            'compartments_per_shelf' => config('hanel.default_compartments_per_shelf', 8),
            'timeout_seconds' => config('hanel.timeout', 20),
        ]);
    }

    public function baseUrl(): string
    {
        $scheme = $this->use_https ? 'https' : 'http';
        $port = $this->http_port;
        $defaultPort = $this->use_https ? 443 : 80;

        if ($port === $defaultPort) {
            return "{$scheme}://{$this->host}";
        }

        return "{$scheme}://{$this->host}:{$port}";
    }

    public function soapWsdlUrl(): string
    {
        return $this->soapWsdlCandidates()[0];
    }

    /** @return list<string> */
    public function soapWsdlCandidates(): array
    {
        $primary = config('hanel.soap_paths.'.$this->protocol, config('hanel.soap_paths.host_com'));
        $fallbacks = config('hanel.soap_path_fallbacks.'.$this->protocol, []);

        $paths = array_values(array_unique(array_filter([
            $primary,
            ...$fallbacks,
        ])));

        return array_map(fn (string $path) => $this->baseUrl().$path, $paths);
    }
}

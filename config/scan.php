<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Scan mode
    |--------------------------------------------------------------------------
    |
    | local  — agent on each employee PC (SCAN_AGENT_URL=http://127.0.0.1:8765)
    | server — scanner + agent on ARC server (SCAN_AGENT_URL=/scan-agent)
    |
    */

    'mode' => env('SCAN_MODE', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Browser scan URL
    |--------------------------------------------------------------------------
    |
    | Where the browser sends scan requests.
    | Server mode: /scan-agent (same-origin Laravel proxy)
    | Local mode:   http://127.0.0.1:8765
    |
    */

    'agent_url' => env('SCAN_AGENT_URL', 'http://127.0.0.1:8765'),

    /*
    |--------------------------------------------------------------------------
    | Internal agent URL (server only)
    |--------------------------------------------------------------------------
    |
    | Laravel proxies to this address. Keep on 127.0.0.1 — never expose publicly.
    |
    */

    'internal_agent_url' => env('SCAN_INTERNAL_AGENT_URL', 'http://127.0.0.1:8765'),

    'timeout' => (int) env('SCAN_TIMEOUT', 180),

];

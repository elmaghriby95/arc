<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Local Scan Agent URL
    |--------------------------------------------------------------------------
    |
    | URL of ARC Scan Agent running on the employee workstation (localhost).
    | The browser communicates with this service for direct scanning.
    |
    */

    'agent_url' => env('SCAN_AGENT_URL', 'http://127.0.0.1:8765'),

];

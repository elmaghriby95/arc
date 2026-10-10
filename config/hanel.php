<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hänel storage integration defaults
    |--------------------------------------------------------------------------
    |
    | HostCom: TCP telegrams on port 3500 (MP14-S; some units use 2200).
    | HostWeb: SOAP macros at /jwsmacro/services/Macro?wsdl
    | HostData: SOAP data at /jwsdata/services/Data?wsdl
    |
    */

    'default_host' => env('HANEL_HOST', '172.16.1.1'),

    'default_tcp_port' => (int) env('HANEL_TCP_PORT', 2200),

    'tcp_port_fallbacks' => [3500, 2200],

    'tcp_hostcom_ports' => [2200],

    'tcp_connect_timeout' => (int) env('HANEL_TCP_CONNECT_TIMEOUT', 1),

    'tcp_read_timeout' => (int) env('HANEL_TCP_READ_TIMEOUT', 3),

    'default_http_port' => (int) env('HANEL_HTTP_PORT', 80),

    'default_total_shelves' => (int) env('HANEL_TOTAL_SHELVES', 8),

    'default_compartments_per_shelf' => (int) env('HANEL_COMPARTMENTS_PER_SHELF', 8),

    'timeout' => (int) env('HANEL_TIMEOUT', 20),

    'soap_paths' => [
        'host_com' => '/jwsmacro/services/Macro?wsdl',
        'host_web' => '/jwsmacro/services/Macro?wsdl',
        'host_data' => '/jwsdata/services/Data?wsdl',
    ],

    /*
    | MP14-S host communication (JacomMan) exposes WSDL at /jwscom/services/Com?wsdl,
    | not at the Com.ComHttpSoap11Endpoint path shown in the JacomMan UI diagram.
    */
    'soap_path_fallbacks' => [
        'host_data' => [
            '/jwscom/services/Com?wsdl',
        ],
        'host_web' => [
            '/jwscom/services/Com?wsdl',
        ],
    ],

    'soap_endpoints' => [
        'com' => '/jwscom/services/Com.ComHttpSoap11Endpoint/',
        'macro' => '/jwsmacro/services/Macro.MacroHttpSoap11Endpoint/',
    ],

    'soap_namespaces' => [
        'com' => 'http://main.jws.com.hanel.de',
        'com_xsd' => 'http://main.jws.com.hanel.de/xsd',
        'macro' => 'http://main.jws.com.hanel.de',
        'macro_xsd' => 'http://main.jws.com.hanel.de/xsd',
    ],

];

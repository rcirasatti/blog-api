<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Octane Server
    |--------------------------------------------------------------------------
    |
    | This value determines which server will be used to run your Laravel
    | application. By default, the application will be served by RoadRunner.
    |
    | Supported: "roadrunner", "swoole", "frankenphp"
    |
    */

    'server' => env('OCTANE_SERVER', 'roadrunner'),

    /*
    |--------------------------------------------------------------------------
    | Application Port
    |--------------------------------------------------------------------------
    |
    | This value is the port at which the Octane server will serve your
    | application. This value is ignored if the server is started via
    | the Artisan command line interface.
    |
    */

    'port' => env('OCTANE_PORT', 8000),

    /*
    |--------------------------------------------------------------------------
    | Application Host
    |--------------------------------------------------------------------------
    |
    | This value is the host on which Octane will serve your application.
    | This value is ignored if the server is started via the Artisan command
    | line interface.
    |
    */

    'host' => env('OCTANE_HOST', '0.0.0.0'),

    /*
    |--------------------------------------------------------------------------
    | Octane Workers
    |--------------------------------------------------------------------------
    |
    | The number of workers that should be used to serve the application. In
    | most cases, this value should match the number of processors on your
    | machine.
    |
    */

    'workers' => env('OCTANE_WORKERS', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Max Requests
    |--------------------------------------------------------------------------
    |
    | The number of requests to process before reloading the worker. This
    | value is useful for preventing memory leaks during long-running
    | processes.
    |
    */

    'max_requests' => env('OCTANE_MAX_REQUESTS', 500),

    /*
    |--------------------------------------------------------------------------
    | Octane Cache Table
    |--------------------------------------------------------------------------
    |
    | While using Swoole, you may leverage Swoole's powerful table feature
    | for caching data in your application. However, we have disabled it
    | by default for compatibility with other drivers.
    |
    */

    'tables' => [
        'caches' => [
            'rows' => 1000,
            'columns' => [
                'value' => ['type' => 'string', 'size' => 0],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Listening On Sockets
    |--------------------------------------------------------------------------
    |
    | Using this configuration value, you may specify which sockets Octane
    | should listen on. You may add as many sockets as you want for the
    | application to listen on multiple addresses.
    |
    */

    'listeners' => [
        // ':8000',
    ],

    /*
    |--------------------------------------------------------------------------
    | RoadRunner Configuration
    |--------------------------------------------------------------------------
    |
    | If you are using RoadRunner to serve your Laravel application, you
    | may specify additional RoadRunner configuration using this section.
    |
    */

    'roadrunner' => [
        'config_path' => env('ROADRUNNER_CONFIG_PATH', base_path('rr.yaml')),
    ],

];

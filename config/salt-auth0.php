<?php

return [
    'app' => array(
        'client_id' => env('AUTH0_CLIENT_ID', ''),
        'client_secret' => env('AUTH0_CLIENT_SECRET', ''),
        'db_connection' => env('AUTH0_DB_CONNECTION', '')
    ),
    'api' => array(
        'audience' => env('API_MACHINE_AUDIENCE', ''),
        'client_id' => env('AUTH0_MACHINE_CLIENT_ID', ''),
        'client_secret' => env('AUTH0_MACHINE_CLIENT_SECRET', ''),
        'domain' => env('AUTH0_MACHINE_DOMAIN')
    ),
    'url' => env('APP_URL', '')
];

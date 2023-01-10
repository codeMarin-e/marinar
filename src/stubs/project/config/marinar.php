<?php
return [
    'max_file_size' => env('MAX_FILE_SIZE', '1024'), //IN MB

    'where_route_prefixes' => [
        'web' => '',
        'admin' => '/admin',
    ],
    'admin_home' => '/admin',

    'addons' => [],

    // @HOOK_MARINAR_CONFIG
];


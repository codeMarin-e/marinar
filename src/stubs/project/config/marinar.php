<?php
return [
    /**
     * Behavior when package is installed or update
     * true - normal
     * false - do not do anything
     */
    'install_behavior' => env('MARINAR_INSTALL_BEHAVIOR', true),

    /**
     * Behavior when package is removed from composer
     * true - delete all
     * false - delete all, but not changed stubs files
     * 1 - delete all, but keep the stub files and injection
     * 2 - keep everything
     */
    'delete_behavior' => env('MARINAR_DELETE_BEHAVIOR', false),

    /**
     * File stubs that return arrays that are configurable,
     * If path is directory - its files and sub directories
     */
    'values_stubs' => [
        __DIR__,
        dirname(__DIR__).DIRECTORY_SEPARATOR.'lang'
    ],

    /**
     * Exclude stubs to be updated
     * If path is directory - exclude all its files
     * If path is file - only it
     */
    'exclude_stubs' => [
        dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'project'.DIRECTORY_SEPARATOR.'routes',
        dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'public_html'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'images',
        dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'public_html'.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'vendor',
        dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'public_html'.DIRECTORY_SEPARATOR.'packages',

        // @HOOK_MARINAR_EXCLUDE_STUBS
    ],

    'max_file_size' => env('MAX_FILE_SIZE', '1024'), //IN MB

    /**
     * To determine which guard (and where are we) by the route prefix
     * Auth guard => route prefix
     */
    'where_route_prefixes' => [
        'web' => '',
        'admin' => '/admin',
    ],

    'admin_home' => '/admin',

    /**
     * Type of comment for different file extensions. Helps for addons
     * end_file_extension => comment_type
     */
    'ext_comments' => [
        '.blade.php' => "{{-- __COMMENT__ --}}",
        '.php' => "// __COMMENT__",
    ],

    /**
     * Type of garbage collecting
     * provider - clean in 30% of the '/admin' requests
     * cron - call the cleaning in cronjob //TO DO - may do it in the laravel schedule
     */
    'garbage_collecting' => env('GARBAGE_COLLECTING', 'provider'),

    /**
     * Addons hooked to the package
     */
    'addons' => [
        // @HOOK_MARINAR_CONFIG_ADDONS
    ],

    // @HOOK_MARINAR_CONFIG
];


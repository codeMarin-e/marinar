<?php

    $systemRoutesPath = implode(DIRECTORY_SEPARATOR, [
        base_path(), 'routes', 'system.php'
    ]);

    Route::group([
        'middleware' => [\App\Http\Middleware\SetLocale::class],
        'prefix' => '/{locale}',
        'where' => ['locale' => '[a-zA-Z]{2}'],
        'as' => 'i18n_',
    ], function () use ($systemRoutesPath) {
        include($systemRoutesPath);
    });

    Route::group([
        'middleware' => [\App\Http\Middleware\SetLocale::class],
    ], function () use ($systemRoutesPath) {
        include($systemRoutesPath);
    });

<?php

    use App\Http\Controllers\Admin\DashController;
    use Illuminate\Support\Facades\Route;
    use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
    use \App\Http\Controllers\Admin\LoginController;

    Route::group([
        'prefix' => '/admin',
        'as' => 'admin.'
    ], function() {
        Route::group(['middleware' => config('fortify.middleware', ['web'])], function () {

            //AUTHENTICATE
            Route::get('/login', [LoginController::class, 'index'])
                ->middleware(['guest:' . config('fortify.guard')])
                ->name('login');

//            $limiter = config('fortify.limiters.login');
//            Route::post('/login', [AuthenticatedSessionController::class, 'store'])
//                ->middleware(array_filter([
//                    'guest:'.config('fortify.guard'),
//                    $limiter ? 'throttle:'.$limiter : null,
//                ]));

            Route::get('/logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout.get');

            Route::get('/two-factor-challenge',  [LoginController::class, 'twoFactorChallenge'])
                ->middleware(['guest:'.config('fortify.guard')])
                ->name('two-factor.login');
            //ЕND Authentication

            Route::group(['middleware' => 'auth:admin'], function() {
                Route::get('/', [DashController::class, 'index'])->name('home');
            });

            //ADDING PACKAGE ROUTES
            $adminPathsDir = implode(DIRECTORY_SEPARATOR, array(base_path(), 'routes', 'admin'));
            foreach(glob($adminPathsDir.DIRECTORY_SEPARATOR.'*.php') as $path) include $path;
        });
    });

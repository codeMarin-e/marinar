<?php

namespace App\Providers;

use App\View\Composers\MainComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class MarinarViewServiceProvider extends ServiceProvider {

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        View::composer(
            ['*'], MainComposer::class
        );

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['router']->aliasMiddleware('elfinder', \App\Http\Middleware\Elfinder::class);
    }


}

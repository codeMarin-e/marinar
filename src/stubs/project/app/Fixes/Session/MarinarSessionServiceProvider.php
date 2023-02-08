<?php

    namespace App\Fixes\Session;

    use Illuminate\Session\SessionServiceProvider;

    class MarinarSessionServiceProvider extends SessionServiceProvider
    {

        /**
         * Register the session manager instance.
         *
         * @return void
         */
        protected function registerSessionManager()
        {
            $this->app->singleton('session', function ($app) {
                return new SessionManager($app);
            });
        }
    }

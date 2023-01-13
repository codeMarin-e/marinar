<?php

    namespace App\Http\Middleware;

    use Closure;
    use Illuminate\Support\Facades\URL;
    use Illuminate\Support\Facades\View;

    class SetLocale
    {
        /**
         * Handle an incoming request.
         *
         * @param  \Illuminate\Http\Request  $request
         * @param  \Closure  $next
         * @return mixed
         */
        public function handle($request, Closure $next)
        {
            $locale = strtolower( $request->segment(($appDir = env('APP_DIR')) ? 1 + count(explode('/', $appDir)) : 1) );

            if( isset(config('app.available_locales')[$locale]) ) {
                $request->route()->forgetParameter('locale'); //to not use $locale in controllers
                URL::defaults([ 'locale' => $locale ]); //for route() function to set $locale automatically
            } else {
                $locale = config('app.locale');
            }
            app()->setLocale( $locale );

            View::share('appLocale', app()->getLocale() );
            return $next($request);
        }
    }

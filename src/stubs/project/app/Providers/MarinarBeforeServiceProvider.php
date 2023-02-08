<?php

    namespace App\Providers;

    use App\Models\Site;
    use App\Models\User;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\App;
    use Illuminate\Support\Facades\Gate;
    use App\Fixes\UrlGenerator\UrlGenerator;
    use Illuminate\Support\ServiceProvider;

    class MarinarBeforeServiceProvider extends ServiceProvider {

        public static function getInFolder() {
            return __DIR__;
        }

        public static $where_i_am = null;
        public static $main_segments = null;
        public static $route_segments = null;
        public static $route_use_locale = false;

        public static function routeSegments() {
            if(is_array(static::$route_segments)) return static::$route_segments;
            static::$route_segments = array_slice( request()->segments(), count( static::mainSegments() ) );
            if(!isset(static::$route_segments[0])) return (static::$route_segments = []);
            if(isset(config('app.available_locales')[static::$route_segments[0]] )) {
                static::$route_use_locale = true;
                array_shift(static::$route_segments);
            }
            return static::$route_segments;
        }

        public static function mainSegments() {
            if(is_array(static::$main_segments)) return static::$main_segments;
            return (static::$main_segments = ($appDir = config('app.dir'))? explode('/', $appDir ) : []);
        }

        /**
         * Register services.
         *
         * @return void
         */
        public function register()
        {
            App::singleton('Site', function(){
//                return Site::where('domain', env('ALIAS_DOMAIN', \Illuminate\Support\Str::replaceFirst('www.', '', request()->getHost()) ) )->first();
                return json_decode(json_encode(array('id' => 1, 'domain' => 'testing.test')));
            });

            Request::macro('whereIAm', function() {
                if(!is_null(\App\Providers\MarinarBeforeServiceProvider::$where_i_am))
                    return \App\Providers\MarinarBeforeServiceProvider::$where_i_am;
                if (app()->runningInConsole()) {
                    return (\App\Providers\MarinarBeforeServiceProvider::$where_i_am = 'CLI');
                }
                $routeSegments = \App\Providers\MarinarBeforeServiceProvider::routeSegments();
                foreach(config('marinar.where_route_prefixes') as $whereRoutePrefixKey => $whereRoutePrefix) {
                    $whereRoutePrefixSegments = explode('/', $whereRoutePrefix);
                    $whereRoutePrefixSegments =  array_values(array_filter($whereRoutePrefixSegments, function ($value) {
                        return $value !== '';
                    }));
                    $i_am_in = true;
                    foreach($whereRoutePrefixSegments as $index => $whereRoutePrefixSegment) {
                        if(!isset($routeSegments[$index]) || $whereRoutePrefixSegment !== $routeSegments[$index]) {
                            $i_am_in = false;
                            break;
                        }
                    }
                    if($i_am_in) {
                        \App\Providers\MarinarBeforeServiceProvider::$where_i_am = $whereRoutePrefixKey;
                    }
                }
                return \App\Providers\MarinarBeforeServiceProvider::$where_i_am;
            });


            App::singleton('Site', function(){
                return Site::where('domain', env('ALIAS_DOMAIN', \Illuminate\Support\Str::replaceFirst('www.', '', request()->getHost()) ) )->first();
            });

            // @HOOK_REGISTER

        }

        protected function requestRebinder()
        {
            return function ($app, $request) {
                $app['url']->setRequest($request);
            };
        }

        /**
         * Bootstrap services.
         *
         * @return void
         */
        public function boot()
        {
            $dispatcher = new \App\Fixes\Events\Dispatcher();
            $dispatcher->loadFromParentObj( User::getEventDispatcher() );
            User::setEventDispatcher( $dispatcher );

            $this->app->singleton('url', function ($app) {
                $routes = $app['router']->getRoutes();

                // The URL generator needs the route collection that exists on the router.
                // Keep in mind this is an object, so we're passing by references here
                // and all the registered routes will be available to the generator.
                $app->instance('routes', $routes);

                return new UrlGenerator(
                    $routes, $app->rebinding(
                    'request', $this->requestRebinder()
                ), $app['config']['app.asset_url']
                );
            });

            // Implicitly grant "Super Admin" role all permissions
            // This works in the app by using gate-related functions like auth()->user->can() and @can()
            Gate::before(function ($user, $ability) {
                return $user->hasRole('Super Admin') ? true : null;
            });

            // @HOOK_BOOT
        }

    }

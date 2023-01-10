<?php
    namespace Marinar\Marinar\Http\Middleware;

    class CheckSite {


        public function handle($request, \Closure $next, $marinarRouteModel) {
            $marinarModel = request()->route($marinarRouteModel);
            if($marinarModel->site_id != app()->make('Site')->id) {
                abort('401');
            }

            return $next($request);
        }
    }

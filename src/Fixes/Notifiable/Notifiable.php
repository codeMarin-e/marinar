<?php

    namespace Marinar\Marinar\Fixes\Notifiable;

    use Illuminate\Notifications\HasDatabaseNotifications;
    use Illuminate\Notifications\RoutesNotifications;
    use Illuminate\Support\Str;

    trait Notifiable
    {
        use HasDatabaseNotifications;
        use RoutesNotifications {
            routeNotificationFor as oldRouteNotificationFor;
        }

        public function routeNotificationFor($driver, $notification = null) {
            $method = 'routeNotificationFor'.Str::studly($driver);
            if(method_exists($this, 'macro') && $this->hasMacro($method)) {
                return $this->{$method}($notification);
            }
            return $this->oldRouteNotificationFor($driver, $notification);
        }
    }

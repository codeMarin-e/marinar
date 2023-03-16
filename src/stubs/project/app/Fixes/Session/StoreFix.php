<?php
    namespace App\Fixes\Session;

    use Illuminate\Cookie\CookieValuePrefix;
    use Illuminate\Cookie\Middleware\EncryptCookies;
    use Illuminate\Support\Str;

    trait StoreFix {

        public function tokenUsedName($tokenPrefix = null) {
            $tokenPrefix = is_null($tokenPrefix)? request()->whereIAm(): $tokenPrefix;
            return '_'.$tokenPrefix.'_token';
        }

        /**
         * Start the session, reading the data from a handler.
         *
         * @return bool
         */
        public function start()
        {
            $this->loadSession();

            if (! $this->has( $this->tokenUsedName() )) {
                $this->regenerateToken();
            }

            return $this->started = true;
        }

        /**
         * Get the CSRF token value.
         *
         * @return string
         */
        public function token()
        {
            if($headerCSRFtoken = request()->header('X-CSRF-TOKEN')) {
                foreach(config('marinar.where_route_prefixes') as $guard => $uriSegment) {
                    if($sessionTypeToken = $this->get( $this->tokenUsedName($guard) ))
                        if (hash_equals($sessionTypeToken, $headerCSRFtoken)) {
                            return $sessionTypeToken;
                        }
                }
            }
            if ($header = request()->header('X-XSRF-TOKEN')) {
                $cookieToken = CookieValuePrefix::remove(app()->make('encrypter')->decrypt($header, EncryptCookies::serialized('XSRF-TOKEN')));
                foreach(config('marinar.where_route_prefixes') as $guard => $uriSegment) {
                    if($sessionTypeToken = $this->get( $this->tokenUsedName($guard) ))
                        if (hash_equals($sessionTypeToken, $cookieToken)) {
                            return $sessionTypeToken;
                        }
                }
            }
            return $this->get( $this->tokenUsedName() );
        }


        /**
         * Regenerate the CSRF token value.
         *
         * @return void
         */
        public function regenerateToken()
        {
            event( 'session.regenerate.start');
            $this->put($this->tokenUsedName(), Str::random(40));
            event( 'session.regenerate.end');
        }
    }

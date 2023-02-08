<?php

    namespace App\Traits;

    use App\Traits\Addonable;
    use Closure;
    use ReflectionClass;
    use ReflectionMethod;

    trait MacroableModel
    {
        use Addonable;

        public static function bootMacroableModel() {
            $dispatcher = new \App\Fixes\Events\Dispatcher();
            $dispatcher->loadFromParentObj( parent::getEventDispatcher() );
            static::setEventDispatcher( $dispatcher );
        }

        /**
         * The registered string macros.
         *
         * @var array
         */
        public static $macros = [];

        /**
         * Register a custom macro.
         *
         * @param  string $name
         * @param  object|callable  $macro
         *
         * @return void
         */
        public static function macro($name, $macro)
        {
            static::$macros[$name] = $macro;
        }

        /**
         * Mix another object into the class.
         *
         * @param  object  $mixin
         * @param  bool  $replace
         * @return void
         *
         * @throws \ReflectionException
         */
        public static function mixin($mixin, $replace = true)
        {
            $methods = (new ReflectionClass($mixin))->getMethods(
                ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
            );

            foreach ($methods as $method) {
                if ($replace || ! static::hasMacro($method->name)) {
                    $method->setAccessible(true);
                    static::macro($method->name, $method->invoke($mixin));
//                    dd(\Closure::fromCallable([$mixin, $method->name]));
//                    dd($method);
//                    dd($method->getClosure($this));
//                    dd(Closure::bind(Closure::fromCallable([$mixin, $method->name]), null, static::class));
//                    static::macro($method->name, \Closure::fromCallable([$mixin, $method->name]));
                }
            }
        }

        /**
         * Checks if macro is registered.
         *
         * @param  string  $name
         * @return bool
         */
        public static function hasMacro($name)
        {
            return isset(static::$macros[$name]);
        }

        /**
         * Dynamically handle calls to the class.
         *
         * @param  string  $method
         * @param  array  $parameters
         * @return mixed
         *
         * @throws \BadMethodCallException
         */
        public static function __callStatic($method, $parameters)
        {
            if (! static::hasMacro($method)) {
                return parent::__callStatic($method, $parameters);
            }

            $macro = static::$macros[$method];

            if ($macro instanceof Closure) {
                return call_user_func_array(Closure::bind($macro, null, static::class), $parameters);
            }

            return $macro(...$parameters);
        }

        /**
         * Dynamically handle calls to the class.
         *
         * @param  string  $method
         * @param  array  $parameters
         * @return mixed
         *
         * @throws \BadMethodCallException
         */
        public function __call($method, $parameters)
        {
            if (! static::hasMacro($method)) {
                return parent::__call($method, $parameters);
            }

            $macro = static::$macros[$method];

            if ($macro instanceof Closure) {
                return call_user_func_array($macro->bindTo($this, static::class), $parameters);
            }

            return $macro(...$parameters);
        }

        public function hasNamedScope($scope) {
            return (method_exists($this, 'scope'.ucfirst($scope)) || $this->hasMacro($scope)) ;
        }
    }

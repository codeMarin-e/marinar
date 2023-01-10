<?php

    namespace App\Providers;

    use Marinar\Marinar\Providers\MarinarBeforeServiceProvider as Base;

    class MarinarBeforeServiceProvider extends Base {

        public static function getInFolder() {
            return __DIR__;
        }
    }

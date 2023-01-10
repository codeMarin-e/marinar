<?php

    namespace Marinar\Marinar\Fixes\Session;

    use \Illuminate\Session\Store as OriginalStore;

    class Store extends OriginalStore
    {
        use StoreFix;
    }

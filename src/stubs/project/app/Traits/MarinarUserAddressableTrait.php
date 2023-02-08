<?php
    namespace App\Traits;

    if(trait_exists('\\App\\Traits\\Addressable')) {
        trait MarinarUserAddressableTrait {
            use \App\Traits\Addressable;
        }
    } else {
        trait MarinarUserAddressableTrait { }
    }

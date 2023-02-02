<?php
if(request()->whereIAm() == 'admin' && config('marinar.garbage_collecting', 'provider') === 'provider') {
    if(mt_rand(0, 100) < 31) {
        \Illuminate\Support\Facades\Artisan::call('gc:cleanup');
    }
}

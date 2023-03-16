<?php

namespace App\Models;

use App\Traits\MacroableModel;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $fillable = [ 'domain', 'language', 'testing', 'seo' ];

    public $config = [
        // @HOOK_CONFIGS
    ];

    use MacroableModel;

    // @HOOK_TRAITS
}

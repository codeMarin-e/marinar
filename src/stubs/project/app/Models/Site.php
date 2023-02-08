<?php

namespace App\Models;

use App\Traits\MacroableModel;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $fillable = [ 'domain', 'language' ];

    public $config = [];

    use MacroableModel;

    // @HOOK_TRAITS
}

<?php

namespace Marinar\Marinar\Models;

use Illuminate\Database\Eloquent\Model;
use Marinar\Marinar\Traits\MacroableModel;

class SiteBase extends Model
{
    use MacroableModel;
    protected $fillable = [ 'domain', 'language' ];

    public $config = [];
}

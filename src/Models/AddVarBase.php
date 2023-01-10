<?php

namespace Marinar\Marinar\Models;

use Illuminate\Database\Eloquent\Model;

class AddVarBase extends Model
{
    protected $fillable = ['var_name', 'var_value', 'language', 'site_id'];

    public static $relation_name = 'addvariable';

    protected $touches = ['addvariable'];

    public function addvariable() {
        return $this->morphTo(self::$relation_name);
    }

}

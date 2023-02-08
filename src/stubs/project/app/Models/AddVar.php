<?php

    namespace App\Models;

    use App\Traits\MacroableModel;
    use Illuminate\Database\Eloquent\Model;

    class AddVar extends Model
    {
        protected $fillable = ['var_name', 'var_value', 'language', 'site_id'];

        public static $relation_name = 'addvariable';

        protected $touches = ['addvariable'];

        use MacroableModel;

        // @HOOK_TRAITS

        public function addvariable() {
            return $this->morphTo(self::$relation_name);
        }

    }

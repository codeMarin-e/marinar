<?php

    namespace App\Traits;

    use App\Models\AddVar;

    trait AddVariable
    {

        public $addvariables_cache = null;

        public static function bootAddVariable() {
            if (method_exists(static::class, 'bootSoftDeletes') || static::hasMacro('bootSoftDeletes')) {
                static::registerModelEvent('forceDeleted', static::class . '@onDeleting_addVariables');
            } else {
                static::deleting(static::class . '@onDeleting_addVariables');
            }
        }

        public function addvariable()
        {
            return $this->morphMany(AddVar::class, AddVar::$relation_name);
        }

        public function aVarCache() {
            $this->addvariables_cache = [];
            foreach($this->addvariable()->get() as $aVar) {
                $this->addvariables_cache[$aVar->site_id][$aVar->language][$aVar->var_name] = $aVar->var_value;
            }
        }

        public function clearAVarCache() {
            $this->addvariables_cache = null; //clear the memory cache
        }

        public function aVar($name, $site = null, $language = null, $useOtherLanguages = true)
        {
            if(is_null($this->addvariables_cache)) {
                $this->aVarCache();
            }
            $language = !is_null($language)? $language : app()->getLocale();
            $site = !is_null($site) ? $site : app()->make('Site');
            if(isset($this->addvariables_cache[$site->id][$language][$name])) {
                return $this->addvariables_cache[$site->id][$language][$name];
            }

            if (!$useOtherLanguages) return '';

            $fallbackLanguage = $site->language; //config('app.fallback_locale');
            if($fallbackLanguage != $language) {
                $return = $this->aVar($name, $site, $fallbackLanguage, false);
                if ($return !== '') return $return;
            }

            $otherLanguages = config('app.available_locales');
            unset($otherLanguages[$language]);
            unset($otherLanguages[$fallbackLanguage]);
            foreach ($otherLanguages as $otherLanguage => $otherLanguageName) {
                $return = $this->aVar($name, $site, $otherLanguage, false);
                if ($return !== '') return $return;
            }
            return '';
        }

        public function setAVar(String $name, $value, $site = null, $language = null)
        {
            if ($name == '') return;
            $this->setAVars([ $name => $value]);
        }

        public function setAVars($addVars = [], $site = null, $language = null)
        {
            if (!is_array($addVars) || empty($addVars)) {
                return;
            }
            $this->clearAVarCache();
            $values = [
                'site_id' => (!is_null($site) ? $site : app()->make('Site')->id),
                'language' => (!is_null($language) ? $language : app()->getLocale()),
                AddVar::$relation_name . '_id' => $this->id,
                AddVar::$relation_name . '_type' => static::class,
            ];
            foreach ($addVars as $name => $value) {
                $values['var_name'] = $name;
                $values['var_value'] = $value;
                $addVarModels[] = $values;
            }
            AddVar::upsert($addVarModels, [
                AddVar::$relation_name . '_id',
                AddVar::$relation_name . '_type',
                'site_id', 'language', 'var_name'
            ], ['var_value']);
        }

        public function onDeleting_addVariables($model)
        {
            $model->clearAVarCache();
            $model->addvariable()->delete();
        }
    }

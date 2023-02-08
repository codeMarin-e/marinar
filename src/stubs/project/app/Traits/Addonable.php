<?php
    namespace App\Traits;

    use Illuminate\Database\Eloquent\Casts\Attribute;
    use Illuminate\Support\Str;

    /**
     * @see \Illuminate\Database\Eloquent\Model used Traits
     * Trait Addonable
     */
    trait Addonable {
        public static $addonFillable = [];
        public static $addonGuarded = [];
        public static $addonDates = [];
        public static $addonVisible = [];
        public static $addonHidden = [];
        public static $addonCasts = [];
        public static $addonRelations = [];
        public static $addonTouches = [];
        public static $addonObservables = [];

        public static $addonStatics = [];


        public function getFillable() {
//            return Schema::getColumnListing($this->getTable()); //all columns
            if(!isset(static::$addonFillable))
                return parent::getFillable();
            return array_merge(parent::getFillable(), (array)static::$addonFillable);
        }

        public function getGuarded() {
//            return Schema::getColumnListing($this->getTable()); //all columns
            if(!isset(static::$addonGuarded))
                return parent::getGuarded();
            return array_merge(parent::getGuarded(), (array)static::$addonGuarded);
        }

        public function getDates() {
            if(!isset(static::$addonDates))
                return parent::getDates();
            return array_merge(parent::getDates(), (array)static::$addonDates);
        }

        public function getVisible() {
            if(!isset(static::$addonVisible))
                return parent::getVisible();
            return array_merge(parent::getVisible(), (array)static::$addonVisible);
        }

        public function getHidden() {
            if(!isset(static::$addonHidden))
                return parent::getHidden();
            return array_merge(parent::getHidden(), (array)static::$addonHidden);
        }

        public function getCasts() {
            if(!isset(static::$addonCasts))
                return parent::getCasts();
            return array_merge(parent::getCasts(), (array)static::$addonCasts);
        }

        public function getRelations() {
            if(!isset(static::$addonRelations))
                return parent::getRelations();
            return array_merge(parent::getRelations(), (array)static::$addonRelations);
        }

        public function getTouchedRelations() {
            if(!isset(static::$addonTouches))
                return parent::getTouchedRelations();
            return array_merge(parent::getTouchedRelations(), (array)static::$addonTouches);
        }

        public function getObservableEvents() {
            if(!isset(static::$addonObservables))
                return parent::getObservableEvents();
            return array_merge(parent::getObservableEvents(), (array)static::$addonObservables);
        }

        public function hasGetMutator($key) {
            $scope = 'get'.Str::studly($key).'Attribute';
            return (method_exists($this, $scope) || $this->hasMacro($scope));
        }

        public function hasSetMutator($key) {
            $scope = 'set'.Str::studly($key).'Attribute';
            return (method_exists($this, $scope) || $this->hasMacro($scope));
        }


        public function hasAttributeMutator($key)
        {
            if (isset(static::$attributeMutatorCache[get_class($this)][$key])) {
                return static::$attributeMutatorCache[get_class($this)][$key];
            }

            if (! method_exists($this, $method = Str::camel($key)) && !$this->hasMacro($method)) {
                return static::$attributeMutatorCache[get_class($this)][$key] = false;
            }

            $returnType = $this->hasMacro($method)?
                (new \ReflectionFunction( static::$macros[$method] ))->getReturnType() :
                (new \ReflectionMethod($this, $method))->getReturnType();

            return static::$attributeMutatorCache[get_class($this)][$key] =
                $returnType instanceof ReflectionNamedType &&
                $returnType->getName() === Attribute::class;
        }

        public function hasAttributeSetMutator($key)
        {
            $class = get_class($this);

            if (isset(static::$setAttributeMutatorCache[$class][$key])) {
                return static::$setAttributeMutatorCache[$class][$key];
            }

            if (! method_exists($this, $method = Str::camel($key)) && !$this->hasMacro($method)) {
                return static::$setAttributeMutatorCache[$class][$key] = false;
            }

            $returnType = $this->hasMacro($method)?
                (new \ReflectionFunction( static::$macros[$method] ))->getReturnType() :
                (new \ReflectionMethod($this, $method))->getReturnType();

            return static::$setAttributeMutatorCache[$class][$key] =
                $returnType instanceof ReflectionNamedType &&
                $returnType->getName() === Attribute::class &&
                is_callable($this->{$method}()->set);
        }

        public function isRelation($key)
        {
            if ($this->hasAttributeMutator($key)) {
                return false;
            }

            return method_exists($this, $key) || $this->hasMacro($key) ||
                (static::$relationResolvers[get_class($this)][$key] ?? null);
        }

    }

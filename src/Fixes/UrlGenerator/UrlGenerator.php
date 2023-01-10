<?php
    namespace Marinar\Marinar\Fixes\UrlGenerator;

    use Illuminate\Routing\UrlGenerator as UrlGeneratorBase;
    use Illuminate\Support\Facades\URL;
    use Illuminate\Support\Str;

    class UrlGenerator extends UrlGeneratorBase {

        public function route($name, $parameters = [], $absolute = true) {
            $startsWith = 'i18n_';
            if(!Str::startsWith($name, $startsWith) && isset(URL::getDefaultParameters()['locale'])) {
                $name = $startsWith.$name;
            }
            return parent::route($name, $parameters, $absolute);
        }
    }

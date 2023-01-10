<?php
    namespace Marinar\Marinar\Fixes;

    use Illuminate\Container\Container;
    use Illuminate\Filesystem\Filesystem;
    use Illuminate\Foundation\Application;
    use Illuminate\Foundation\Mix;
    use Illuminate\Foundation\PackageManifest;
    use Illuminate\Foundation\ProviderRepository;
    use Illuminate\Support\Collection;

    class App extends Application {
        public function publicPath() {
            return $this->basePath.DIRECTORY_SEPARATOR.env('PUBLIC_FOLDER', '..'.DIRECTORY_SEPARATOR.'public_html') ;
        }

        protected function registerBaseBindings()
        {
            static::setInstance($this);

            $this->instance('app', $this);

            $this->instance(Container::class, $this);
            $this->singleton(Mix::class);

            $this->singleton(PackageManifest::class, function () {
                return new \Marinar\Marinar\Fixes\Manifest\PackageManifest(
                    new Filesystem, $this->basePath(), $this->getCachedPackagesPath()
                );
            });
        }

        /**
         * Register all of the configured providers.
         *
         * @return void
         */
        public function registerConfiguredProviders()
        {
            $providers = Collection::make($this->config['app.providers'])
                ->partition(function ($provider) {
                    return (strpos($provider, 'Illuminate\\') === 0 || $provider == "App\\Providers\\MarinarBeforeServiceProvider");
                });

            $providers->splice(1, 0, [$this->make(PackageManifest::class)->providers()]);

            (new ProviderRepository($this, new Filesystem, $this->getCachedServicesPath()))
                ->load($providers->collapse()->toArray());
        }


    }

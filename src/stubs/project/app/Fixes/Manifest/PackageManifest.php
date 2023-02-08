<?php
    namespace App\Fixes\Manifest;

    class PackageManifest extends \Illuminate\Foundation\PackageManifest {

        public function build()
        {
            $packages = [];

            if ($this->files->exists($path = $this->vendorPath.'/composer/installed.json')) {
                $installed = json_decode($this->files->get($path), true);
                $packages = $installed['packages'] ?? $installed;
            }
            //SORT MARINAR PACKAGES HERE

            $collectPackages = isset($packages['packages'])? collect( $packages['packages'] ) : collect( $packages );
            $marinarPackages = $collectPackages
                ->reject(function($package) {
                    $packageNameParts = explode('/', $package['name']);
                    return $packageNameParts[0] != 'marinar';

                })->mapWithKeys(function ($package) {
                    return [
                        $package['name'] => (collect($package['require']?? [])->reject(function($version, $packageName) {
                            $packageNameParts = explode('/',$packageName);
                            return $packageNameParts[0] != 'marinar';
                        })->keys()->all())
                    ];
                })->all();
            $requirePackages = array_reverse( array_keys( \App\Models\Package::sortMarinarPackages( $marinarPackages ) ) );

            //END SORT MARINAR PACKAGES HERE

            $ignoreAll = in_array('*', $ignore = $this->packagesToIgnore());

            $installedWithAutoDiscovery = $collectPackages->mapWithKeys(function ($package) {
                return [$this->format($package['name']) => $package['extra']['laravel'] ?? []];
            })->each(function ($configuration) use (&$ignore) {
                $ignore = array_merge($ignore, $configuration['dont-discover'] ?? []);
            })->reject(function ($configuration, $package) use ($ignore, $ignoreAll) {
                return $ignoreAll || in_array($package, $ignore);
            })->filter()->all();

            $writeArr = [];
            $onRequireIndex = 0;
            foreach($installedWithAutoDiscovery as $key => $value) {
                if(!in_array($key, $requirePackages)) {
                    $writeArr[$key] = $value;
                    continue;
                }
                do {
                    $requireKey = $requirePackages[$onRequireIndex];
                    $onRequireIndex++;
                } while(!isset($installedWithAutoDiscovery[ $requireKey ]));
                $writeArr[ $requireKey ] = $installedWithAutoDiscovery[ $requireKey ];
            }

            $this->write($writeArr);
        }
    }

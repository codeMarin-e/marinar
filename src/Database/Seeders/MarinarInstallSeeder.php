<?php
    namespace Marinar\Marinar\Database\Seeders;

    use Illuminate\Database\Seeder;
    use Illuminate\Support\Str;
    use Marinar\Marinar\Console\Commands\MarinarInstall;
    use Marinar\Marinar\Marinar;
    use Marinar\Marinar\Models\PackageBase as Package;

    /**
     * php artisan db:seed --class="\\Marinar\\Marinar\\Database\\Seeders\\MarinarInstallSeeder"
     * @package Marinar\Marinar\Database\Seeders
     */
    class MarinarInstallSeeder extends Seeder {

        use \Marinar\Marinar\Traits\MarinarSeedersTrait;

        public static function configure() {
            static::$packageName = 'marinar';
            static::$packageDir = Marinar::getPackageMainDir();
        }

        public function run() {
            if(!in_array(env('APP_ENV'), ['dev', 'local'])) return;
            static::configure();

            $this->getRefComponents();

            $this->structurePublic();
            $this->stubFiles();
            $this->mainDBMigrate();
            $this->installMarinarPackages();
            $this->initialSeeds();
            $this->prepareComposerJSON();
            $this->giveGitPermissions();
            $this->givePermissions(base_path().DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'marinar_stubs', show: true);

            $this->command->newLine();
            $this->refComponents->info("Done!");
        }

        private function structurePublic() {
            $mainDir = dirname( base_path() );
            $basePath = base_path();
            $source = "{$basePath}".DIRECTORY_SEPARATOR."public";
            if(!realpath($source)) return;
            $command = Package::replaceEnvCommand("mv -T {$source} {$mainDir}".DIRECTORY_SEPARATOR."public_html",
                $basePath //where to search for commands_replace_env.php file
            );
            $this->refComponents->task("Structure public_html [$command]", function() use ($command, $source){
                if(!realpath($source)) return false;
                return $this->execCommand($command);
            });
        }

        private function mainDBMigrate() {
            $command = Package::replaceEnvCommand("php artisan migrate",
                base_path()//where to search for commands_replace_env.php file
            );
            $this->execCommand($command, true);
        }

        private function installMarinarPackages() {
            $this->installAddressable();
            $this->installOrderable();
        }

        private function installAddressable() {
            $command = Package::replaceEnvCommand("php artisan marinar:package marinar/addressable",
                base_path()//where to search for commands_replace_env.php file
            );
            $this->execCommand($command, true);
        }

        private function installOrderable() {
            $command = Package::replaceEnvCommand("php artisan marinar:package marinar/orderable",
                base_path()//where to search for commands_replace_env.php file
            );
            $this->execCommand($command, true);
        }

        private function initialSeeds() {
            $command = Package::replaceEnvCommand('php artisan db:seed --class="\\Database\\Seeders\\MarinarSeeder"',
                base_path()//where to search for commands_replace_env.php file
            );
            $this->refComponents->task("Initial seeds [$command]", function() use ($command){
                return $this->execCommand($command);
            });
        }

        private function prepareComposerJSON() {
            $this->refComponents->task("Prepare composer.json", function(){
                $composerPath = base_path().DIRECTORY_SEPARATOR.'composer.json';
                if(!realpath($composerPath)) {
                    return false;
                }
                if( ($composerJSON = file_get_contents( $composerPath )) === false ) {
                    return false;
                }
                if(!($composerJSON = json_decode($composerJSON, true))) {
                    return false;
                }
                if(!isset($composerJSON['scripts'])) {
                    $composerJSON['scripts'] = array();
                }
                if(!isset($composerJSON['scripts']['pre-operations-exec'])) {
                    $composerJSON['scripts']['pre-operations-exec'] = array();
                }
                if(!isset($composerJSON['scripts']['post-autoload-dump'])) {
                    $composerJSON['scripts']['post-autoload-dump'] = array();
                }
                $operation = "App\\Models\\Package::preOperationsExec";
                if(!in_array($operation, $composerJSON['scripts']['pre-operations-exec'])) {
                    $composerJSON['scripts']['pre-operations-exec'][] = $operation;
                }
                $operation = "App\\Models\\Package::postAutoloadDump";
                if(!in_array($operation, $composerJSON['scripts']['post-autoload-dump'])) {
                    $composerJSON['scripts']['post-autoload-dump'][] = $operation;
                }
                if(!isset($composerJSON['autoload'])) {
                    $composerJSON['autoload'] = array();
                }
                if(!isset($composerJSON['autoload']['files'])) {
                    $composerJSON['autoload']['files'] = array();
                }
                $file = "app/Fixes/elfinder/ElFinderController.php";
                if(!in_array($file, $composerJSON['autoload']['files'])) {
                    $composerJSON['autoload']['files'][] = $file;
                }
                if(!isset($composerJSON['require'])) {
                    $composerJSON['require'] = array();
                }
                if(!isset($composerJSON['require-dev'])) {
                    $composerJSON['require-dev'] = array();
                }
                $mineComposer = json_decode(file_get_contents(static::$packageDir.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'composer.json'), true );
                foreach($mineComposer['require']?? [] as $package => $version) {
                    if(Str::startsWith($package, 'marinar/')) continue;
                    $composerJSON['require'][$package] = $version;
                }
                foreach($mineComposer['require-dev']?? [] as $package => $version) {
                    if(Str::startsWith($package, 'marinar/')) continue;
                    $composerJSON['require-dev'][$package] = $version;
                }
                if( !($composerJSON = json_encode($composerJSON, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
                    return false;
                }
                if(file_put_contents($composerPath, $composerJSON) === false) {
                    return false;
                }
                return true;
            });
        }
    }

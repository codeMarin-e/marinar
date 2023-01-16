<?php
    namespace Marinar\Marinar\Database\Seeders;

    use Illuminate\Database\Seeder;
    use Marinar\Marinar\Console\Commands\MarinarInstall;
    use Marinar\Marinar\Models\PackageBase as Package;
    use Symfony\Component\Process\Exception\ProcessFailedException;
    use Symfony\Component\Process\Process;

    /**
     * php artisan db:seed --class="\\Marinar\\Marinar\\Database\\Seeders\\MarinarInstallSeeder"
     * @package Marinar\Marinar\Database\Seeders
     */
    class MarinarInstallSeeder extends Seeder {

        use \Marinar\Marinar\Traits\MarinarSeedersTrait;

        public static $addons = [];

        public function run() {
            $this->getRefComponents();

            $this->structurePublic();
            $this->stubFiles();
            $this->dbMigrate();
            $this->installMarinarPackages();
            $this->initialSeeds();
            $this->prepareComposerJSON();
            $this->giveGitPermissions(\Marinar\Marinar\Marinar::getPackageMainDir());
            $this->command->newLine();
            $this->refComponents->info("Done!");
        }

        private function createPublicDir() {
            $mainDir = dirname( base_path() );
            $command = Package::replaceEnvCommand("mkdir {$mainDir}".DIRECTORY_SEPARATOR."public_html",
                base_path() //where to search for commands_replace_env.php file
            );
            $this->refComponents->task("Create public_html directory [{$command}]", function() use ($command){
                return $this->execCommand($command);
            });
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

        private function stubFiles() {
            if(!realpath(base_path().DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'marinar.php')) {
                return $this->copyStubs(\Marinar\Marinar\Marinar::getPackageMainDir().DIRECTORY_SEPARATOR.'stubs',force: true);
            }

            //clean for updates
            static::$addons = config('marinar.addons')?? []; //because cleaning stubs clean marinar
            $this->call([
                \Marinar\Marinar\Database\Seeders\MarinarCleanStubsSeeder::class
            ]);

            $this->copyStubs(\Marinar\Marinar\Marinar::getPackageMainDir().DIRECTORY_SEPARATOR.'stubs');

            //inject addons again
            foreach(static::$addons as $addonClass) {
                if(!method_exists($addonClass, 'injects')) continue;
                $this->call( $addonClass::injects() );
            }
        }

        private function dbMigrate() {
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

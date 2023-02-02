<?php
    namespace Marinar\Marinar\Models;

    use Composer\Script\Event;
    use Composer\Installer\PackageEvent;
    use Composer\Installer\InstallerEvent;


    use Symfony\Component\Process\Exception\ProcessFailedException;
    use Symfony\Component\Process\Process;

    class PackageBase {

        public static function mainFolder() {
            return dirname( dirname( \App\Providers\MarinarBeforeServiceProvider::getInFolder()) );
        }

        public static function getRemoveFlagFile() {
            $return = [  static::mainFolder() , 'storage', 'composer_marinar_remove' ];
            return implode(DIRECTORY_SEPARATOR, $return);
        }

        public static function getPackagesJSONPath() {
            $return = [  static::mainFolder() , 'storage', 'composer_marinar.json' ];
            return implode(DIRECTORY_SEPARATOR, $return);
        }

        public static function getServicesCachePath() {
            $return = [  static::mainFolder() , 'bootstrap', 'cache', 'services.php' ];
            return implode(DIRECTORY_SEPARATOR, $return);
        }

        public static function getPackagesCachePath() {
            $return = [  static::mainFolder() , 'bootstrap', 'cache', 'packages.php' ];
            return implode(DIRECTORY_SEPARATOR, $return);
        }

        public static function sortMarinarPackages( $packages ) {
            while(true) {
                $buff = [];
                $foundIn = false;
                $lastPackageName = false;
                foreach ($packages as $packageName => $requires) {
                    $start = false;
                    foreach ($packages as $packageName2 => $requires2) {
                        if ($packageName == $packageName2) {
                            $start = true;
                            continue;
                        }
                        if(!$start) continue;
                        if(in_array($packageName, $requires2)) {
                            $foundIn = $packageName2;
                            $lastPackageName = $packageName;
                            break 2;
                        }
                    }
                    $buff[$packageName] = $requires;
                }
                if(count($buff) != count($packages)) {
                    $help = [];
                    foreach($packages as $packageName => $requires) {
                        if($packageName == $lastPackageName) {
                            $help[ $foundIn ] = $packages[ $foundIn ];
                            continue;
                        }
                        if($packageName == $foundIn) {
                            $help[ $lastPackageName ] = $packages[ $lastPackageName ];
                            continue;
                        }
                        $help[$packageName] = $requires;
                    }
                    $packages = $help;
                    continue;
                }
                break;
            }
            return $buff;
        }


        public static function postAutoloadDump() {
            $command = static::replaceEnvCommand("php -d memory_limit=-1 artisan marinar:package");
            echo "$command \n";
            $process = new Process( explode(' ', $command) );
            $process->setWorkingDirectory( static::mainFolder() );
            $process->setTimeout(380);
            $process->run();

            // executes after the command finishes
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
            echo print_r( $process->getOutput(), true )." \n";
        }
//NOT USED ANYMORE
//        private static function packageCheck($packageName) {
//            $packageParts = explode('/', $packageName);
//            if ($packageParts[0] != 'marinar') {
//                return;
//            }
//
//            $jsonPath = static::getPackagesJSONPath();
//            $packages = [];
//            if($json = realpath( $jsonPath )) {
//                if(!($packages = json_decode( file_get_contents($json) )) ) {
//                    $packages = [];
//                }
//            }
//            $packages[] = $packageName;
//            file_put_contents( $jsonPath, json_encode($packages));
//        }
//
//        public static function postPackageUpdate($event) {
//            $installedPackage = $event->getOperation()->getTargetPackage();
//            static::packageCheck($installedPackage->getName());
//        }
//
//        public static function postPackageInstall(PackageEvent $event) {
//            $installedPackage = $event->getOperation()->getPackage();
//            static::packageCheck($installedPackage->getName());
//        }
//
//        public static function prePackageUninstall(PackageEvent $event) {
//            if($servicesCache = realpath(static::getServicesCachePath()))
//                @unlink($servicesCache);
//            if($packagesCache = realpath(static::getPackagesCachePath()))
//                @unlink($packagesCache);
//            $installedPackage = $event->getOperation()->getPackage();
//            $package = $installedPackage->getName();
//
//            $packageParts = explode('/', $package);
//            if ($packageParts[0] != 'marinar') {
//                return;
//            }
//            $removeFlagFile = static::getRemoveFlagFile();
//            @file_put_contents( $removeFlagFile, $package );
//
//            echo "php artisan marinar:package {$package} -r \n";
//            $process = new Process( "php artisan marinar:package {$package} -r" );
//            $process->setWorkingDirectory( static::mainFolder() );
//            $process->setTimeout(180);
//            $process->run();
//            @unlink($removeFlagFile);
//            // executes after the command finishes
//            if (!$process->isSuccessful()) {
//                throw new ProcessFailedException($process);
//            }
//            echo print_r( $process->getOutput(), true )." \n";
//        }


        public static function preOperationsExec(InstallerEvent $arg1) {
            $operations = $arg1->getTransaction()->getOperations();
            $marinarInstallsAndUpdates = [];
            $marinarUninstalls = [];
            foreach($operations as $index => $operation) {
                //InstallOperation
                //UninstallOperation
                //UpdateOperation
                $operationJob = $operation->getOperationType();
                if(!in_array($operationJob, ['update', 'install', 'uninstall']))
                    continue;

                $package = $operationJob == 'update'? $operation->getTargetPackage() : $operation->getPackage();
                $packageName = $package->getName();
                $packageParts = explode('/', $packageName);
                if ($packageParts[0] != 'marinar') {
                    continue;
                }

//                echo "\n \n operation {$index}: " . get_class($operation);
//                echo "\n reason {$index}: " .$operation->getReason();
//                echo "\n job {$index}: " .$operation->getJobType();
//                echo "\n string {$index}: " .$operation;
////                echo "\n package class {$index}: " .get_class($package);
//                echo "\n package {$index}: " .$packageName;
//                $requires = $package->getRequires();
//                echo "\n requires {$index}:";
//                foreach($requires as $index2 => $require) {
//                    echo "\n require {$index2}: " .$require->getSource()." => ".$require->getTarget(). "\n";
//                }

//                echo "\n \n" . var_dump(get_class_methods($package));

                $arrName =  $operationJob == 'uninstall'? 'marinarUninstalls' : 'marinarInstallsAndUpdates';
                ${$arrName}[$packageName] = [];
                foreach (array_keys($package->getRequires()) as $requirePackageName) {
                    $packageParts = explode('/', $requirePackageName);
                    if ($packageParts[0] != 'marinar') {
                        continue;
                    }
                    ${$arrName}[$packageName][] = $requirePackageName;
                }
            }
            if(!empty($marinarInstallsAndUpdates)) {
                $installPackages = array_reverse( array_keys( static::sortMarinarPackages($marinarInstallsAndUpdates) ) );
                $jsonPath = static::getPackagesJSONPath();
                $packages = [];
                if($json = realpath( $jsonPath )) {
                    if(!($packages = json_decode( file_get_contents($json) )) ) {
                        $packages = [];
                    }
                }
                $packages = array_unique( array_merge($packages, $installPackages) );
                file_put_contents( $jsonPath, json_encode($packages));
            }
            if(!empty($marinarUninstalls)) {
                $removePackages = static::sortMarinarPackages($marinarUninstalls);
                foreach ($removePackages as $packageName => $requires) {
                    $forProcessCmd = static::replaceEnvCommand("php -d memory_limit=-1 artisan marinar:package {$packageName} -r");
                    echo "{$forProcessCmd} \n";
                    $process = new Process(explode(' ', $forProcessCmd));
                    $process->setWorkingDirectory(static::mainFolder());
                    $process->setTimeout(380);
                    $process->run();
                    // executes after the command finishes
                    if (!$process->isSuccessful()) {
                        throw new ProcessFailedException($process);
                    }
                    echo print_r($process->getOutput(), true) . " \n";
                }
            }
        }

        public static function replaceEnvCommand($command, $mainFolder = null) {
            $mainFolder = $mainFolder? $mainFolder : static::mainFolder();
            if(!file_exists( $mainFolder.DIRECTORY_SEPARATOR.'commands_replace_env.php')) {
                return $command;
            }
            $replace = include $mainFolder.DIRECTORY_SEPARATOR.'commands_replace_env.php';
            if(isset($replace['parts'])) {
                $replaceCmds = array_keys($replace['parts']);
                foreach ($replace['parts'] as $key => $value) {
                    $replaceCmds[strtolower($key)] = $value;
                }
                $command = explode(' ', $command);
                foreach ($command as $index => $commandPart) {
                    if (!isset($replaceCmds[strtolower($commandPart)]))
                        continue;
                    $command[$index] = $replaceCmds[strtolower($commandPart)];
                }
                $command = implode(' ', $command);
            }
            return (isset($replace['replace'])?
                str_replace(array_keys($replace['replace']), array_values($replace['replace']), $command) :
                $command);
        }
    }

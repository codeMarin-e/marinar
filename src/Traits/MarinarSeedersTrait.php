<?php

    namespace Marinar\Marinar\Traits;

    use Marinar\Marinar\Models\PackageBase as Package;
    use Symfony\Component\Process\Exception\ProcessFailedException;
    use Symfony\Component\Process\Process;

    trait MarinarSeedersTrait {

        public $refComponents = null;

        private function getRefComponents() {
            $Reflection = new \ReflectionProperty(get_class($this->command), 'components');
            $Reflection->setAccessible(true);
            $this->refComponents = $Reflection->getValue($this->command);
        }

        private function execCommand($command, $output = false) {
            $process = Process::fromShellCommandline( $command );
            $process->setWorkingDirectory( base_path() );
            // $process->setTty(true);
            $process->setTimeout(null);
            $process->run();
            // executes after the command finishes
            if (!$process->isSuccessful()) {
                return false;
                throw new ProcessFailedException($process);
            }
            if($output) echo $process->getOutput();
            return true;
        }

        private function copyStubs($copyDir) {
            $mainDir = dirname( base_path() );
            $command = Package::replaceEnvCommand("cp -rn {$copyDir}".DIRECTORY_SEPARATOR.". {$mainDir}",
                base_path()//where to search for commands_replace_env.php file
            );
            $this->refComponents->task("Coping files [$command]", function() use ($command){
                return $this->execCommand($command);
            });
        }


        private function cleanInjects($addonClasses) {
            foreach($addonClasses as $addonClass) {
                if(!method_exists($addonClass, 'cleanInjects')) continue;
                $this->call( $addonClass::cleanInjects() );
            }
        }

        private static function removeStubFiles($path, $copyDir, $showLogs = false) {
            foreach(glob($path.DIRECTORY_SEPARATOR.'*') as $path) {
                $appPath = base_path( '..'.DIRECTORY_SEPARATOR.substr($path, strlen($copyDir)+1) );
                if(is_dir($path)) {
                    static::removeStubFiles($path, $copyDir, $showLogs);
                    if(is_dir($appPath) && count(glob("$appPath/*")) === 0 ) {
                        rmdir($appPath);
                    }
                    continue;
                }
                if(is_file( $appPath ) ) {
                    if(file_get_contents($path) === file_get_contents($appPath)) { //only if content is same as stubs
                        unlink( $appPath );
                    } else {
                        if($showLogs) {
                            echo PHP_EOL."Not deleted: ".$appPath;
                        }
                    }
                }
            }
        }

        private function hookLines($filePath) {
            if(!($fp = fopen($filePath, "r"))) return false;
            $return = array();
            while (($line = fgets($fp)) !== false) {
                $line = trim($line);
                if($line === '<?php') continue;
                $return[] = $line;
            }
            fclose($fp);
            return $return;
        }

        private function removeFromContent($filePath, $removeLines) {
            if(!($fp = fopen($filePath, "r"))) return false;
            $return = '';
            $check = true;
            $removeLinesCount = count($removeLines);
            while(true) {
                if($check) {
                    if(($line = fgets($fp)) === false) break;
                }
                $trimLine = trim($line);
                $buff = array();
                foreach($removeLines as $removeLine) {
                    if($trimLine !== $removeLine) break;
                    $buff[] = $line;
                    if(($line = fgets($fp)) === false) break 2;
                    $trimLine = trim($line);
                }
                if(count($buff) === $removeLinesCount) {
                    $check = false;
                    continue;
                }
                $return .= implode('', $buff);
                $check = true;
                $return .= $line;
            }
            fclose($fp);
            return $return;
        }

        private function putBeforeInContent($filePath, $searches, $replaces) {
            if(!($fp = fopen($filePath, "r"))) return false;
            $searches = (array)$searches;
            $replaces = (array)$replaces;
            $return = '';
            while (($line = fgets($fp)) !== false) {
                foreach($searches as $index => $search) {
                    if(strpos($line, $search) === false) continue;
                    $return .= isset($replaces[$index])? $replaces[$index] : $replaces[0];
                }
                $return .= $line;
            }
            fclose($fp);
            return $return;
        }


        private function dbMigrateDir($migrationsFilePath) {
            foreach(glob($migrationsFilePath.DIRECTORY_SEPARATOR.'*.php') as $migrationFile) {
                $command = Package::replaceEnvCommand('php artisan migrate --realpath --path="'.$migrationFile.'" -n',
                    base_path()//where to search for commands_replace_env.php file
                );
                $this->execCommand($command, true);
            }
        }


        private function dbMigrateRollbackDir($migrationsFilePath) {
            foreach(glob($migrationsFilePath.DIRECTORY_SEPARATOR.'*.php') as $migrationFile) {
                $command = Package::replaceEnvCommand('php artisan migrate:refresh --realpath --path="'.$migrationFile.'" -n',
                    base_path()//where to search for commands_replace_env.php file
                );
                $this->execCommand($command, true);
                $command = Package::replaceEnvCommand('php artisan migrate:rollback --realpath --path="'.$migrationFile.'" -n',
                    base_path()//where to search for commands_replace_env.php file
                );
                $this->execCommand($command, true);
            }
        }



    }

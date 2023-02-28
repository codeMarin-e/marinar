<?php

namespace Marinar\Marinar\Traits;

use Illuminate\Support\Str;
use Marinar\Marinar\Models\PackageBase as Package;
use Illuminate\Support\Facades\Process;

trait MarinarSeedersTrait {

    public static $addons = null; //for the stubs and addons map
    public static $remove_addons = null; //for removing contents addons map
    public static $addon_on_addons = []; //for addon on addon files

    public $refComponents = null; //for the interface

    public static $packageName;
    public static $packageDir;

    private function getRefComponents() {
        $Reflection = new \ReflectionProperty(get_class($this->command), 'components');
        $Reflection->setAccessible(true);
        $this->refComponents = $Reflection->getValue($this->command);
    }

    private function execCommand($command, $show = false, $output = false, $workingDir = null) {
        $process = Process::forever()->path($workingDir?? base_path())->run( $command );
        // $process->tty();
        // executes after the command finishes
        if ($process->failed()) {
            return false;
            throw new \Illuminate\Process\Exceptions\ProcessFailedException($process);
        }
        if($show) echo $process->output();
        if($output) return $process->output();
        return true;
    }

    private function copyStubs($copyDir, $force = false) {
        $mainDir = dirname( base_path() );
        $force = $force? 'f' : 'n';
        $command = Package::replaceEnvCommand("cp -r{$force} '{$copyDir}".DIRECTORY_SEPARATOR.".' '{$mainDir}'",
            base_path()//where to search for commands_replace_env.php file
        );
        $this->refComponents->task("Coping files [$command]", function() use ($command){
            return $this->execCommand($command);
        });
    }

    private function marinarStubFromGitVersion($version) {
        $this->clearMarinarStubs();
        $vendorPackageDir = dirname( static::$packageDir );
        //check version exists in the package
        $command = Package::replaceEnvCommand("git tag -l 'v{$version}'",
            base_path()//where to search for commands_replace_env.php file
        );
        $response = $this->execCommand($command, output: true, workingDir: $vendorPackageDir);
        if(trim($response) != 'v'.$version) return false;

        $oldStubsPath = implode(DIRECTORY_SEPARATOR, [ base_path(), 'storage', 'marinar_stubs', static::$packageName]);
        $versionStubPackageDir = implode(DIRECTORY_SEPARATOR, [ $oldStubsPath, 'v'.$version, 'src']);
        $versionStubPackageStubs = implode(DIRECTORY_SEPARATOR, [ $versionStubPackageDir, 'stubs']);
        $versionStubPackageHooks = implode(DIRECTORY_SEPARATOR, [ $versionStubPackageDir, 'hooks']);

        //make directory for marinar_stub, clone tag version, copy stubs in the marinar_stub package dir
        $command = Package::replaceEnvCommand("mkdir -p '{$oldStubsPath}' && cd '{$oldStubsPath}' && ".
            "git clone '{$vendorPackageDir}' 'v{$version}' && ".
            "cp -rf '{$versionStubPackageStubs}".DIRECTORY_SEPARATOR.".' '{$oldStubsPath}'",
            base_path()//where to search for commands_replace_env.php file
        );
        if(!$this->execCommand($command, workingDir: dirname($oldStubsPath))) return false;
        //check for hooks
        if(realpath($versionStubPackageHooks)) {
            $command = Package::replaceEnvCommand("cp -rf '{$versionStubPackageHooks}' '{$oldStubsPath}'",
                base_path()//where to search for commands_replace_env.php file
            );
            $this->execCommand($command);
        }
        //remove the cloned folder
        $command = Package::replaceEnvCommand("rm -rf '".dirname($versionStubPackageDir)."'",
            base_path()//where to search for commands_replace_env.php file
        );
        $this->execCommand($command);
        $this->givePermissions( $oldStubsPath ); //give permissions
        file_put_contents($oldStubsPath.DIRECTORY_SEPARATOR.'version.php', "<?php \nreturn '{$version}';");
        return true;
    }

    private function checkMarinarStubs() {
        $oldStubsVersion = implode(DIRECTORY_SEPARATOR, [ base_path(), 'storage', 'marinar_stubs', static::$packageName, 'version.php']);
        if(!realpath($oldStubsVersion)) {
            if(config(static::$packageName.'.version') == 'dev-main') {
                $this->copyToMarinarStubs();
                $this->copyToMarinarHooks();
                return true;
            }
            return $this->marinarStubFromGitVersion( config(static::$packageName.'.version'));
        }
        $oldStubVersion = include $oldStubsVersion;
        if(config(static::$packageName.'.version') == $oldStubVersion) return true;
        return $this->marinarStubFromGitVersion( config(static::$packageName.'.version'));
    }

    private function stubFiles() {
        $stubsPath = implode(DIRECTORY_SEPARATOR, [ static::$packageDir, 'stubs' ]);
        if(!realpath($stubsPath)) return;
        $installedVersion = static::marinarPackageVersion(static::$packageName);
        if(realpath(base_path().DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.static::$packageName.'.php')) { //updating
            $oldVersion = config(static::$packageName.'.version');
            if(!$this->checkMarinarStubs()) throw new \Exception('No marinar stub folder for: '.static::$packageName);
            $this->setVersion($installedVersion);
            $this->updateStubFiles();
            if($installedVersion == 'dev-main' || $installedVersion != $oldVersion) {
                $this->copyToMarinarStubs($installedVersion);
            }
            return;
        }
        //first install
        $this->copyStubs($stubsPath,force: true);
        $this->setVersion($installedVersion);
        $this->copyToMarinarStubs($installedVersion);
    }

    public static function marinarPackageVersion($packageName) {
        $packageName = str_replace('marinar_', '', $packageName);
        $installed = implode(DIRECTORY_SEPARATOR, [
            base_path(), 'vendor', 'composer', 'installed.json'
        ]);
        if(!realpath($installed)) return false;
        if(!($installed = json_decode(file_get_contents($installed), true))) return false;
        if(!isset($installed['packages'])) return false;
        foreach($installed['packages'] as $package) {
            if(!isset($package['name'])) continue;
            if($package['name'] == 'marinar/'.$packageName) {
                return $package['version']?? false;
            }
        }
        return false;
    }

    private function updateStubFiles() {
        static::$addons = [];
        static::$remove_addons = [];
        $filesForUpdate = static::cleanStubsForUpdate(
            static::$packageDir.DIRECTORY_SEPARATOR.'stubs',
            static::$packageDir.DIRECTORY_SEPARATOR.'stubs',
            static::$packageName
        );
//        $this->copyStubs(static::$packageDir.DIRECTORY_SEPARATOR.'stubs'); //trying only with file_put_contents in cleanStubsForUpdate
        $this->callStubsAddonsRemove($filesForUpdate);
        $this->injectStubsAddons($filesForUpdate);
    }

    private function setVersion($packageVersion) {
        $nowVersion = config(static::$packageName.'.version', false);
        $configPath = base_path( 'config'.DIRECTORY_SEPARATOR.static::$packageName.'.php');
        $configContent = @file_get_contents( $configPath );
        if($nowVersion === false) {
            $search = "return [";
            $replace = "return [\n\t'version' => '{$packageVersion}',\n";
        } else {
            $search = [
                "'version' => '{$nowVersion}'",
                "\"version\" => \"{$nowVersion}\"",
                "'version'=>'{$nowVersion}'",
                "\"version\"=>\"{$nowVersion}\"",
                "'version' => \"{$nowVersion}\"",
                "\"version\" => '{$nowVersion}'",
                "'version'=>\"{$nowVersion}\"",
                "\"version\"=>'{$nowVersion}'",
            ]; //maybe is better with regular expression
            $replace = "'version' => '{$packageVersion}'";
        }
        @file_put_contents( $configPath, str_replace($search, $replace, $configContent) );
    }

    private function removeVersion() {
        $nowVersion = config(static::$packageName.'.version', false);
        $configPath = base_path( 'config'.DIRECTORY_SEPARATOR.static::$packageName.'.php');
        $configContent = @file_get_contents( $configPath );
        if($nowVersion === false) return;
        $search = [
            "'version' => '{$nowVersion}',\n",
            "\"version\" => \"{$nowVersion}\",\n",
            "'version'=>'{$nowVersion}',\n",
            "\"version\"=>\"{$nowVersion}\",\n",
            "'version' => \"{$nowVersion}\",\n",
            "\"version\" => '{$nowVersion}',\n",
            "'version'=>\"{$nowVersion}\",\n",
            "\"version\"=>'{$nowVersion}',\n",
            "'version' => '{$nowVersion}', \n",
            "\"version\" => \"{$nowVersion}\", \n",
            "'version'=>'{$nowVersion}', \n",
            "\"version\"=>\"{$nowVersion}\", \n",
            "'version' => \"{$nowVersion}\", \n",
            "\"version\" => '{$nowVersion}', \n",
            "'version'=>\"{$nowVersion}\", \n",
            "\"version\"=>'{$nowVersion}', \n",
        ]; //maybe is better with regular expression
        $replace = "";
        @file_put_contents( $configPath, str_replace($search, $replace, $configContent) );
    }

    private static function cleanStubsForUpdate($path, $copyDir, $packageName) {
        $return = [];
        $excludeStubs = config($packageName.'.exclude_stubs', []);
        $valuesStubs = config($packageName.'.values_stubs', []);
        foreach(new \DirectoryIterator($path) as $path) {
            if($path->isDot() === true) continue;
            $path = $path->getRealPath();
            $appPath = str_replace(
                DIRECTORY_SEPARATOR.'project'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'project'.DIRECTORY_SEPARATOR,
                DIRECTORY_SEPARATOR.'project'.DIRECTORY_SEPARATOR,
                base_path('..' . DIRECTORY_SEPARATOR . substr($path, strlen($copyDir) + 1)) );

            $createUpdateStub = false;
            if (in_array($appPath, $excludeStubs)) continue; //do not update
            if (is_dir($path)) {
                $return = array_merge($return , static::cleanStubsForUpdate($path, $copyDir, $packageName));
                if (is_dir($appPath) && (iterator_count(new \DirectoryIterator($appPath)) - 2) === 0) {
                    rmdir($appPath);
                }
                continue;
            }
            if(realpath($appPath)) {
                if (!is_file($appPath)) continue;
                //stubs that return configurable array
                if (in_array($appPath, $valuesStubs) || dirInArray(dirname($appPath), $valuesStubs)) {
                    $pathArray = include($path);
                    $appPathArray = include($appPath);
                    if (!checkConfigFileForUpdate($appPathArray, $pathArray)) continue; //there is no new config key
                    $createUpdateStub = true;
                    $pathContent = file_get_contents($path);
                } else {  //end stubs that return configurable array
                    $fileContent = file_get_contents($appPath);
                    $startComment = $endComment = false;
                    foreach (config('marinar.ext_comments') as $endsWith => $commentType) {
                        if (!Str::endsWith($appPath, $endsWith)) continue;
                        $startComment = str_replace('__COMMENT__', '@ADDON', $commentType) . "\n";
                        $endComment = str_replace('__COMMENT__', '@END_ADDON', $commentType) . "\n";
                        break;
                    }
                    if ($startComment !== false) { //addonable file
                        //add slashes for the regular expression
                        $startAddon = $endAddon = "";
                        for ($index = 0; $index < strlen($startComment); $index++) {
                            if (in_array($startComment[$index], ["/", "{", "-", '@'])) $startAddon .= "\\";
                            $startAddon .= $startComment[$index];
                        }
                        for ($index = 0; $index < strlen($endComment); $index++) {
                            if (in_array($endComment[$index], ["/", "{", "-", '@'])) $endAddon .= "\\";
                            $endAddon .= $endComment[$index];
                        }

                        //FOR REMOVES
                        foreach (config($packageName . '.addons') as $addonMainClass) {
                            if (!method_exists($addonMainClass, 'injects')) continue;
                            $injectAddonClass = $addonMainClass::injects();
                            if (!method_exists($injectAddonClass, 'addonsRemoveMap')) continue;
                            $injectAddonClass::configure();
                            $injectAddonClass::addonsRemoveMap(useExcludes: false); //still need to replace if something is left
                            if (!isset($injectAddonClass::$remove_addons[$appPath])) continue;
                            $fileContent = static::replaceInContent($fileContent,
                                array_keys($injectAddonClass::$remove_addons[$appPath]),
                                array_values($injectAddonClass::$remove_addons[$appPath])
                            );
                            static::$remove_addons[$appPath] = static::$remove_addons[$appPath]?? [];
                            static::$remove_addons[$appPath][$index] = $injectAddonClass; //just reusing to safe memory - cleaned in @updateStubFiles
                        }
                        //END FOR REMOVES

                        preg_match_all("/([ \t])*(" . $startAddon . ")([\s\S]*?)(" . $endAddon . ")/", $fileContent, $foundAddons);
                        if (isset($foundAddons[0]) && !empty($foundAddons[0])) { //found @ADDONS
                            $fileContent = str_replace($foundAddons[0], "", $fileContent);
                            $alreadyUsedHooks = [];
                            foreach ($foundAddons[0] as $index => $addonScript) {
                                $addonScript = str_replace([' ', "\t", "\n"], '', $addonScript);
                                foreach (config($packageName . '.addons') as $addonMainClass) {
                                    if (!method_exists($addonMainClass, 'injects')) continue;
                                    $injectAddonClass = $addonMainClass::injects();
                                    if (!method_exists($injectAddonClass, 'addonsMap')) continue;
                                    $injectAddonClass::configure();
                                    $injectAddonClass::addonsMap(useExcludes: false); //do not inject but still need to replace
                                    if (!isset($injectAddonClass::$addons[$appPath])) continue;

                                    foreach ($injectAddonClass::$addons[$appPath] as $hook => $hookAddonScript) {
                                        if (isset($alreadyUsedHooks[$injectAddonClass]) && in_array($hook, $alreadyUsedHooks[$injectAddonClass])) continue; //for same content hooks
                                        $hookAddonScript = str_replace([' ', "\t", "\n"], '', $startComment . $hookAddonScript . $endComment);
                                        if ($hookAddonScript === $addonScript) {
                                            //addon is from this class(package)
                                            static::$addons[$appPath] = static::$addons[$appPath]?? [];
                                            static::$addons[$appPath][$index] = [  //just reusing to safe memory - cleaned in @updateStubFiles
                                                'class' => $injectAddonClass,
                                                'hook' => $hook
                                            ];
                                            $alreadyUsedHooks[$injectAddonClass][] = $hook;
                                            break;
                                        }
                                    }

                                }
                            }
                        }

                        //FOR ADDON ON ADDON
                        preg_match_all("/[ \t]*\{\{\-\-  @REMOVE[\s\S]*?\-\-\}\}/", $fileContent, $foundAddons);
                        if (isset($foundAddons[0]) && !empty($foundAddons[0])) { //found ADDON_ON_ADDON
                            $fileContent = str_replace($foundAddons[0], "", $fileContent);
                        }
                        //END FOR ADDON ON ADDON

                    }
                    $fileContent = str_replace([' ', "\t", "\n"], '', $fileContent);

                    //if there is a stub file - it should have, but just for safety
                    $oldStubPath = implode(DIRECTORY_SEPARATOR, [
                        base_path(), 'storage', 'marinar_stubs', $packageName, substr($path, strlen($copyDir) + 1)
                    ]);
                    if (realpath($oldStubPath)) {
                        if ($fileContent !== str_replace([' ', "\t", "\n"], '', file_get_contents($oldStubPath))) {
                            //cannot update - file is changed
                            $createUpdateStub = true;
                        }
                    }

                    //NOT REALLY NECESSARY - NEED TO CHECK WHICH IS FASTER AND TAKE LESS MEMORY (THIS OR UNLINK-COPY-ADDON)
                    $pathContent = file_get_contents($path);
                    if ($fileContent === str_replace([' ', "\t", "\n"], '', $pathContent)) {
                        if (isset(static::$addons[$appPath])) {
                            static::$addons[$appPath] = []; //static properties cannot be unset
                        }
                        //do not update - file is same
                        continue;
                    }
                }
            } else {
                if(!realpath(dirname($appPath))) {
                    $old = umask(0);
                    mkdir(dirname($appPath), 0777, true);
                    umask($old);
                }
                $pathContent = file_get_contents($path);
            }

            //create manual update stub file - file is changed
            if($createUpdateStub) {
                $updateStubPath = implode( DIRECTORY_SEPARATOR, [
                    base_path(), 'updates', $packageName, substr($path, strlen($copyDir) + 1)
                ]);
                if(!realpath(dirname($updateStubPath))) {
                    $old = umask(0);
                    mkdir(dirname($updateStubPath), 0777, true);
                    umask($old);
                }
                file_put_contents($updateStubPath, $pathContent);
                $return[] = $appPath;
                echo "UPDATE STUB WAS CREATED: ".$appPath.PHP_EOL;
                continue;
            }

            //delete the file - next command will add the updated stub
//            unlink($appPath);

            //NEED TO CHECK WHICH IS MORE EFFICIENT - DIRECTLY HERE OR UNLINK-COPY
            @file_put_contents($appPath, $pathContent);
        }
        return $return; //files for update
    }

    private function injectStubsAddons($filesForUpdate = []) {
        foreach(static::$addons as $appPath => $classHookData) {
            if(in_array($appPath, $filesForUpdate)) continue;
            $this->refComponents->task("Inject addon - ".basename($appPath), function() use ($appPath, $classHookData){
                foreach($classHookData as $classHookArr) {
                    $className = $classHookArr['class'];
                    if(!method_exists($className, 'injectAddon')) continue;
                    $className::configure();
                    $className::injectAddon($appPath, $classHookArr['hook']);
                }
                return true;
            });
        }
    }

    private function callStubsAddonsRemove($filesForUpdate = []) {
        foreach(static::$remove_addons as $appPath => $classData) {
            if(in_array($appPath, $filesForUpdate)) continue;
            $this->refComponents->task("Addon remove - ".basename($appPath), function() use ($appPath, $classData){
                foreach($classData as $className) {
                    if(!method_exists($className, 'addonRemoveForPath')) continue;
                    $className::configure();
                    $className::addonRemoveForPath($appPath);
                }
                return true;
            });
        }
    }

    private function clearFiles() {
        $this->refComponents->task("Clear stubs", function() {
            $this->removeVersion();
            $stubDir = static::$packageDir.DIRECTORY_SEPARATOR.'stubs';
            static::removeStubFiles($stubDir,
                $stubDir,
                deleteBehavior: config(static::$packageName.'.delete_behavior'),
                showLogs: true);
            return true;
        });
        $this->clearMarinarStubs();
    }

    private static function removeStubFiles($path, $copyDir, $deleteBehavior = false, $showLogs = false) {
        if(is_numeric($deleteBehavior)) return;
        foreach(new \DirectoryIterator($path) as $path) {
            if($path->isDot() === true) continue;
            $path = $path->getRealPath();
            $appPath = base_path( '..'.DIRECTORY_SEPARATOR.substr($path, strlen($copyDir)+1) );
            if(is_dir($path)) {
                static::removeStubFiles($path, $copyDir, $deleteBehavior, $showLogs);
                if(is_dir($appPath) && (iterator_count(new \DirectoryIterator($appPath))-2) === 0 ) {
                    rmdir($appPath);
                }
                continue;
            }
            if($deleteBehavior === true || !is_file($appPath)) {
                unlink($appPath);
                continue;
            }
            //stubs that return configurable array
            $valuesStubs = config(static::$packageName.'.values_stubs', []);
            if(in_array($appPath, $valuesStubs) || dirInArray(dirname($appPath), $valuesStubs)) {
                $oldStubPath = implode( DIRECTORY_SEPARATOR, [
                    base_path(), 'storage', 'marinar_stubs', static::$packageName, substr($path, strlen($copyDir) + 1)
                ]);
                if(realpath($oldStubPath)) {
                    $oldArray = include($oldStubPath);
                    $appPathArray = include($appPath);
                    if(checkConfigFileForUpdate($oldArray, $appPathArray)) { //there is new config key
                        if($showLogs) {
                            echo PHP_EOL."Not deleted: ".$appPath;
                        }
                        continue;
                    }
                    //there is no new config key
                }
                unlink($appPath);
                continue;
            }
            $fileContent = file_get_contents($appPath);
            $startComment = $endComment = false;
            foreach(config('marinar.ext_comments') as $endsWith => $commentType) {
                if(!Str::endsWith($appPath, $endsWith)) continue;
                $startComment = str_replace('__COMMENT__', '@ADDON', $commentType)."\n";
                $endComment = str_replace('__COMMENT__', '@END_ADDON', $commentType)."\n";
                break;
            }
            if($startComment !== false) { //addonable file
                //add slashes for the regular expression
                $startAddon = $endAddon = "";
                for ($index = 0; $index < strlen($startComment); $index++) {
                    if (in_array($startComment[$index], ["/", "{", "-", '@'])) $startAddon .= "\\";
                    $startAddon .= $startComment[$index];
                }
                for ($index = 0; $index < strlen($endComment); $index++) {
                    if (in_array($endComment[$index], ["/", "{", "-", '@'])) $endAddon .= "\\";
                    $endAddon .= $endComment[$index];
                }

                preg_match_all("/([ \t])*(" . $startAddon . ")([\s\S]*?)(" . $endAddon . ")/", $fileContent, $foundAddons);
                if(isset($foundAddons[0]) && !empty($foundAddons[0])) { //found @ADDONS
                    $fileContent = str_replace($foundAddons[0], "", $fileContent);
                }
            }

            $fileContent = str_replace([' ', "\t", "\n"], '', $fileContent);

            //if there is a stub file - it should have, but just for safety
            $oldStubPath = implode( DIRECTORY_SEPARATOR, [
                base_path(), 'storage', 'marinar_stubs', static::$packageName, substr($path, strlen($copyDir) + 1)
            ]);
            if(realpath($oldStubPath)) {
                if($fileContent !== str_replace([' ', "\t", "\n"], '', file_get_contents($oldStubPath))) {
                    if($showLogs) {
                        echo PHP_EOL."Not deleted: ".$appPath;
                    }
                    continue;
                }
            }
            unlink($appPath);
        }
    }

    private function clearMarinarStubs() {
        $oldStubsPath = implode( DIRECTORY_SEPARATOR, [ base_path(), 'storage', 'marinar_stubs', static::$packageName ]);
        if(!realpath($oldStubsPath)) return;
        $command = Package::replaceEnvCommand("rm -rf '{$oldStubsPath}'",
            base_path()//where to search for commands_replace_env.php file
        );
        $this->refComponents->task("Delete marinar_stubs [$command]", function() use ($command){
            return $this->execCommand($command);
        });
    }

    private function copyToMarinarStubs($installedVersion) {
        $this->clearMarinarStubs();
        $copyDir = static::$packageDir.DIRECTORY_SEPARATOR.'stubs';
        $oldStubsPath = implode( DIRECTORY_SEPARATOR, [ base_path(), 'storage', 'marinar_stubs', static::$packageName ]);
        $command = Package::replaceEnvCommand("mkdir -p '{$oldStubsPath}' && cp -rf '{$copyDir}".DIRECTORY_SEPARATOR.".' '{$oldStubsPath}'",
            base_path()//where to search for commands_replace_env.php file
        );
        $this->refComponents->task("Coping marinar_stubs [$command]", function() use ($command, $oldStubsPath, $installedVersion){
            if(!$this->execCommand($command)) return false;
            $this->givePermissions( $oldStubsPath );
            file_put_contents($oldStubsPath.DIRECTORY_SEPARATOR.'version.php', "<?php \nreturn '{$installedVersion}';");
            return true;
        });
    }

    private function copyToMarinarHooks() {
        $hooksPath = static::$packageDir.DIRECTORY_SEPARATOR.'hooks';
        if(!realpath($hooksPath)) return;
        $oldStubsHookPath = implode( DIRECTORY_SEPARATOR, [ base_path(), 'storage', 'marinar_stubs', static::$packageName, 'hooks' ]);
        $this->refComponents->task("Coping hooks to marinar_stubs", function() use ($hooksPath, $oldStubsHookPath){
            if(realpath($oldStubsHookPath)) {
                $command = Package::replaceEnvCommand("rm -rf '{$oldStubsHookPath}'",
                    base_path()//where to search for commands_replace_env.php file
                );
                $this->execCommand($command);
            }
            $command = Package::replaceEnvCommand("cp -rf '{$hooksPath}".DIRECTORY_SEPARATOR.".' '{$oldStubsHookPath}'",
                base_path()//where to search for commands_replace_env.php file
            );
            if(!$this->execCommand($command)) return false;
            $this->givePermissions( $oldStubsHookPath );
            return true;
        });
    }

    public static function putInHook($line, $searches, $replaces, $startComment, $endComment) {
        $return = [];
        foreach($searches as $index => $search) {
            if(str_replace([' ', "\t", "\n"], '',$line) !== str_replace([' ', "\t", "\n"], '', $search)) continue;
            $add = trim(isset($replaces[$index])? $replaces[$index] : $replaces[0], "\n\r");
            $add = explode("\n", $add);

            $spaces = '';
            for ($index = 0; $index < strlen($line); $index++) {
                if ($line[$index] !== " " && $line[$index] !== "\t") break;
                $spaces .= $line[$index];
            }
            $addSpaces = '';
            for ($index = 0; $index < strlen($add[0]); $index++) {
                if ($add[0][$index] !== " " && $add[0][$index] !== "\t") break;
                $addSpaces .= $add[0][$index];
            }
            foreach($add as $index => $addLine) {
                $add[$index] = Str::startsWith($addLine, $addSpaces)?
                    Str::replaceFirst($addSpaces, $spaces, $addLine) : $spaces.$addLine;
            }
            if($startComment) {
                $return[] = $spaces.$startComment;
            }
            $return = array_merge($return, $add);

            if($endComment) {
                $return[] = $spaces.$endComment;
            }
        }
        return $return;
    }

    public static function putBeforeInText($text, $searches, $replaces, $putCommentFilePath = false) {
        $startComment = $endComment = '';
        if($putCommentFilePath) {
            foreach (config('marinar.ext_comments') as $endsWith => $commentType) {
                if (!Str::endsWith($putCommentFilePath, $endsWith)) continue;
                $startComment = str_replace('__COMMENT__', '@ADDON', $commentType);
                $endComment = str_replace('__COMMENT__', '@END_ADDON', $commentType);
                break;
            }
        }

        $text = explode("\n" , trim($text, "\n\r"));
        $searches = (array)$searches;
        $replaces = (array)$replaces;
        $return = [];
        foreach($text as $line) {
            $lineHooked = static::putInHook($line, $searches, $replaces, $startComment, $endComment);
            if(!empty($lineHooked)) {
                $return = array_merge($return, $lineHooked);
            }
            $return[] = $line;
        }
        return implode("\n", $return)."\n";
    }

    public static function putBeforeInContent($filePath, $searches, $replaces, $putComments = true) {
        if(!($fp = fopen($filePath, "r"))) return false;
        $startComment = $endComment = '';
        foreach(config('marinar.ext_comments') as $endsWith => $commentType) {
            if(!Str::endsWith($filePath, $endsWith)) continue;
            $startComment = str_replace('__COMMENT__', '@ADDON', $commentType);
            $endComment = str_replace('__COMMENT__', '@END_ADDON', $commentType);
            break;
        }
        $searches = (array)$searches;
        $replaces = (array)$replaces;
        $return = '';
        while (($line = fgets($fp)) !== false) {
            $lineHooked = static::putInHook($line, $searches, $replaces, $startComment, $endComment);
            $return .= empty($lineHooked)? $line : implode("\n", $lineHooked)."\n".$line;
        }
        fclose($fp);
        return $return;
    }

    public static function injectAddon($filePath, $hook) {
        static::addonsMap();
        if(!isset(static::$addons[$filePath]) || !isset(static::$addons[$filePath][$hook]))
            return false;
        if(!realpath($filePath)) return false;
        if(!file_put_contents($filePath, static::putBeforeInContent(
            $filePath, $hook, static::$addons[$filePath][$hook]
        ))) return false;
        return true;
    }

    public function injectAddons() {
        $hooksPath = static::$packageDir.DIRECTORY_SEPARATOR.'hooks';
        if(!realpath($hooksPath)) return;
        static::addonsMap();
        foreach(static::$addons as $filePath => $hookAddons) {
            if(!is_array($hookAddons) || empty($hookAddons)) continue;
            $this->refComponents->task("Inject addons - ".basename($filePath), function() use ($filePath, $hookAddons){
                if(!realpath($filePath)) return false;
                if(!file_put_contents($filePath, static::putBeforeInContent(
                    $filePath, array_keys($hookAddons), array_values($hookAddons)
                ))) return false;
                return true;
            });

        }
    }

    private function dbMigrate() {
        $migrationsPath = implode(DIRECTORY_SEPARATOR, [
            static::$packageDir, 'stubs', 'project', 'database', 'migrations',
        ]);
        if(!realpath($migrationsPath)) return;
        $this->dbMigrateDir($migrationsPath);
    }

    private function dbMigrateDir($migrationsFilePath) {
        foreach(glob($migrationsFilePath.DIRECTORY_SEPARATOR.'*.php') as $migrationFile) {
            $command = Package::replaceEnvCommand('php artisan migrate --realpath --path="'.$migrationFile.'" -n',
                base_path()//where to search for commands_replace_env.php file
            );
            $this->execCommand($command, true);
        }
    }

    private function dbMigrateRollback() {
        $migrationsPath = implode(DIRECTORY_SEPARATOR, [
            static::$packageDir, 'stubs', 'project', 'database', 'migrations',
        ]);
        if(!realpath($migrationsPath)) return;
        $this->dbMigrateRollbackDir($migrationsPath);
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

    private function givePermissions($dir, $show = false) {
        $command = Package::replaceEnvCommand("chmod -R 777 '{$dir}'");
        if($show) {
            $this->refComponents->task("Give permissions [$dir]", function () use ($command) {
                return $this->execCommand($command);
            });
            return;
        }
        return $this->execCommand($command);
    }


    private function giveGitPermissions() {
        $packageVendorDir = static::$packageDir.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'.git'.DIRECTORY_SEPARATOR.'objects';
        $this->refComponents->task("GIT remove fix", function() use ($packageVendorDir){
            return $this->givePermissions($packageVendorDir);
        });
    }

    public static function pureAddonsMap() {
        $mapPath = static::$packageDir.DIRECTORY_SEPARATOR.'hooks'.DIRECTORY_SEPARATOR.'map.php';
        if(!realpath($mapPath)) return [];
        return include($mapPath);
    }

    public static function addonsMap($useExcludes = true) {
        if(!is_null(static::$addons)) return static::$addons;
        $excludeInjects = config(static::$packageName.'.exclude_injects', []);//exclude for injections
        $return = method_exists(static::class, 'pureAddonsMap')? static::pureAddonsMap() : [];
        foreach($return as $appPath => $hookAddons) {
            if($useExcludes && isset($excludeInjects[$appPath]) && $excludeInjects[$appPath] == '*') {
                unset($return[$appPath]); continue;
            }
            foreach($hookAddons as $hook => $addonContent) {
                if($useExcludes && isset($excludeInjects[$appPath]) && in_array($hook, $excludeInjects[$appPath])) {
                    unset($return[$appPath][$hook]);
                    if(empty($return[$appPath])) unset($return[$appPath]);
                    continue;
                }
                if(Str::startsWith($addonContent, dirname( base_path() ))) { //content is in hook file
                    $return[$appPath][$hook] = str_replace(["<?php\n", "<?php \n", "<?php\r\n", "<?php \r\n"], '', file_get_contents($addonContent));
                }
            }
        }
        return (static::$addons = $return);
    }

    private function updateAddonInjects($clear = false) {
        $hooksPath = static::$packageDir.DIRECTORY_SEPARATOR.'hooks';
        if(!realpath($hooksPath)) return;
        if(!realpath(base_path().DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.static::$packageName.'.php')) { //not updating
            return;
        }
        static::addonsMap();
        if($clear) static::$addons = []; //clear addons

        $oldStubsHookPath = implode( DIRECTORY_SEPARATOR, [ base_path(), 'storage', 'marinar_stubs', static::$packageName, 'hooks' ]);
        $oldMapPath = $oldStubsHookPath.DIRECTORY_SEPARATOR.'map.php';
        if(!realpath($oldMapPath)) return;
        $oldMap = include $oldMapPath;
        //exclude for injections
        if(!$clear) { //when removing - remove excluded, too - it may be talked about
            foreach (config(static::$packageName . '.exclude_injects', []) as $filePath => $excludedHooks) {
                if (isset($oldMap[$filePath]) && $excludedHooks == '*') {
                    unset($oldMap[$filePath]);
                    continue;
                }
                foreach ($excludedHooks as $hook) {
                    if (!isset($oldMap[$filePath][$hook])) continue;
                    unset($oldMap[$filePath][$hook]);
                    if(empty($oldMap[$filePath])) unset($oldMap[$filePath]);
                }
            }
        }
        //end exclude for injections

        foreach($oldMap as $filePath => $hookAddons) {
            if (!is_array($hookAddons) || empty($hookAddons)) continue;
            foreach($hookAddons as $hook => $addonContent) {
                if(Str::startsWith($addonContent, dirname( base_path() ))) { //content is in hook file
                    $addonContent = str_replace(static::$packageDir.DIRECTORY_SEPARATOR.'hooks', $oldStubsHookPath, $addonContent);
                    $oldMap[$filePath][$hook] = str_replace(["<?php\n", "<?php \n", "<?php\r\n", "<?php \r\n"], '', file_get_contents($addonContent));
                }
            }
        }

        foreach($oldMap as $filePath => $hookAddons) {
            $startComment = $endComment = false;
            foreach(config('marinar.ext_comments') as $endsWith => $commentType) {
                if(!Str::endsWith($filePath, $endsWith)) continue;
                $startComment = str_replace('__COMMENT__', '@ADDON', $commentType);
                $endComment = str_replace('__COMMENT__', '@END_ADDON', $commentType);
                break;
            }
            if($startComment === false) continue;//not addonable file
            if(!realpath($filePath)) continue;
            $fileContent = file_get_contents($filePath);

            //add slashes for the regular expression
            $startAddon = $endAddon = "";
            for($index = 0; $index < strlen($startComment); $index++){
                if(in_array($startComment[$index],["/", "{", "-", '@'])) $startAddon .= "\\";
                $startAddon .= $startComment[$index];
            }
            for($index = 0; $index < strlen($endComment); $index++){
                if(in_array($endComment[$index],["/", "{", "-", '@'])) $endAddon .= "\\";
                $endAddon .= $endComment[$index];
            }
            $contentChanged = false;
            $alreadyReplacedPureContents = []; //If two hook have same injection
            preg_match_all("/([ \t])*(".$startAddon.")([\s\S]*?)(".$endAddon.")/", $fileContent, $foundAddons);
            if(isset($foundAddons[0]) && !empty($foundAddons[0])) { //found @ADDONS
                foreach($hookAddons as $hook => $addonContent) {
                    $pureAddonContent = str_replace([" ", "\n", "\t", "\r"], '', $startComment.$addonContent.$endComment);
                    if(in_array($pureAddonContent, $alreadyReplacedPureContents)) { //for hooks with same content
                        //unset from static::$addons to not inject again
                        $buff = static::$addons;
                        unset($buff[$filePath][$hook]);
                        static::$addons = $buff;
                        continue;
                    }
                    foreach($foundAddons[0] as $index => $injectedAddon) {
                        if($pureAddonContent !== str_replace([" ", "\n", "\t", "\r"], '', $injectedAddon)) continue; //not same
                        if(in_array($pureAddonContent, $alreadyReplacedPureContents)) continue; //for hooks with same content
                        $alreadyReplacedPureContents[] = $pureAddonContent;
                        //found match
                        $newContent = '';
                        if(isset(static::$addons[$filePath]) && isset(static::$addons[$filePath][$hook])) {
                            if($pureAddonContent === str_replace([" ", "\n", "\t", "\r"], '', $startComment.static::$addons[$filePath][$hook].$endComment)) {//the new is same as old
                                unset($foundAddons[0][$index]);//make loops smaller
                                //unset from static::$addons to not inject again
                                $buff = static::$addons;
                                unset($buff[$filePath][$hook]);
                                static::$addons = $buff;
                                continue;
                            }
                            //put same spaces and tabs
                            $spaces = '';
                            for ($spaceIndex = 0; $spaceIndex < strlen($injectedAddon); $spaceIndex++) {
                                if ($injectedAddon[$spaceIndex] !== " " && $injectedAddon[$spaceIndex] !== "\t") break;
                                $spaces .= $injectedAddon[$spaceIndex];
                            }
                            $addonSpaces = '';
                            for ($spaceIndex = 0; $spaceIndex < strlen(static::$addons[$filePath][$hook]); $spaceIndex++) {
                                if (static::$addons[$filePath][$hook][$spaceIndex] !== " " && static::$addons[$filePath][$hook][$spaceIndex] !== "\t") break;
                                $addonSpaces .= static::$addons[$filePath][$hook][$spaceIndex];
                            }
                            $newContent = explode("\n", trim(static::$addons[$filePath][$hook], "\n\r"));
                            foreach($newContent as $rowIndex => $newContentRow) {
                                $newContent[$rowIndex] = Str::startsWith($newContent[$rowIndex], $addonSpaces)?
                                    Str::replaceFirst($addonSpaces, $spaces, $newContent[$rowIndex]) :
                                    $spaces.$newContent[$rowIndex];
                            }
                            $newContent = implode("\n", $newContent)."\n";
                            // end put same spaces and tabs

                            $newContent = $spaces.$startComment."\n".$newContent.$spaces.$endComment."\n";

                            //unset from static::$addons to not inject again
                            $buff = static::$addons;
                            unset($buff[$filePath][$hook]);
                            static::$addons = $buff;
                        }
                        $contentChanged = true;
                        $fileContent = str_replace($injectedAddon."\n", $newContent, $fileContent);

                        unset($foundAddons[0][$index]);//make loops smaller
                    }
                }
                if($contentChanged) {
                    $taskTitle = empty(static::$addons) ? "Addons cleared" : "Addons changed";
                    $this->refComponents->task("{$taskTitle} - ".basename($filePath), function() use ($filePath, $fileContent){
                        return file_put_contents($filePath, $fileContent);
                    });
                }

            }
        }
    }

    private function seedMe() {
        $namespacePackageName = ucfirst(str_replace('marinar_', '', static::$packageName));
        $seedersPath = implode(DIRECTORY_SEPARATOR, [
            static::$packageDir, 'stubs', 'project', 'database', 'seeders', 'Packages', $namespacePackageName
        ]);
        if(!realpath($seedersPath)) return;
        foreach(glob($seedersPath.DIRECTORY_SEPARATOR.'*.php') as $seedersFiles) {
            $seeder = str_replace('.php', '', basename($seedersFiles)); //may be better but it's fast solution
            $command = Package::replaceEnvCommand('php artisan db:seed --class="\\Database\\Seeders\\Packages\\'.$namespacePackageName.'\\'.$seeder.'"');
            $this->refComponents->task("Seeding DB [$command]", function() use ($command){
                return $this->execCommand($command);
            });
        }
    }

    private function prepareComposerJSON() {
        $mineComposer = json_decode(file_get_contents(static::$packageDir.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'composer.json'), true );
        $mergeArr = [
            'require' => [],
            'require-dev' => [],
        ];
        foreach($mineComposer['require']?? [] as $package => $version) {
            if(Str::startsWith($package, 'marinar/')) continue;
            $mergeArr['require'][$package] = $version;
        }
        foreach($mineComposer['require-dev']?? [] as $package => $version) {
            if(Str::startsWith($package, 'marinar/')) continue;
            $mergeArr['require-dev'][$package] = $version;
        }
        if(empty($mergeArr['require']) && empty($mergeArr['require-dev'])) return;
        $this->refComponents->task("Prepare composer.json", function() use ($mergeArr){
            $composerPath = base_path() . DIRECTORY_SEPARATOR . 'composer.json';
            if (!realpath($composerPath)) {
                return false;
            }
            if( ($composerJSON = file_get_contents( $composerPath )) === false ) {
                return false;
            }
            if(!($composerJSON = json_decode($composerJSON, true))) {
                return false;
            }
            $composerJSON = array_replace_recursive($composerJSON, array_replace_recursive($mergeArr, $composerJSON));
            if( !($composerJSON = json_encode($composerJSON, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
                return false;
            }

            if(file_put_contents($composerPath, $composerJSON) === false) {
                return false;
            }
        });
    }

    public static function pureAddonsRemoveMap() {
        $mapPath = static::$packageDir.DIRECTORY_SEPARATOR.'hooks'.DIRECTORY_SEPARATOR.'remove_map.php';
        if(!realpath($mapPath)) return [];
        return include($mapPath);
    }

    public static function addonsRemoveMap($useExcludes = true) {
        if(!is_null(static::$remove_addons)) return static::$remove_addons;
        $excludeRemoves = config(static::$packageName.'.exclude_removes', []);//exclude for removing
        $return = method_exists(static::class, 'pureAddonsRemoveMap')? static::pureAddonsRemoveMap() : [];
        foreach($return as $appPath => $hookAddons) {
            if($useExcludes && isset($excludeRemoves[$appPath]) && $excludeRemoves[$appPath] == '*') {
                unset($return[$appPath]); continue;
            }
            foreach($hookAddons as $hook => $removeContent) {
                if($useExcludes && isset($excludeRemoves[$appPath][$hook])) {
                    unset($return[$appPath][$hook]); continue;
                }
                if(Str::startsWith($removeContent, dirname( base_path() ))) { //content is in hook file
                    $return[$appPath][$hook] = str_replace(["<?php\n", "<?php \n", "<?php\r\n", "<?php \r\n"], '', file_get_contents($removeContent));
                }
            }
        }
        return (static::$remove_addons = $return);
    }

    private static function replaceInContent($content, $searches, $replaces) {
        $searches = (array)$searches;
        $replaces = (array)$replaces;

        $return = [];
        if(Str::startsWith($content, dirname( base_path() ))) { //content is in hook file
            if(!($fp = fopen($content, "r"))) return false;
            while (($line = fgets($fp)) !== false) {
                $return[] = $line;
            }
            fclose($fp);
        } else {
            $content = explode("\n", trim($content, "\n\r"));
            foreach($content as $line) $return[] = $line."\n";
        }
        foreach($searches as $index => $searchLines) {
            $searchLines = is_string($searchLines)? explode("\n", trim($searchLines, "\n\r")) : $searchLines;
            if(!is_array($searchLines) || empty($searchLines))continue;
            $pureSearchLine = str_replace([" ", "\n", "\t", "\r"], '', $searchLines[0]);
            $newReturn = [];
            for($i=0; $i<count($return); $i++) {
                $pureLine = str_replace([" ", "\n", "\t", "\r"], '', $return[$i]);
                if($pureLine !== $pureSearchLine) { $newReturn[] = $return[$i]; continue; } //not same with first line

                for($j=1; $j<count($searchLines);$j++) {
                    if(!isset($return[$i+$j])) { $newReturn[] = $return[$i]; continue 2; } //there is no rows left
                    $pureLine = str_replace([" ", "\n", "\t", "\r"], '', $return[$i+$j]);
                    $pureSearchLine2 = str_replace([" ", "\n", "\t", "\r"], '', $searchLines[$j]);
                    if($pureLine !== $pureSearchLine2) { $newReturn[] = $return[$i]; continue 2; } //not same part
                }
                //the part is same
                $replaces[$index] = is_string($replaces[$index])? explode("\n", trim($replaces[$index], "\n\r")) : $replaces[$index];
                $spaces = '';
                for ($y = 0; $y < strlen($return[$i]); $y++) {
                    if ($return[$i][$y] !== " " && $return[$i][$y] !== "\t") break;
                    $spaces .= $return[$i][$y];
                }
                $addonSpaces = '';
                for ($y = 0; $y < strlen($replaces[$index][0]); $y++) {
                    if ($replaces[$index][0][$y] !== " " && $replaces[$index][0][$y] !== "\t") break;
                    $addonSpaces .= $replaces[$index][0][$y];
                }
                foreach($replaces[$index] as $replaceLine) {
                    $newReturn[] = (Str::startsWith($replaceLine, $addonSpaces)?
                            Str::replaceFirst($addonSpaces, $spaces, $replaceLine) : $spaces.$replaceLine)."\n";
                }
                $i += count($searchLines)-1; //go to line after the part
            }
            $return = $newReturn;
        }
        return implode("", $return);
    }

    public static function addonRemoveForPath($filePath) {
        static::addonsRemoveMap();
        if(!isset(static::$remove_addons[$filePath]))
            return false;
        if(!realpath($filePath)) return false;
        $hookAddons = static::$remove_addons[$filePath];
        if(!file_put_contents($filePath, static::replaceInContent(
            $filePath, array_values($hookAddons), array_keys($hookAddons)
        ))) return false;
        return true;
    }

    private function addonRemove() {
        $hooksPath = static::$packageDir.DIRECTORY_SEPARATOR.'hooks';
        if(!realpath($hooksPath)) return;
        static::addonsRemoveMap();
        foreach(static::$remove_addons as $filePath => $hookAddons) {
            if(!is_array($hookAddons) || empty($hookAddons)) continue;
            $this->refComponents->task("Addon removing parts - ".basename($filePath), function() use ($filePath, $hookAddons){
                if(!realpath($filePath)) return false;
                if(!file_put_contents($filePath, static::replaceInContent(
                    $filePath, array_values($hookAddons), array_keys($hookAddons)
                ))) return false;
                return true;
            });
        }
    }

    private function addonRemoveUninstall() {
        $hooksPath = static::$packageDir.DIRECTORY_SEPARATOR.'hooks';
        if(!realpath($hooksPath)) return;
        static::addonsRemoveMap();
        foreach(static::$remove_addons as $filePath => $hookAddons) {
            if(!realpath($filePath)) return false;
            if(!file_put_contents($filePath, static::replaceInContent(
                $filePath, array_keys($hookAddons), array_values($hookAddons)
            ))) return false;
        }
    }

    private function triggerInstallAddons() {
        if(!realpath(base_path().DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.static::$packageName.'.php')) return;
        foreach (config(static::$packageName . '.addons') as $addonMainClass) {
            if(!method_exists($addonMainClass, 'triggeredInstalled')) continue;
            $addonMainClass::triggeredInstalled(static::class);
        }
    }

    /**
     * FOR PUTTING ADDON ON ALREADY ADDON - CHECK BEFORE ADDON FOR CONTENT - FOR hooks/map.php MAINLY
     */
    public static function addonOnAddon($otherMap, $filePath, $otherHook, $addonHook, $addonOnAddonContent, $mapDir, $oldHookFileName) {
        if(!\Illuminate\Support\Str::startsWith($mapDir, static::$packageDir)) {
            return $mapDir.DIRECTORY_SEPARATOR.$oldHookFileName;
        }
        $return = '';
        if(!realpath($otherMap)) return false;
        $otherMap = include $otherMap;
        if(!isset($otherMap[$filePath]) || !isset($otherMap[$filePath][$otherHook])) return false;
        $addonContent = $otherMap[$filePath][$otherHook];
        if (Str::startsWith($addonOnAddonContent, dirname(base_path()))) { //content is in hook file
            $addonOnAddonContent = str_replace(["<?php\n", "<?php \n", "<?php\r\n", "<?php \r\n"], '', file_get_contents($addonOnAddonContent));
        }
        $return = Str::startsWith($addonContent, dirname( base_path() ))? //content is in hook file
            static::putBeforeInContent($addonContent, [$addonHook],[$addonOnAddonContent], false) :
            static::putBeforeInText($addonContent, [$addonHook], [$addonOnAddonContent], $filePath);

        $oldCategoryHookTestPath = implode( DIRECTORY_SEPARATOR, [ base_path(), 'storage', 'marinar_stubs', $oldHookFileName  ]);
        file_put_contents($oldCategoryHookTestPath, $return);
        static::$addon_on_addons[] = $oldHookFileName;
        return $oldCategoryHookTestPath;
    }

    /**
     * FOR PUTTING ADDON ON ALREADY ADDON - SHOULD REMOVE THE FIRST ADDON CONTENT - FOR hooks/remove_map.php MAINLY
     */
    public static function removeAddonForAddon($otherMap, $filePath, $otherHook) {
        $return = '';
        if(!realpath($otherMap)) return false;
        $hooksMap = include $otherMap;
        if(!isset($hooksMap[$filePath]) || isset($hooksMap[$filePath][$otherHook])) return false;
        $addonContent = $hooksMap[$filePath][$otherHook];

        $return = \Illuminate\Support\Str::startsWith($addonContent, base_path())? //content is in hook file
            file_get_contents($addonContent) : $addonContent;
        $return = str_replace(["<?php\n", "<?php \n", "<?php\r\n", "<?php \r\n"], '', $return);
        $return = trim($return," \n\r")."\n";

        $startComment = $endComment = '';
        foreach(config('marinar.ext_comments') as $endsWith => $commentType) {
            if(!Str::endsWith($filePath, $endsWith)) continue;
            $startComment = str_replace('__COMMENT__', '@ADDON', $commentType);
            $endComment = str_replace('__COMMENT__', '@END_ADDON', $commentType);
            break;
        }
        if($startComment) {
            $return = $startComment."\n".$return;
        }
        if($endComment) {
            $return = $return.$endComment."\n";
        }
        return $return;
    }

    private function moveAddonOnAddonFiles() {
        foreach(static::$addon_on_addons as $fileName) {
            rename(
                implode( DIRECTORY_SEPARATOR, [ base_path(), 'storage', 'marinar_stubs', $fileName ]),
                implode( DIRECTORY_SEPARATOR, [ base_path(), 'storage', 'marinar_stubs', static::$packageName, 'hooks', $fileName ])
            );
        }
    }

    private function autoInstall() {
        static::configure();
        $this->getRefComponents();
        if(config(static::$packageName.'.install_behavior', true) !== false) { //do not install/update
            $this->addonRemove();
            $this->updateAddonInjects();
            $this->injectAddons();
            $this->stubFiles();
            $this->copyToMarinarHooks();
            $this->moveAddonOnAddonFiles();
            $this->dbMigrate();
            $this->seedMe();
            $this->prepareComposerJSON();
            if(method_exists($this, 'installMe')) $this->installMe();
            $this->triggerInstallAddons();
        }
        $this->giveGitPermissions();
    }

    private function autoRemove() {
        static::configure();
        $this->getRefComponents();
        if((int)config(static::$packageName.'.delete_behavior', false) === 2) return; //keep everything
        if(method_exists($this, 'clearMe')) $this->clearMe();
        $this->dbMigrateRollback();
        $this->addonRemoveUninstall();
        $this->updateAddonInjects(clear: true);
        $this->clearFiles();
    }

}

<?php

namespace Marinar\Marinar\Traits;

use Illuminate\Support\Str;
use Marinar\Marinar\Models\PackageBase as Package;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

trait MarinarSeedersTrait {

    public static $addons = null; //for the stubs and addons map

    public $refComponents = null; //for the interface

    public static $packageName;
    public static $packageDir;

    private function getRefComponents() {
        $Reflection = new \ReflectionProperty(get_class($this->command), 'components');
        $Reflection->setAccessible(true);
        $this->refComponents = $Reflection->getValue($this->command);
    }

    private function execCommand($command, $show = false, $output = false) {
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
        if($show) echo $process->getOutput();
        if($output) return $process->getOutput();
        return true;
    }

    private function copyStubs($copyDir, $force = false) {
        $mainDir = dirname( base_path() );
        $force = $force? 'f' : 'n';
        $command = Package::replaceEnvCommand("cp -r{$force} {$copyDir}".DIRECTORY_SEPARATOR.". {$mainDir}",
            base_path()//where to search for commands_replace_env.php file
        );
        $this->refComponents->task("Coping files [$command]", function() use ($command){
            return $this->execCommand($command);
        });
    }

    private function stubFiles() {
        $stubsPath = implode(DIRECTORY_SEPARATOR, [ static::$packageDir, 'stubs' ]);
        if(!realpath($stubsPath)) return;
        $installedVersion = static::marinarPackageVersion(static::$packageName);
        if(realpath(base_path().DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.static::$packageName.'.php')) { //updating
            if(config(static::$packageName.'.version') === $installedVersion) {
                return;
            }
            $this->setVersion($installedVersion);
            $this->updateStubFiles($installedVersion);
        } else { //first install
            $this->copyStubs($stubsPath,force: true);
            $this->setVersion($installedVersion);
        }
        $this->copyToMarinarStubs();
    }

    public static function marinarPackageVersion($packageName) {
        $installed = implode(DIRECTORY_SEPARATOR, [
            base_path(), 'vendor', 'composer', 'installed.json'
        ]);
        if(!realpath($installed)) return false;
        if(!($installed = @json_decode($installed, true))) return false;
        if(!isset($installed['packages'])) return false;
        foreach($installed['packages'] as $package) {
            if(!isset($package['name'])) continue;
            if($package['name'] == 'marinar/'.$packageName) {
                return $package['version']?? false;
            }
        }
        return false;
    }

    private function updateStubFiles($packageVersion) {
        static::cleanStubsForUpdate(
            static::$packageDir.DIRECTORY_SEPARATOR.'stubs',
            static::$packageDir.DIRECTORY_SEPARATOR.'stubs',
            static::$packageName
        );
        $this->copyStubs(static::$packageDir.DIRECTORY_SEPARATOR.'stubs');
        $this->injectStubsAddons();
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

    private static function cleanStubsForUpdate($path, $copyDir, $packageName) {
        $excludeStubs = config($packageName.'.exclude_stubs', []);
        $valuesStubs = config($packageName.'.values_stubs', []);
        static::$addons = [];
        foreach(glob($path.DIRECTORY_SEPARATOR.'*') as $path) {
            $appPath = base_path('..' . DIRECTORY_SEPARATOR . substr($path, strlen($copyDir) + 1));
            if(in_array($appPath, $excludeStubs)) continue; //do not update
            if(is_dir($path)) {
                static::cleanStubsForUpdate($path, $copyDir, $packageName);
                if (is_dir($appPath) && count(glob("$appPath/*")) === 0) {
                    rmdir($appPath);
                }
                continue;
            }
            if(!is_file($appPath)) continue;
            //stubs that return configurable array
            if(in_array($appPath, $valuesStubs) || dirInArray(dirname($appPath), $valuesStubs)) {
                $pathArray = include($path);
                $appPathArray = include($appPath);
                $pathArray = marinar_assoc_arr_merge($pathArray, $appPathArray);
                file_put_contents($appPath, returnArrayFileContent($pathArray));
                continue;
            }
            //end stubs that return configurable array

            $fileContent = file_get_contents($appPath);
            $startComment = $endComment = false;
            foreach(config('marinar.ext_comments') as $endsWith => $commentType) {
                if(!Str::endsWith($appPath, $endsWith)) continue;
                $startComment = str_replace('__COMMENT__', '@ADDON', $commentType)."\n";
                $endComment = str_replace('__COMMENT__', '@END_ADDON', $commentType)."\n";
                break;
            }
            $createUpdateStub = false;
            if($startComment !== false) { //addonable file
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

                preg_match_all("/([ \t])*(".$startAddon.")([\s\S]*?)(".$endAddon.")/", $fileContent, $foundAddons);
                if(isset($foundAddons[0]) && !empty($foundAddons[0])) { //found @ADDONS
                    $fileContent = str_replace($foundAddons[0], "", $fileContent);

                    foreach($foundAddons[0] as $index => $addonScript) {
                        $addonScript = str_replace([' ', "\t", "\n"], $addonScript);
                        foreach(config($packageName.'.addons') as $addonMainClass) {
                            if(!property_exists($addonMainClass, 'injects', )) continue;
                            $injectAddonClass = $addonMainClass::injects();
                            if(!method_exists($injectAddonClass, 'addonsMap')) continue;
                            $injectAddonClass::addonsMap(useExcludes: false); //do not inject but still need to replace
                            if(!isset($injectAddonClass::$addons[$appPath])) continue;
                            foreach($injectAddonClass::$addons[$appPath] as $hook => $hookAddonScript){
                                $hookAddonScript = str_replace([' ', "\t", "\n"], $startComment.$hookAddonScript.$endComment);
                                if($hookAddonScript === $addonScript) {
                                    //addon is from this class(package)
                                    static::$addons[ $appPath ][ $index ] = [
                                        'class' => $injectAddonClass,
                                        'hook' => $hook
                                    ];
                                    break;
                                }
                            }

                        }
                    }
                }
            }
            $fileContent = str_replace([' ', "\t", "\n"], $fileContent);

            //if there is a stub file - it should have, but just for safety
            $oldStubPath = implode( DIRECTORY_SEPARATOR, [
                base_path(), 'storage', 'marinar_stubs', $packageName, substr($path, strlen($copyDir) + 1)
            ]);
            if(realpath($oldStubPath)) {
                if($fileContent !== str_replace([' ', "\t", "\n"], file_get_contents($oldStubPath))) {
                    //cannot update - file is changed
                    $createUpdateStub = true;
                }
            }

            //NOT REALLY NECESSARY - NEED TO CHECK WHICH IS FASTER AND TAKE LESS MEMORY (THIS OR UNLINK-COPY-ADDON)
            $pathContent = file_get_contents($path);
            if($fileContent === str_replace([' ', "\t", "\n"], $pathContent)) {
                if(isset(static::$addons[ $appPath ])) {
                    static::$addons[ $appPath ] = []; //static properties cannot be unset
                }
                //do not update - file is same
                continue;
            }

            //create manual update stub file - file is changed
            if($createUpdateStub) {
                $updateStubPath = implode( DIRECTORY_SEPARATOR, [
                    base_path(), 'updates', $packageName, substr($path, strlen($copyDir) + 1)
                ]);
                @file_put_contents($updateStubPath, $pathContent);
                echo PHP_EOL."UPDATE STUB WAS CREATED: ".$appPath;
                continue;
            }

            //delete the file - next command will add the updated stub
            @unlink($appPath);

            //NEED TO CHECK WHICH IS MORE EFFICIENT - DIRECTLY HERE OR UNLINK-COPY
//                @file_put_contents($appPath, $pathContent);
        }
    }

    private function injectStubsAddons() {
        foreach(static::$addons as $appPath => $classHookData) {
            $this->refComponents->task("Inject addon - ".basename($appPath), function() use ($appPath, $classHookData){
                foreach($classHookData as $classHookArr) {
                    $className = $classHookArr['class'];
                    if(!method_exists($className, 'injectAddon')) continue;
                    $className::injectAddon($appPath, $classHookArr['hook']);
                }
                return true;
            });
        }
    }

    private function clearFiles() {
        $this->refComponents->task("Clear stubs", function() {
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
        foreach(glob($path.DIRECTORY_SEPARATOR.'*') as $path) {
            $appPath = base_path( '..'.DIRECTORY_SEPARATOR.substr($path, strlen($copyDir)+1) );
            if(is_dir($path)) {
                static::removeStubFiles($path, $copyDir, $deleteBehavior, $showLogs);
                if(is_dir($appPath) && count(glob("$appPath/*")) === 0 ) {
                    rmdir($appPath);
                }
                continue;
            }
            if($deleteBehavior === true || !is_file($appPath)) {
                @unink($appPath);
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

            $fileContent = str_replace([' ', "\t", "\n"], $fileContent);

            //if there is a stub file - it should have, but just for safety
            $oldStubPath = implode( DIRECTORY_SEPARATOR, [
                base_path(), 'storage', 'marinar_stubs', static::$packageName, substr($path, strlen($copyDir) + 1)
            ]);
            if(realpath($oldStubPath)) {
                if($fileContent !== str_replace([' ', "\t", "\n"], file_get_contents($oldStubPath))) {
                    if($showLogs) {
                        echo PHP_EOL."Not deleted: ".$appPath;
                    }
                    continue;
                }
            }
            @unink($appPath);
        }
    }

    private function clearMarinarStubs() {
        $oldStubsPath = implode( DIRECTORY_SEPARATOR, [ base_path(), 'storage', 'marinar_stubs', static::$packageName ]);
        if(!realpath($oldStubsPath)) return;
        $command = Package::replaceEnvCommand("rm -rf {$oldStubsPath}",
            base_path()//where to search for commands_replace_env.php file
        );
        $this->refComponents->task("Delete marinar_stubs [$command]", function() use ($command){
            return $this->execCommand($command);
        });
    }

    private function copyToMarinarStubs() {
        $this->clearMarinarStubs();
        $copyDir = static::$packageDir.DIRECTORY_SEPARATOR.'stubs';
        $oldStubsPath = implode( DIRECTORY_SEPARATOR, [ base_path(), 'storage', 'marinar_stubs', static::$packageName ]);
        $command = Package::replaceEnvCommand("mkdir -p {$oldStubsPath} && cp -rf {$copyDir}".DIRECTORY_SEPARATOR.". {$oldStubsPath}",
            base_path()//where to search for commands_replace_env.php file
        );
        $this->refComponents->task("Coping marinar_stubs [$command]", function() use ($command, $oldStubsPath){
            if(!$this->execCommand($command)) return false;
            $this->givePermissions( $oldStubsPath );
            return true;
        });
    }

    private function copyToMarinarHooks() {
        $hooksPath = static::$packageDir.DIRECTORY_SEPARATOR.'hooks';
        if(!realpath($hooksPath)) return;
        $oldStubsHookPath = implode( DIRECTORY_SEPARATOR, [ base_path(), 'storage', 'marinar_stubs', static::$packageName, 'hooks' ]);
        $this->refComponents->task("Coping hooks to marinar_stubs", function() use ($hooksPath, $oldStubsHookPath){
            if(realpath($oldStubsHookPath)) {
                $command = Package::replaceEnvCommand("rm -rf {$oldStubsHookPath}",
                    base_path()//where to search for commands_replace_env.php file
                );
                $this->execCommand($command);
            }
            $command = Package::replaceEnvCommand("cp -rf {$hooksPath}".DIRECTORY_SEPARATOR.". {$oldStubsHookPath}",
                base_path()//where to search for commands_replace_env.php file
            );
            if(!$this->execCommand($command)) return false;
            $this->givePermissions( $oldStubsHookPath );
            return true;
        });
    }

    private static function putBeforeInContent($filePath, $searches, $replaces) {
        if(!($fp = fopen($filePath, "r"))) return false;
        $startComment = $endComment = '';
        foreach(config('marinar.ext_comments') as $endsWith => $commentType) {
            if(!Str::endsWith($filePath, $endsWith)) continue;
            $startComment = str_replace('__COMMENT__', '@ADDON', $commentType)."\n";
            $endComment = str_replace('__COMMENT__', '@END_ADDON', $commentType)."\n";
            break;
        }
        $searches = (array)$searches;
        $replaces = (array)$replaces;
        $return = '';

        $lineCounter = 0;
        while (($line = fgets($fp)) !== false) {
            $lineCounter++;
            foreach($searches as $index => $search) {
//                //SEARCH FOR THE LINE
//                if(property_exists(static::class, 'alreadyInjectedInLine') && isset(static::$alreadyInjectedInLine[$filePath]) &&
//                    (isset(static::$alreadyInjectedInLine[$filePath][$search]) || isset(static::$alreadyInjectedInLine[$filePath]['default']))
//                ) {
//                    $lineIn = isset(static::$alreadyInjectedInLine[$filePath][$search])?
//                        static::$alreadyInjectedInLine[$filePath][$search] :
//                        static::$alreadyInjectedInLine[$filePath]['default'];
//                    if($lineIn != $lineCounter) continue;
//                } else { //SEARCH FOR THE HOOK
//                    if(strpos($line, $search) === false) continue;
//                }

                $add = isset($replaces[$index])? $replaces[$index] : $replaces[0];
                $spaces = '';
                if($startComment !== '') {
                    for ($index = 0; $index < strlen($add); $index++) {
                        if ($add[$index] !== " " && $add[$index] !== "\t") break;
                        $spaces .= $add[$index];
                    }
                }
                $return .= $spaces.$startComment.$add.$spaces.$endComment;
            }
            $return .= $line;
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
        $command = Package::replaceEnvCommand("chmod -R 777 {$dir}");
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
                if($useExcludes && isset($excludeInjects[$appPath][$hook])) {
                    unset($return[$appPath][$hook]); continue;
                }
                if(Str::startsWith($addonContent, dirname( base_path() ))) { //content is in hook file
                    $return[$appPath][$hook] = file_get_contents($addonContent);
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
                    if (isset($oldMap[$filePath][$hook])) unset($oldMap[$filePath][$hook]);
                }
            }
        }
        //end exclude for injections

        foreach($oldMap as $filePath => $hookAddons) {
            if (!is_array($hookAddons) || empty($hookAddons)) continue;
            foreach($hookAddons as $hook => $addonContent) {
                if(Str::startsWith($addonContent, dirname( base_path() ))) { //content is in hook file
                    $addonContent = str_replace(static::$packageDir.DIRECTORY_SEPARATOR.'hooks', $oldStubsHookPath, $addonContent);
                    $oldMap[$filePath][$hook] = str_replace(["<?php\n", "<?php \n"], '', file_get_contents($addonContent));
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
            preg_match_all("/([ \t])*(".$startAddon.")([\s\S]*?)(".$endAddon.")/", $fileContent, $foundAddons);
            if(isset($foundAddons[0]) && !empty($foundAddons[0])) { //found @ADDONS
                foreach($hookAddons as $hook => $addonContent) {
                    $pureAddonContent = str_replace([" ", "\n", "\t"], '', $startComment.$addonContent.$endComment);
                    $alreadyFound = false;
                    foreach($foundAddons[0] as $index => $injectedAddon) {
                        if($pureAddonContent !== str_replace([" ", "\n", "\t"], $injectedAddon)) continue; //not same
                        //found match
                        if($alreadyFound) { //if there is more than one same addon in the file
                            unset($foundAddons[0][$index]);//make loops smaller
                            continue;
                        }
                        $contentChanged = true;
                        $newContent = '';
                        if(isset(static::$addons[$filePath]) && isset(static::$addons[$filePath][$hook])) {
                            //put same spaces and tabs
                            $spaces = '';
                            for ($spaceIndex = 0; $spaceIndex < strlen($injectedAddon); $spaceIndex++) {
                                if ($injectedAddon[$spaceIndex] !== " " && $injectedAddon[$spaceIndex] !== "\t") break;
                                $spaces .= $injectedAddon[$spaceIndex];
                            }
                            $addonSpaces = '';
                            for ($spaceIndex = 0; $spaceIndex < strlen($injectedAddon); $spaceIndex++) {
                                if ($injectedAddon[$spaceIndex] !== " " && $injectedAddon[$spaceIndex] !== "\t") break;
                                $addonSpaces .= $injectedAddon[$spaceIndex];
                            }
                            $newContent = explode("\n", static::$addons[$filePath][$hook]);
                            foreach($newContent as $rowIndex => $newContentRow) {
                                $newContent[$rowIndex] = Str::startsWith($newContent[$rowIndex], $addonSpaces)?
                                    Str::replaceFirst($addonSpaces, $spaces, $newContent[$rowIndex]) :
                                    $newContent[$rowIndex];
                            }
                            $newContent = implode("\n", $newContent);
                            // end put same spaces and tabs

                            $newContent = $spaces.$startComment."\n".$newContent.$spaces.$endComment."\n";
                        }
                        $fileContent = str_replace($injectedAddon, $newContent, $fileContent);
                        unset($foundAddons[0][$index]);//make loops smaller
                        $alreadyFound = true;
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
        $namespacePackageName = ucfirst(static::$packageName);
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

    private function autoInstall() {
        $this->getRefComponents();
        $this->updateAddonInjects();
        $this->injectAddons();
        $this->stubFiles();
        $this->copyToMarinarHooks();
        $this->dbMigrate();
        $this->seedMe();
        $this->giveGitPermissions();
    }

    private function autoRemove() {
        if(config(static::$packageName.'.delete_behavior', false) === 2) return; //keep everything
        $this->getRefComponents();
        if(method_exists($this, 'clearDB')) $this->clearDB();
        $this->updateAddonInjects(clear: true);
        $this->clearFiles();
    }

}

<?php

    namespace Marinar\Marinar\Console\Commands;

    use App\Models\Package;
    use Illuminate\Console\Command;


    use Illuminate\Support\Facades\Schema;
    use Symfony\Component\Process\Exception\ProcessFailedException;
    use Symfony\Component\Process\Process;

    class MarinarPackage extends Command
    {
        /**
         * The name and signature of the console command.
         *
         * @var string
         */
        protected $signature = 'marinar:package
            {package=all : Package name}
            {--r|remove : To remove}';

        /**
         * The console command description.
         *
         * @var string
         */
        protected $description = 'Marinar packages';

        /**
         * Create a new command instance.
         *
         * @return void
         */
        public function __construct()
        {
            parent::__construct();
        }

        /**
         * Execute the console command.
         *
         * @return mixed
         */
        public function handle()
        {
//            if(!db_table_exists('modules')) {
//                return;
//            }
            $packageName = $this->argument('package');
            if($packageName == 'all') {
                $jsonPath = Package::getPackagesJSONPath();
                if(!($json = realpath( $jsonPath ))) {
                    return;
                }
                if(!($packages = json_decode( @file_get_contents($json) )) ) {
                    return;
                }
            } else {
                $packages = [ $packageName ];
            }

            foreach($packages as $packageName) {
                $packageParts = explode('/', $packageName);
                if ($packageParts[0] != 'marinar') {
                    continue;
                }
                $filePath = ['vendor', $packageParts[0], $packageParts[1], 'src', 'config', 'package.php'];
                if (!($filePath = realpath(base_path(implode(DIRECTORY_SEPARATOR, $filePath)))))
                    continue;
                $packageCommands = include $filePath;
                if ($remove = $this->option('remove')) {
//                if($servicesCache = realpath(Package::getServicesCachePath()))
//                    @unlink($servicesCache);
//                if($packagesCache = realpath(Package::getPackagesCachePath()))
//                    @unlink($packagesCache);

                    $commands = (isset($packageCommands['remove']) && is_array($packageCommands['remove'])) ?
                        $packageCommands['remove'] : [];
                } else {
                    $commands = (isset($packageCommands['install']) && is_array($packageCommands['install'])) ?
                        $packageCommands['install'] : [];
                }
                foreach ($commands as $command) {
                    $command = Package::replaceEnvCommand($command);
                    $this->components->info($command);
                    $process = Process::fromShellCommandline($command.' --force');
//                $process = new Process(explode(' ', $addCommand));
                    $process->setWorkingDirectory(app('path.base'));
                    $process->setTimeout(180);
                    $process->run();

                    // executes after the command finishes
                    if (!$process->isSuccessful()) {
                        throw new ProcessFailedException($process);
                    }
                    echo $process->getOutput();
                }
            }
            if($this->argument('package') == 'all') {
                @unlink($jsonPath);
            }

        }
    }

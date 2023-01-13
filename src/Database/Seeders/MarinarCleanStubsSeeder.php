<?php
    namespace Marinar\Marinar\Database\Seeders;

    use Illuminate\Database\Seeder;

    class MarinarCleanStubsSeeder extends Seeder {

        use \Marinar\Marinar\Traits\MarinarSeedersTrait;

        public function run() {
            $this->getRefComponents();

            $this->cleanInjects(config('marinar.addons'));
            $this->clearFiles();
        }

        private function clearFiles() {
            $this->refComponents->task("Clear stubs", function() {
                $stubDir = \Marinar\Marinar\Marinar::getPackageMainDir().DIRECTORY_SEPARATOR.'stubs_old'.DIRECTORY_SEPARATOR.'v1.0.6';
                static::removeStubFiles($stubDir, $stubDir);

                $stubDir = \Marinar\Marinar\Marinar::getPackageMainDir().DIRECTORY_SEPARATOR.'stubs';
                static::removeStubFiles($stubDir, $stubDir, showLogs: true);
                return true;
            });
        }
    }

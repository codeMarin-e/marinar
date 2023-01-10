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
//                $stubDir = \Marinar\Users\Marinar::getPackageMainDir().DIRECTORY_SEPARATOR.'old_stubs'.DIRECTORY_SEPARATOR.'v0.0.99';
//                static::removeStubFiles($stubDir, $stubDir);

                $stubDir = \Marinar\Users\Marinar::getPackageMainDir().DIRECTORY_SEPARATOR.'stubs';
                static::removeStubFiles($stubDir, $stubDir, true);
                return true;
            });
        }
    }

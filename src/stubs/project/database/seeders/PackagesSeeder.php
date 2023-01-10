<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PackagesSeeder extends Seeder {

    public static $package_seeders = [];

    private function addDirFiles($dirPath) {
        if(!realpath($dirPath) || !is_dir($dirPath)) return false;
        foreach(glob($dirPath.DIRECTORY_SEPARATOR.'*') as $path) {
            if(is_dir($path)) {
                $this->addDirFiles($path);
                continue;
            }
            $className = str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''],
                str_replace(database_path('seeders'), '\Database\Seeders', $path));
            static::$package_seeders[] = $className;
        }
        return true;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->addDirFiles(database_path('seeders').DIRECTORY_SEPARATOR.'Packages');
        if(empty(self::$package_seeders))
            return;
        $this->call(self::$package_seeders);
    }
}

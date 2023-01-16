<?php
    namespace Database\Seeders;

    use Illuminate\Database\Seeder;

    class MarinarSeeder extends Seeder {

        /**
         * Run the database seeds.
         *
         * @return void
         */
        public function run() {
            $domainName = str_replace(['http://', 'https://'], '',
                env('ALIAS_DOMAIN', env('APP_URL', request()->getHost()))
            );
            $domain = \App\Models\Site::updateOrCreate([
                'domain' => $domainName,
            ], [
                'domain' => $domainName,
                'seo' => 1,
                'language' => config('app.locale'),
            ]);

            $user = \App\Models\User::updateOrCreate([
                'email' => 'super@dev.frontsoftware.no',
            ], [
                'email' => 'super@dev.frontsoftware.no',
                'site_id' => $domain->id,
                'name' => 'Super Admin',
                'password' => \Illuminate\Support\Facades\Hash::make('12345'),
                'active' => 1,
            ]);

            //SPATIE/laravel-permissions documentation
            //https://spatie.be/docs/laravel-permission/v5/basic-usage/multiple-guards

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            $role = \Spatie\Permission\Models\Role::updateOrCreate(['guard_name' => 'admin', 'name' => 'Super Admin']);
            $user->assignRole($role); //Super Admin get all rights automatically in AuthServiceProvider

            $userAddr = $user->getAddress();
            $userAddr->update([
                'fname' => 'Super',
                'lname' => 'Admin',
                'email' => 'super@dev.frontsoftware.no',
            ]);
        }
    }

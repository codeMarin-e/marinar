## MARINAR

Packages based extendable Laravel system made by Marin Ivanov

- [Installation](#installation)
- [Update](#update)
- [Models](#models)
- [Traits](#traits)
- [Providers](#providers)
- [Commands](#commands)
- [Components](#components)
- [Seeders](#seeders)

## Installation
1. Make Laravel project
```bash
composer create-project laravel/laravel project && cd project
```

2. In `project/composer.json`
```bash 
...
"repositories": [
    {
        "type": "composer",
        "url": "https://marin.dev.frontsoftware.no/lpackages/api/[TOKEN]"
    }
]
...
"minimum-stability": "dev",
```

3. In `project/.env`
```bash 
APP_TESTING=true
APP_URL=[YOUR_URL]
TIMEZONE=Europe/Sofia

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=testing
DB_USERNAME=homestead
DB_PASSWORD=secret

FILESYSTEM_DISK=public_html
```

4. Require `marinar` CMS
```bash
composer require --dev marinar/marinar
```

5. Intall `marinar`
```
php artisan db:seed --class="\\Marinar\\Marinar\\Database\\Seeders\\MarinarInstallSeeder"
```

6. Rename `app/commands_replace_env_example.php` to `app/commands_replace_env.php`. Change if needed

## Models
1. `AddVar` - Used for dynamic additional columns(variables) to other models
2. `Site` - For main settings on different domains
3. `User` - Default Laravel User model extended with `MarinarUserTrait` trait
4 `Package` - helper class for automatic `marinar` package installation/removing
   
## Traits 
1. `Addonable` - for extending models properties 
2. `AddVariable` - for dynamic additional columns(variables)
3. `MacroableModel` - for extending models with methods and properties
4. `MarinarUserAddressableTrait` - helper trait for extending `User` model with `addressable` functionality
5. `MarinarUserTrait` - extending default Laravel `User` model with `marinar` functionalities

## Providers
1. `AppServiceProvider` - for adding helpers functions, `pushonce`(for push only once scripts) and 
   `pushonceOnReady`(push once scripts on jQuery onReady - automatically remove `<script>` and `</script>` tags) blade directives,
   and add automatically php files from `project\app\Providers\marinar`
   (if file name ends with `_register` - for register method, if with '_boot' - for the boot method)
2. `FortifyServiceProvider` - adding functionality for `fortify` laravel package and separate `web` from `admin` environment
3. `MarinarBeforeServiceProvider` - added before every other provider. Add `Dispatcher` fix(to can search model macros, too),
   `whereIam` macros to the `Illuminate\Http\Request`, `Site` singleton for the used domain, add `Super Admin` gate permissions.
4. `MarinarViewServiceProvider` - for the `view composers`
5. `RouteServiceProvider` - mainly to add sub directory installation functionality

## Commands
1. `GarbageCollector` - `gc:cleanup` - for session cleaning, but can be extended
2. `MarinarPackage` - for `marinar` packages installation/removing

## Components
1. `<x-admin.editor>`
- `inputName` - for the input name
- `otherClasses` - for adding class to `textarea`

## Seeders
1. `MarinarSeeder` - add Super Admin user and main site 
2. `PackagesSeeder` - for automatic call other seeder from `project/database/seeders/Packages` folder(can be in sub folders, too)
__Note:__ Call these two to the main `DatabaseSeeder`

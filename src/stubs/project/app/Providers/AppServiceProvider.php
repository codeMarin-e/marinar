<?php

namespace App\Providers;

use App\Models\Site;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use App\Http\Middleware\CheckSite;

class AppServiceProvider extends ServiceProvider
{

    public static $marinarProvidersDir = null;
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('path.public', function() {
            return dirname( base_path() ).'/public_html';
        });

        //ADDING PACKAGE SCRIPTS
        static::$marinarProvidersDir = implode(DIRECTORY_SEPARATOR, array(app_path(), 'Providers', 'marinar'));
        foreach(glob(static::$marinarProvidersDir.DIRECTORY_SEPARATOR.'*_register.php') as $path) include $path;
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        include base_path().DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Models'.DIRECTORY_SEPARATOR.'helpers.php';
        if(!Schema::hasTable(Site::getModel()->getTable()))
            return;
        if($chSite = app()->make('Site')) {
            config( (array)$chSite->config );
        }
        app()->make('router')->aliasMiddleware('CheckSite', CheckSite::class);


        Blade::directive('pushonce', function ($expression) {
            $var = '$__env->{"__pushonce_" . md5(__FILE__ . ":" . __LINE__)}';
            return "<?php if(!isset({$var})): {$var} = true; \$__env->startPush({$expression}); ?>";
        });

        Blade::directive('endpushonce', function ($expression) {
            return '<?php $__env->stopPush(); endif; ?>';
        });
        Blade::directive('pushOnReady', function ($expression) {
            return "<?php \$__env->startPush({$expression}); ?>";
        });
        Blade::directive('endpushOnReady', function ($expression) {
            return '<?php $__env->marinar_stop_push(); ?>';
        });

        Blade::directive('pushonceOnReady', function ($expression) {
            $var = '$__env->{"__pushonceOnReady_" . md5(__FILE__ . ":" . __LINE__)}';
            return "<?php if(!isset({$var})): {$var} = true; \$__env->startPush({$expression}); ?>";
        });

        \Illuminate\View\Factory::macro('marinar_stop_push', function() {
            if (empty($this->pushStack)) {
                throw new InvalidArgumentException('Cannot end a push stack without first starting one.');
            }
            $content = Str::replaceFirst("<script>", '', ob_get_clean());
            $content = Str::replaceLast("</script>", '',$content);
            return tap(array_pop($this->pushStack), function ($last) use ($content){
                $this->extendPush($last, $content);
            });
        });

        Blade::directive('endpushonceOnReady', function ($expression) {
            return '<?php $__env->marinar_stop_push(); endif; ?>';
        });

        //ADDING PACKAGE SCRIPTS
        foreach(glob(static::$marinarProvidersDir.DIRECTORY_SEPARATOR.'*_boot.php') as $path) include $path;


    }
}

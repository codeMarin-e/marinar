<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Controllers\Admin\LoginController;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Contracts\FailedTwoFactorLoginResponse as FailedTwoFactorLoginResponseContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Illuminate\Support\Facades\Hash;
use App\Fixes\Fortify\AdminRedirectIfTwoFactorAuthenticatable;
use App\Fixes\Fortify\FailedTwoFactorLoginResponse;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $addDir = config('app.dir')? '/'.config('app.dir') : '';
        if(request()->whereIAm() == 'admin') {
            //@see Laravel\Fortify\Http\Controllers\AuthenticatedSessionController@loginPipeline
            config([
                'fortify.guard' => 'admin',
                'fortify.home' => config('marinar.admin_home'),
                'fortify.redirects.login' => $addDir.'/admin/login',
                'fortify.redirects.logout' => $addDir.'/admin',
                'fortify.pipelines.login' => [ //@see Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::loginPipeline
                    config('fortify.limiters.login') ? null : EnsureLoginIsNotThrottled::class,
                    Features::enabled(Features::twoFactorAuthentication()) ? AdminRedirectIfTwoFactorAuthenticatable::class : null,
                    AttemptToAuthenticate::class,
                    PrepareAuthenticatedSession::class,
                ]
            ]);
        }

        Route::group([
            'namespace' => 'Laravel\Fortify\Http\Controllers',
            'prefix' => $addDir.'/admin',
            'as' => 'admin.',
        ], function () {
            $this->loadRoutesFrom(base_path('vendor/laravel/fortify/routes/routes.php'));
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;
            return Limit::perMinute(10)->by($email.$request->ip());
        });


        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        //FIX OLD() PROBLEM ON CODE ERROR
        $this->app->singleton(FailedTwoFactorLoginResponseContract::class, FailedTwoFactorLoginResponse::class);



        if(request()->whereIAm() == 'admin') {

            //find user to authenticate
            Fortify::authenticateUsing([\App\Http\Controllers\Admin\LoginController::class, 'findUserForFortify']);


            return;
        }

        //find user to authenticate
        Fortify::authenticateUsing([\App\Http\Controllers\LoginController::class, 'findUserForFortify']);

//        //login view
//        Fortify::loginView(function () {
//            return view('login');
//        });
//        //register view
//        Fortify::registerView(function () {
//            return view('register');
//        });
//        //amnesiac view
//        Fortify::requestPasswordResetLinkView(function () {
//            return view('forgot-password');
//        });
//        //view for return from amnesiac mail
//        Fortify::resetPasswordView(function ($request) {
//            return view('reset-password', ['request' => $request]);
//        });
//        //view for return from verified mail
//        //for routes that need verification first use ->middleware(['verified']);
//        Fortify::verifyEmailView(function () {
//            return view('mails.verify-email');
//        });
//        //for pages that want to confirm password
        Fortify::confirmPasswordView(function () {
            return view('confirm-password');
        });


//        Fortify::createUsersUsing(CreateNewUser::class);
//        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
//        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
//        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);


    }
}

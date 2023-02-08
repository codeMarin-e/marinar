<?php


    use App\Http\Controllers\ConfirmPasswordController;
    use App\Http\Controllers\InfopageController;
    use App\Http\Controllers\LoginController;
    use App\Http\Controllers\ProfileController;
    use App\Http\Controllers\ForgottenPasswordController;
    use App\Http\Controllers\RegisterController;
    use App\Http\Controllers\VerifyEmailController;
    use Illuminate\Support\Facades\Route;
    use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
    use Laravel\Fortify\Http\Controllers\ConfirmablePasswordController;

    /*
    |--------------------------------------------------------------------------
    | Web Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register web routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | contains the "web" middleware group. Now create something great!
    |
    */

    require 'admin.php'; //admin routes

    //REGISTER
    Route::get('/terms', [InfopageController::class, 'terms'])
        ->name('terms');

    Route::get('/register', [RegisterController::class, 'index'])
        ->middleware(['guest:' . config('fortify.guard')])
        ->name('register');

    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware(['guest:'.config('fortify.guard')]);
    //END REGISTER

    //VERIFY E_MAIL
    Route::get('/verify-email/{id}/{hash}', [VerifyEmailController::class, 'verify'])
        ->middleware([
//            'guest:'.config('fortify.guard'),
            'signed', 'throttle:'.config('fortify.limiters.verification', '6,1')])
        ->name('verification.verify');
    Route::get('/verified', [VerifyEmailController::class, 'verified'])
        ->middleware(['auth:'.config('fortify.guard')])
        ->name('verification.verified');
    //VERIFY E_MAIL

    //CONFIRM PASSWORD
    Route::get('confirm-password', [ConfirmPasswordController::class, 'show'])
        ->middleware([config('fortify.auth_middleware', 'auth').':'.config('fortify.guard')]);
    Route::post('confirm-password', [ConfirmPasswordController::class, 'store'])
        ->middleware([config('fortify.auth_middleware', 'auth').':'.config('fortify.guard')])
        ->name('password.confirm');
    //END CONFIRM PASSWORD

    //RESET PASSWORD
    Route::get('/forgot-password', [ForgottenPasswordController::class, 'create'])
        ->middleware(['guest:'.config('fortify.guard')])
        ->name('password.request');

    Route::post('/forgot-password', [ForgottenPasswordController::class, 'store'])
        ->middleware(['guest:'.config('fortify.guard'), 'throttle:6,1'])
        ->name('password.email');

    Route::get('/reset-password/{token}', [ForgottenPasswordController::class, 'passwordResetShow'])
        ->middleware(['guest:'.config('fortify.guard'), 'throttle:6,1'])
        ->name('password.reset');

    Route::post('/reset-password', [ForgottenPasswordController::class, 'passwordResetting'])
        ->middleware(['guest:'.config('fortify.guard'), 'throttle:6,1'])
        ->name('password.update');
    //END RESET PASSWORD

    //AUTHENTICATE
    Route::get('/login', [LoginController::class, 'index'])
        ->middleware(['guest:' . config('fortify.guard')])
        ->name('login');

    Route::get('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::get('/two-factor-challenge',  [LoginController::class, 'twoFactorChallenge'])
        ->middleware(['guest:'.config('fortify.guard')])
        ->name('two-factor.login');
    //END AUTHENTICATE

    Route::group(['middleware' => ['auth', 'verified:logout']], function() {
        //PROFILE
        Route::get('/profile', [ProfileController::class, 'get'])
            ->name('profile');
        Route::patch('/profile', [ProfileController::class, 'patch']);
        //END PROFILE
        //TWO FACTOR AUTHENTICATION
        Route::get('/two-factor', [ProfileController::class, 'twoFactor'])
            ->middleware(['password.confirm'])
            ->name('two-factor');
        //END TWO FACTOR AUTHENTICATION

        Route::get('/', function () {
            return view('home');
        });

        //ADDING PACKAGE ROUTES
        $systemPathsDir = implode(DIRECTORY_SEPARATOR, array(base_path(), 'routes', 'loged'));
        foreach(glob($systemPathsDir.DIRECTORY_SEPARATOR.'*.php') as $path) include $path;

    });

    //ADDING PACKAGE ROUTES
    $systemPathsDir = implode(DIRECTORY_SEPARATOR, array(base_path(), 'routes', 'system'));
    foreach(glob($systemPathsDir.DIRECTORY_SEPARATOR.'*.php') as $path) include $path;

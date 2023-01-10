<?php
    namespace App\Http\Controllers;

    use App\Models\User;
    use Illuminate\Auth\Events\PasswordReset;
    use Illuminate\Contracts\Auth\StatefulGuard;
    use Illuminate\Http\Request;
    use Illuminate\Support\Arr;
    use Illuminate\Support\Facades\Password;
    use Illuminate\Support\Facades\Validator;
    use Illuminate\Support\Str;
    use Illuminate\Validation\ValidationException;
    use Laravel\Fortify\Actions\ConfirmPassword;
    use Laravel\Fortify\Contracts\FailedPasswordConfirmationResponse;
    use Laravel\Fortify\Contracts\PasswordConfirmedResponse;
    use Laravel\Fortify\Fortify;

    class ConfirmPasswordController extends Controller {
        /**
         * @see Laravel\Fortify\Http\Controllers\ConfirmablePasswordController
         * The guard implementation.
         *
         * @var \Illuminate\Contracts\Auth\StatefulGuard
         */
        protected $guard;

        /**
         * @see Laravel\Fortify\Http\Controllers\ConfirmablePasswordController
         * Create a new controller instance.
         *
         * @param  \Illuminate\Contracts\Auth\StatefulGuard  $guard
         * @return void
         */
        public function __construct(StatefulGuard $guard)
        {
            $this->guard = $guard;
        }

        public function show() {
            $viewData = [];
            return view('confirm-password', $viewData);
        }

        /**
         * @see Laravel\Fortify\Http\Controllers\ConfirmablePasswordController
         * @param Request $request
         * @return \Illuminate\Contracts\Foundation\Application|mixed
         */
        public function store(Request $request)
        {
            $confirmed = app(ConfirmPassword::class)(
                $this->guard, $request->user(), $request->input('confirm.password')
            );

            if ($confirmed) {
                $request->session()->put('auth.password_confirmed_at', time());
            }

            return $confirmed
                ? app(PasswordConfirmedResponse::class)
                : app(FailedPasswordConfirmationResponse::class);
        }
    }

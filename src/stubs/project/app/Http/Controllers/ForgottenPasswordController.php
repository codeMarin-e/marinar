<?php
    namespace App\Http\Controllers;

    use App\Models\User;
    use Illuminate\Auth\Events\PasswordReset;
    use Illuminate\Http\Request;
    use Illuminate\Support\Arr;
    use Illuminate\Support\Facades\Password;
    use Illuminate\Support\Facades\Validator;
    use Illuminate\Support\Str;
    use Illuminate\Validation\ValidationException;
    use Laravel\Fortify\Fortify;

    class ForgottenPasswordController extends Controller {

        public function create() {
            $viewData = [];
            return view('forgot-password', $viewData);
        }

        public function store(Request $request) {
            $inputBag = 'forgotten';
            $validatedData = Validator::make(request()->all()[$inputBag]?? [], [
                Fortify::email() => ['required', 'email']
            ], Arr::dot((array)trans('forgotten-password.validation')))->validateWithBag($inputBag);

            // We will send the password reset link to this user. Once we have attempted
            // to send the link, we will examine the response then see the message we
            // need to show to the user. Finally, we'll send out a proper response.
            $status = Password::broker(config('fortify.passwords'))->sendResetLink($validatedData);

            if($status != Password::RESET_LINK_SENT) {
                throw ValidationException::withMessages([
                    Fortify::email() => __($status)
                ])->errorBag($inputBag);
            }
            return back()->with('forgotten_sent', __($status));
        }

        public function passwordResetShow(Request $request) {
            $viewData = [];
            $viewData['email'] = $request->query('email');
            $viewData['token'] = $request->route('token');
            return view('password-reset', $viewData);
        }

        public function passwordResetting(Request $request) {
            $inputBag = 'reset';
            $validatedData = Validator::make(request()->all()[$inputBag]?? [], [
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|string|confirmed|min:8|max:255',
            ], Arr::dot((array)trans('password-reset.validation')))->validateWithBag($inputBag);

            // Here we will attempt to reset the user's password. If it is successful we
            // will update the password on an actual user model and persist it to the
            // database. Otherwise we will parse the error and return the response.
            $status = Password::broker(config('fortify.passwords'))->reset(
                $validatedData,
                function ($user) use ($validatedData) {
                    $user->forceFill([
                        'password' => User::cryptPassword( $validatedData['password'] ),
                        'remember_token' => Str::random(60),
                    ])->save();

                    event(new PasswordReset($user));
                }
            );

            if($status != Password::PASSWORD_RESET) {
                throw ValidationException::withMessages([
                    'password' => __($status)
                ])->errorBag($inputBag);
            }
            return redirect()->route('login')->with('password_reset_success', 1); //see login blade view
        }
    }

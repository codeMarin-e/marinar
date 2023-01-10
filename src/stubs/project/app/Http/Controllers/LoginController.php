<?php
    namespace App\Http\Controllers;

    use App\Models\User;
    use Illuminate\Http\Request;
    use Illuminate\Routing\Controller;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Validation\ValidationException;

    class LoginController extends Controller
    {
        public function index() {
            return view('login');
        }


        public function twoFactorChallenge() {
            $viewData = [];
            return view('two-factor-challenge', $viewData);
        }

        public static function findUserForFortify(Request $request) {
            if($user = User::where('email', $request->email)
                ->whereNotNull('email_verified_at')
                ->whereHas('roles', function($qry) {
                    $qry->whereIn("name", ["Customer"])
                        ->where("guard_name", "web");
                })
                ->where('active', true)
                ->first()) {
                if(!Hash::check($request->password, $user->password)) {
                    throw ValidationException::withMessages([
                        'password' => [trans('auth.password')],
                    ]);
                }
                return $user;
            }
        }
    }

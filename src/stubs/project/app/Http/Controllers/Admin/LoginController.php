<?php
    namespace App\Http\Controllers\Admin;

    use App\Models\User;
    use Illuminate\Http\Request;
    use Illuminate\Routing\Controller;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Validation\ValidationException;

    class LoginController extends Controller
    {
        public function index() {
            $viewData = [];
            return view('admin.login');
        }

        public function twoFactorChallenge() {
            $viewData = [];
            return view('admin.two-factor-challenge', $viewData);
        }

        public static function findUserForFortify(Request $request) {
            if($user = User::where('email', $request->email)
                ->whereHas('roles', function($qry) {
                    $qry->whereIn("name", ["Admin", "Super Admin"])
                        ->where("guard_name", "admin");
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

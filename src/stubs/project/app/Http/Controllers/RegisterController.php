<?php
    namespace App\Http\Controllers;

    use App\Http\Requests\RegisterRequest;
    use App\Models\User;
    use Illuminate\Auth\Events\Registered;
    use Illuminate\Routing\Controller;
    use Illuminate\Support\Facades\DB;

    class RegisterController extends Controller
    {
        public function index() {
            return view('register');
        }

        public function store(RegisterRequest $request) {
            $validatedData = $request->validated();
            DB::transaction(function() use ($validatedData) {
                $newUser = User::updateOrCreate([
                    'email' => $validatedData['addr']['email'],
                ], [
                    'site_id' => app()->make('Site')->id,
                    'name' => $validatedData['addr']['fname'].' '.$validatedData['addr']['lname'],
                    'email' => $validatedData['addr']['email'],
                    'email_for_confirm' => $validatedData['addr']['email'],
                    'password' => User::cryptPassword($validatedData['password']),
                    'active' => true,
                    'type' => isset( $validatedData['addr']['company'] )? 'COMPANY' : null,
                ]);
                $newUserAddr = $newUser->getAddress()->update($validatedData['addr']);
                if($role = \Spatie\Permission\Models\Role::findByName('Customer', 'web')) {
                    $newUser->assignRole("Customer");
                }
                event(new Registered($newUser));
            });

            return redirect()->route('register')->with('register_success', 1);
        }
    }

<?php

namespace App\Http\Controllers;

use App\Notifications\RegisterUser;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    public function verified() {
        return view('mail-verified');;
    }

    public function verify($id, Request $request)
    {
        $chUser = User::whereActive(true)
            ->whereHas('roles', function($qry) {
                $qry->whereIn("name", ["Customer"])
                    ->where("guard_name", "web");
            })
            ->findOrFail($id);
        if (! hash_equals((string) $request->route('hash'),
            sha1($chUser->getEmailForVerification()))) {
            abort(403);
        }

        $profileChanged = false;
        if ($chUser->hasVerifiedEmail()) {
            $profileChanged = true;
//            Auth::guard('web')->login($chUser);
//            return redirect()->route('verification.verified');
        }

        if ($chUser->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }
        if(!$profileChanged) {
            $chUser->notify(new RegisterUser); //register mail after verification
        }


        Auth::guard('web')->login($chUser);

        return redirect()->route('verification.verified');
    }
}

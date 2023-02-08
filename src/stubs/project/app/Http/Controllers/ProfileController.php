<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Marinar\Attachments\Models\Attachment;
use Marinar\Infopages\Models\Infopage;

class ProfileController extends Controller
{
    public function get() {
        $viewData = [];
        return view('profile', $viewData);
    }

    public function twoFactor() {
        $viewData = [];
        return view('two-factor', $viewData);
    }

    private function validateData($request) {
        $authUser = auth()->user();
        $inputs = $request->all()['profile']?? [];
//        dd($inputs);
//        $inputs['agree'] = isset($inputs['agree']);
//        $request->replace(['profile' => $inputs]);
//        request()->replace(['profile' => $inputs]); //global request should be replaced, too
        $rules = [
            'addr.fname' => 'required|string|max:255',
            'addr.lname' => 'required|string|max:255',
            'email_for_confirm' => ['required', 'string', 'email', function($attribute, $value, $fail) use ($authUser) {
                if($suchUser = \App\Models\User::where( function($qry) use ($value) {
                    $qry->where('email', $value)
                        ->orWhere('email_for_confirm', $value);
                })
                    ->whereHas('roles', function($qry) {
                        $qry->whereIn("name", ["Customer"])
                            ->where("guard_name", "web");
                    })
                    ->where('id', '!=', $authUser->id)
                    ->whereNotNull('email_verified_at')->first()) {
                    return $fail( trans('profile.validation.addr.email.unique') );
                }
            }],
            'addr.phone' => 'nullable|max:255',
            'addr.street' => 'nullable|string|max:255',
            'addr.city' => 'nullable|string|max:255',
            'addr.postcode' => 'nullable|max:6',
//            'addr.country' => 'nullable|string|max:255',
            'addr.company' => 'nullable|string|max:255',
            'addr.orgnum' => 'nullable|string|max:255',
            'password' => [ 'nullable', 'string', 'confirmed', 'min:8', 'max:255'],
            'old_password' => ['required_with:password,email','required', 'string', 'min:4', 'max:255', 'current_password:web']
//                function($attribute, $value, $fail) use ($authUser) {
//                    if(!Hash::check($value, $authUser->password)) {
//                        return $fail( trans('profile.validation.old_password.current_password') );
//                    }
//            }],

//            'avatar' => 'nullable|file|mimes:jpg,bmp,png|max:'.config("app.max_file_size"),
        ];
        $validatedData = Validator::make($inputs, $rules, Arr::dot((array)trans('profile.validation')))->validateWithBag('profile');
        $validatedData['type'] = isset($validatedData['addr']['company'])? 'COMPANY' : null;
        if(isset($validatedData['password'])) {
            $validatedData['password'] = User::cryptPassword($validatedData['password']);
        }
        if($authUser->email === $validatedData['email_for_confirm']) {
            unset($validatedData['email_for_confirm']);
        }
        if(is_null($validatedData['password'])) {
            unset($validatedData['password']);
        }
        return $validatedData;
    }

    public function patch(Request $request) {
        $validatedData = $this->validateData($request);
        $chUser = auth()->user();
        $chUserAddr = $chUser->getAddress();
        $chUser->update( $validatedData );
        $chUserAddr->update( $validatedData['addr'] );
//        if($attachments = request()->file('profile.avatar')){
//            if($mainAttach = $chUser->getMainAttachment('avatar')) {
//                $mainAttach->delete();
//            }
//            $chUser->addAttachments($attachments, 'avatar', [
//                'disk' => null,
//                'site_id' => $chUser->site_id
//            ]);
//        }

        $withData  = [ 'profile_success' => 1, ];
        if(isset($validatedData['email_for_confirm'])) {
            $chUser->sendEmailVerificationNotification();
            $withData['email_verification_sent'] = 1;
        }
        event( 'user.submited', [$chUser, $validatedData] );
        return redirect()->route('profile')->with($withData);
    }
}

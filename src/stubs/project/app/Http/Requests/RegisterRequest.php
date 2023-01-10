<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'addr.fname' => 'required|string|max:255',
            'addr.lname' => 'required|string|max:255',
            'addr.email' => ['required', 'string', 'email', function($attribute, $value, $fail) {
                if($suchUser = User::where( function($qry) use ($value){
                        $qry->where('email', $value)
                            ->orWhere('email_for_confirm', $value);
                    })
                    ->whereHas('roles', function($qry) {
                        $qry->whereIn("name", ["Customer"])
                            ->where("guard_name", "web");
                    })
                    ->whereNotNull('email_verified_at')->first()) {
                    return $fail( trans('register.validation.addr.email.unique') );
                }
            }],
            'addr.phone' => 'nullable|max:255',
            'addr.street' => 'nullable|string|max:255',
            'addr.city' => 'nullable|string|max:255',
            'addr.postcode' => 'nullable|max:6',
            'addr.company' => 'nullable|string|max:255',
            'addr.orgnum' => 'nullable|string|max:255',
//            'addr.country' => 'nullable|string|max:255',
            'password' => 'required|string|confirmed|min:8|max:255',
            'agree' => ['boolean', function($attribute, $value, $fail) {
                    if(!$value) {
                        return $fail( trans('register.validation.agree.required') );
                    }
                }
            ]
        ];
    }


    public function messages() {
        return Arr::dot((array)trans('register.validation'));
    }

    public function validationData() {
        $inputBag = 'register';
        $this->errorBag = $inputBag;
        $inputs = $this->all();
        if(!isset($inputs[$inputBag])) {
            throw ValidationException::withMessages([
                'register' => [trans('register.validate.no_data')],
            ]);
        }
        $inputs[$inputBag]['agree'] = isset($inputs[$inputBag]['agree']);
        $this->replace($inputs); //global request should be replaced, too
        request()->replace($inputs); //global request should be replaced, too
        return $inputs[$inputBag];
    }
}

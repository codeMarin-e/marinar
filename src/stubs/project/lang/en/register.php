<?php
    return [
        'form.fname' => 'First Name',
        'form.lname' => 'Last Name',
        'form.email' => 'E-mail',
        'form.phone' => 'Phone',
        'form.company' => 'Company Name',
        'form.orgnum' => 'Org. num.',
        'form.street' => 'Street',
        'form.city' => 'City',
        'form.postcode' => 'Postcode',
        'form.password' => 'Password',
        'form.password_confirmation' => 'Repeat password',
        'form.agree' => 'Agree <a href=":terms_route" target="_blank">terms</a>',
        'form.submit' => 'Register',

//        'validation.agree.required' => 'You must agree with the Privacy Policy',
        'validation' => \Illuminate\Support\Arr::undot([
            'no_data' => 'There is no register data',
            'addr.fname.required' => 'First name is required',
            'addr.lname.required' => 'Last name is required',
            'addr.email.required' => 'Email is required',
            'addr.phone.required' => 'Phone is required',
            'addr.street.required' => 'Street is required',
            'addr.city.required' => 'City is required',
            'addr.postcode.required' => 'Postcode is required',
            'addr.country.required' => 'Country is required',
            'password.required' => 'Password is required',

            'addr.fname.max' => 'First name is too long',
            'addr.lname.max' => 'Last name is too long',
            'addr.email.max' => 'Email is too long',
            'addr.email.email' => 'Email is not in email format',
            'addr.email.unique' => 'There is user already with such email',
            'addr.phone.max' => 'Phone is too long',
            'addr.street.max' => 'Street is too long',
            'addr.city.max' => 'City is too long',
            'addr.postcode.max' => 'Postcode is too long',
            'addr.country.max' => 'Country is too long',
            'password.max' => 'Password is too long',
            'password.min' => 'Password is too short',
            'password.confirmed' => 'Password confirmation field is not same',
            'agree.required' => 'You must agree with the Privacy Policy',
        ]),

        'success' => 'Registration is made - check your email for verification link',
    ];

<?php
    return [
        'email' => 'Email',
        'submit' => 'Send',
        'validation' => \Illuminate\Support\Arr::undot([
            'email.required' => 'Email is required',
            'email.email' => 'Email should be in email format',
            'token.required' => 'Token is required',
            'password.required' => 'Password is required',
            'password.confirmed' => 'Password is not same as confirmation',
            'password.min' => 'Password is too short',
            'password.max' => 'Password is too long',
        ])
    ];

<?php

    namespace App\Fixes\Fortify;

    use Illuminate\Validation\ValidationException;
    use Laravel\Fortify\Contracts\FailedTwoFactorLoginResponse as FailedTwoFactorLoginResponseContract;

    /**
     * @see Laravel\Fortify\Http\Responses
     */
    class FailedTwoFactorLoginResponse implements FailedTwoFactorLoginResponseContract
    {
        /**
         * Create an HTTP response that represents the object.
         *
         * @param  \Illuminate\Http\Request  $request
         * @return \Symfony\Component\HttpFoundation\Response
         */
        public function toResponse($request)
        {
            [$key, $message] = $request->has('recovery_code')
                ? ['recovery_code', __('two-factor-challenge.recovery_code.wrong')]
                : ['code', __('two-factor-challenge.code.wrong')];


            throw ValidationException::withMessages([
                $key => [$message],
            ]);

//            if ($request->wantsJson()) {
//                throw ValidationException::withMessages([
//                    $key => [$message],
//                ]);
//            }
//
//            return redirect()->route('two-factor.login')->withErrors([$key => $message]);
        }
    }

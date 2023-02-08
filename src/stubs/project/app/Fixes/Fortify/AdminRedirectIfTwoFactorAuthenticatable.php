<?php
    namespace App\Fixes\Fortify;

    use \Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable as Base;
    use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;

    class AdminRedirectIfTwoFactorAuthenticatable extends Base {



        /**
         * Get the two factor authentication enabled response.
         *
         * @param  \Illuminate\Http\Request  $request
         * @param  mixed  $user
         * @return \Symfony\Component\HttpFoundation\Response
         */
        protected function twoFactorChallengeResponse($request, $user)
        {
            $request->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => $request->filled('remember'),
            ]);

            TwoFactorAuthenticationChallenged::dispatch($user);

            return $request->wantsJson()
                ? response()->json(['two_factor' => true])
                : redirect()->route('admin.two-factor.login'); //to redirect to admin.
        }
    }

<?php
    namespace Marinar\Marinar\View\Composers;

    use Illuminate\Support\Facades\Auth;
    use Illuminate\View\View;

    class MainComposer
    {
        public function __construct()
        {
        }

        /**
         * Bind data to the view.
         *
         * @param  View  $view
         * @return void
         */
        public function compose(View $view)
        {
            $chSite = app()->make('Site');
            $viewVars = [
                'siteCurrency' => $chSite->currency,
                'chSite' => $chSite,
            ];
            if( $authUser =  Auth::guard( request()->whereIAm() )->user() ) {
                $viewVars['authUserAddr'] = $authUser->getAddress();
            }
            $viewVars['authUser'] = $authUser;
            $viewVars['whereIam'] = request()->whereIAm();
            $view->with($viewVars);
        }
    }

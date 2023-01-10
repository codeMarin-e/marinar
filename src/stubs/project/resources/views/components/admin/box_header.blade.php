<nav class="navbar navbar-expand navbar-dark bg-dark static-top">

    <a class="navbar-brand mr-1" href="javascript:void(0);">{{config('app.name')}}</a>

    <button class="btn btn-link btn-sm text-white order-1 order-sm-0" id="sidebarToggle" href="#">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Navbar -->
    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">

            <a class="nav-link dropdown-toggle"
               href="#" id="languageMenuButton"
               role="button" data-toggle="dropdown"
               aria-haspopup="true" aria-expanded="false">
                <img src="{{ asset("admin/images/flags/{$appLocale}.png") }}"
                     alt="{{$appLocale}}" />
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="languageMenuButton">
                @foreach (config('app.available_locales') as $locale => $localeName)
                    @php
                        if($locale == config('app.fallback_locale')) {
                            if($appLocale == $locale) {
                                $localePrefix = Str::after(\Illuminate\Support\Facades\Route::currentRouteName(), "i18n_");
                                $localeParameters = ['locale' => null];
                            } else {
                                $localePrefix = \Illuminate\Support\Facades\Route::currentRouteName();
                                $localeParameters = ['locale' => false];
                            }
                        } else {
                            $localeParameters = [ 'locale' => $locale ];
                            $localePrefix = ($appLocale == $locale)?
                                \Illuminate\Support\Facades\Route::currentRouteName() :
                                'i18n_'.Str::after(\Illuminate\Support\Facades\Route::currentRouteName(), "i18n_");
                        }
                    @endphp
                    <a class="dropdown-item text-uppercase text-dark font-weight-bold @if($appLocale == $locale) active @endif"
                       href="{{route($localePrefix, array_merge(
                                $localeParameters,
                                \Illuminate\Support\Facades\Route::current()->parameters(),
                                request()->query()
                            )) }}"><img src="{{ asset("admin/images/flags/{$locale}.png") }}"
                                        alt="{{$locale}}" /><span class="pl-1">{{ $locale }}</span></a>
                @endforeach
            </div>
        </li>
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-user-circle fa-fw"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                @php
                    $logoutTrans = trans("{$whereIam}/box_header.logout");
                    $logoutLink = $logoutTrans === "{$whereIam}/box_header.logout"?
                        trans("admin/box_header.logout") : $logoutTrans;
                @endphp

{{--                @if($whereIam == 'admin')--}}
{{--                    @can('use-module', 'marinar_settings')--}}
{{--                        @php--}}
{{--                            $settingsTrans = trans("{$whereIam}/box_header.settings");--}}
{{--                            $settingsLink = $settingsTrans === "{$whereIam}/box_header.settings"?--}}
{{--                                trans("admin/box_header.settings") : $settingsTrans;--}}
{{--                        @endphp--}}
{{--                        <a class="dropdown-item" href="{{route("{$whereIam}.settings.index")}}">{{$settingsLink}}</a>--}}
{{--                    @endcan--}}
{{--                @endif--}}
                @if($whereIam == 'admin' && Route::has("{$whereIam}.users.edit"))
                    <a class="dropdown-item" href="{{ route("{$whereIam}.users.edit", [$authUser]) }}">{{$authUser->name}}</a>
                @else
                    <a href="javascript:void(0)" class="dropdown-item">{{$authUser->name}}</a>
                @endif
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="{{route("{$whereIam}.logout")}}">{{$logoutLink}}</a>
            </div>
        </li>
    </ul>

</nav>

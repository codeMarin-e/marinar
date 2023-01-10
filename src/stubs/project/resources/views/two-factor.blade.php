<x-main>
    <h1>Two factor authentication Page</h1>
    <div>
        @csrf
        @if (session('status') == 'two-factor-authentication-enabled')
            <div>@lang('two-factor.enabled')</div>
        @endif
        @if (session('status') == 'two-factor-authentication-disabled')
            <div>@lang('two-factor.disabled')</div>
        @endif


        @if($authUser->two_factor_secret)
            {{--    RAW SWG    --}}
            {!! $authUser->twoFactorQrCodeSvg() !!}
            <br />
            @foreach((array) $authUser->recoveryCodes() as $recoveryCode)
                <div>{{$recoveryCode}}</div>
            @endforeach
            <form action="{{route('two-factor.recovery-codes')}}" method="POST"  autocomplete="off">
                @csrf
                <button type="submit" onclick="if(!confirm('@lang('two-factor.sure_ask')')) return false;">@lang('two-factor.regenerate')</button>
            </form>

            <br />

            <form action="{{route('two-factor.disable')}}" method="POST" autocomplete="off">
                @csrf
                @method("DELETE")
                <button type="submit" onclick="if(!confirm('@lang('two-factor.sure_ask')')) return false;">@lang('two-factor.disable')</button>
            </form>
        @else
            <form action="{{route('two-factor.enable')}}" method="POST" autocomplete="off">
                @csrf
                <button type="submit" onclick="if(!confirm('@lang('two-factor.sure_ask')')) return false;">@lang('two-factor.enable')</button>
            </form>
        @endif

    </div>
</x-main>

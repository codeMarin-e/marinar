<x-main>
    <h1>Two Factor Challenge Page</h1>

    @if(old('two-factor-challenge-form'))
        @foreach($errors->all() as $error)
            <div style="color: red;"><strong>{{ $error }}</strong></div>
        @endforeach
    @endif
{{--    {{ json_encode(Session::getOldInput()) }}--}}

    <h2>@lang('two-factor-challenge.auth_code_by')</h2>
    <form method="POST" action="{{ route('two-factor.login') }}" autocomplete="off">
        @csrf
        <label for="code">@lang('two-factor-challenge.auth_code')</label>
        <input type="text"
               id="code"
               class="@if(old('two-factor-challenge-form') && $errors->has('code')) error @endif"
               onkeyup="this.classList.remove('error')"
               name="code"
               value="@if(old('two-factor-challenge-form')){{old('code')}}@endif"
               required="required"
               autofocus="autofocus" />
        <br />

        <button type="submit"
                name="two-factor-challenge-form"
                value="1">@lang('two-factor-challenge.auth_code_submit')</button>
    </form>

    <h2>@lang('two-factor-challenge.recovery_code_by')</h2>
    <form method="POST" action="{{ route('two-factor.login') }}" autocomplete="off">
        @csrf
        <label for="recovery_code">@lang('two-factor-challenge.recovery_code')</label>
        <input type="text"
               id="recovery_code"
               class="@if(old('two-factor-challenge-form') && $errors->has('recovery_code')) error @endif"
               onkeyup="this.classList.remove('error')"
               name="recovery_code"
               value="@if(old('two-factor-challenge-form')){{old('recovery_code')}}@endif"
               required="required"
            />
        <br />

        <button type="submit"
                name="two-factor-challenge-form"
                value="1">@lang('two-factor-challenge.recovery_code_submit')</button>
    </form>
</x-main>

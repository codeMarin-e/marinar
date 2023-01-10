<x-main>
    <h1>Login Page</h1>

    @if(session('password_reset_success'))
        <div class="success">@lang('password-reset.success')</div>
    @endif

    @if(old('login_form'))
        @foreach($errors->all() as $error)
            <div style="color: red;"><strong>{{ $error }}</strong></div>
        @endforeach
    @endif

    <form method="POST" action="{{ route('login') }}" autocomplete="off">
        @csrf
        <label for="inputEmail">@lang('login.email')</label>
        <input type="email" id="inputEmail"
               class=" @if(old('login_form') && $errors->has('email')) error @endif"
               onkeyup="this.classList.remove('error')"
               placeholder="@lang('login.email')"
               name="email"
               required="required"
               value="@if(old('login_form')){{ old('email')}}@endif"
               autofocus="autofocus" />
        <br/>

        <label for="inputPassword">@lang('login.password')</label>
        <input type="password"
               id="inputPassword"
               class="@if(old('login_form') && $errors->has('password')) error @endif"
               placeholder="@lang('login.password')"
               required="required"
               name="password"/>
        <br/>

        <input type="checkbox"
               id="checkboxRemember"
               @if(old('login_form') && old('remember')) checked="checked" @endif
               name="remember"
               value="1" />
        <label for="checkboxRemember">@lang('login.remember')</label>
        <br />
        <a href="{{route('password.request')}}">Forgot the password?</a>
        <br />
        <br />
        <button type="submit" name="login_form" value="1">@lang('login.submit')</button>
    </form>
</x-main>

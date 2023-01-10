<x-main>
    <h1>Password Reset Page</h1>

    @foreach($errors->reset->all() as $error)
        <div style="color: red;"><strong>{{ $error }}</strong></div>
    @endforeach
    @if(session('password-reset'))
        <div class="success">@lang('password-reset.success')</div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" autocomplete="off">
        @csrf
        <input type="hidden"
               name="reset[token]"
               value="{{$token}}" />
        <input type="hidden"
               name="reset[email]"
               value="{{$email}}" />

        <label for="inputPassword">@lang('password-reset.password')</label>
        <input type="password"
               id="inputPassword"
               placeholder="@lang('password-reset.password')"
               class="@if($errors->reset->has('password')) error @endif"
               onkeyup="this.classList.remove('error')"
               name="reset[password]"
               required="required"
               autofocus="autofocus"
        />
        <label for="inputPasswordConfirm">@lang('password-reset.password_confirmation')</label>
        <input type="password"
               id="inputPasswordConfirm"
               placeholder="@lang('password-reset.password_confirmation')"
               class="@if($errors->reset->has('password_confirmation')) error @endif"
               onkeyup="this.classList.remove('error')"
               name="reset[password_confirmation]"
               required="required"
        />
        <button type="submit">@lang('password-reset.submit')</button>
    </form>
</x-main>

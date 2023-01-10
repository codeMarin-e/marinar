<x-main>
    <h1>Confirm password Page</h1>

    @foreach($errors->confirm->all() as $error)
        <div style="color: red;"><strong>{{ $error }}</strong></div>
    @endforeach

    @if($message = session('forgotten_sent'))
        {{ $message }}
    @endif

    <form method="POST" action="{{ route('password.confirm') }}" autocomplete="off">
        @csrf
        <label for="confirm[password]">@lang('confirm-password.password')</label>
        <input type="password"
               id="confirm[password]"
               class=" @if($errors->confirm->has('password')) error @endif"
               onkeyup="this.classList.remove('error')"
               name="confirm[password]"
               required="required"
{{--               value="{{ old('confirm.password')}}"--}}
               autofocus="autofocus" />

        <button type="submit">@lang('confirm-password.submit')</button>
    </form>
</x-main>

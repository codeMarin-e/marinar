<x-main>
    <h1>Forgotten password Page</h1>

    @foreach($errors->forgotten->all() as $error)
        <div style="color: red;"><strong>{{ $error }}</strong></div>
    @endforeach

    @if($message = session('forgotten_sent'))
        {{ $message }}
    @endif

    <form method="POST" action="{{ route('password.email') }}" autocomplete="off">
        @csrf
        <label for="inputEmail">@lang('forgotten-password.email')</label>
        <input type="email"
               id="inputEmail"
               class="@if($errors->forgotten->has('email')) error @endif"
               onkeyup="this.classList.remove('error')"
               placeholder="@lang('forgotten-password.email')"
               name="forgotten[email]"
               required="required"
               value="{{ old('forgotten.email')}}"
               autofocus="autofocus" />

        <button type="submit">@lang('forgotten-password.submit')</button>
    </form>
</x-main>

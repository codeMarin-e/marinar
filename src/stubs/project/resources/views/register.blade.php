<x-main>
    <h1>Register Page</h1>
    <form method="POST" autocomplete="off" action="{{route('register')}}">
        @csrf

        @if(session('register_success'))
            <div class="success">@lang('register.success')</div>
        @endif
        @foreach($errors->register->all() as $error)
            <div class="error">{{$error}}</div>
        @endforeach

        <label for="register[addr][fname]">@lang('register.form.fname')</label>
        <input type="text"
               name="register[addr][fname]"
               id="register[addr][fname]"
               value="{{old('register.addr.fname')}}"
               class="@if($errors->register->has('addr.fname')) error @endif"
               onkeyup="this.classList.remove('error')"
               required="required"
               />
        <br />

        <label for="register[addr][lname]">@lang('register.form.lname')</label>
        <input type="text"
               name="register[addr][lname]"
               id="register[addr][lname]"
               value="{{old('register.addr.lname')}}"
               class="@if($errors->register->has('addr.lname')) error @endif"
               onkeyup="this.classList.remove('error')"
               required="required"
               />
        <br />

        <label for="register[addr][email]">@lang('register.form.email')</label>
        <input type="text"
               name="register[addr][email]"
               id="register[addr][email]"
               value="{{old('register.addr.email')}}"
               class="@if($errors->register->has('addr.email')) error @endif"
               onkeyup="this.classList.remove('error')"
               required="required"
               />
        <br />

        <label for="register[addr][phone]">@lang('register.form.phone')</label>
        <input type="text"
               name="register[addr][phone]"
               id="register[addr][phone]"
               value="{{old('register.addr.phone')}}"
               class="@if($errors->register->has('addr.phone')) error @endif"
               onkeyup="this.classList.remove('error')"
    {{--           required="required"--}}
               />
        <br />

        <div id="company_register">
            <label for="register[addr][company]">@lang('register.form.company')</label>
            <input type="text"
                   name="register[addr][company]"
                   id="register[addr][company]"
                   value="{{old('register.addr.company')}}"
                   class="@if($errors->register->has('addr.company')) error @endif"
                   onkeyup="this.classList.remove('error')"
    {{--               required="required"--}}
            />
            <br />

            <label for="register[addr][orgnum]">@lang('register.form.orgnum')</label>
            <input type="text"
                   name="register[addr][orgnum]"
                   id="register[addr][orgnum]"
                   value="{{old('register.addr.orgnum')}}"
                   class="@if($errors->register->has('addr.orgnum')) error @endif"
                   onkeyup="this.classList.remove('error')"
                {{--               required="required"--}}
            />
            <br />
        </div>


        <label for="register[addr][street]">@lang('register.form.street')</label>
        <input type="text"
               name="register[addr][street]"
               id="register[addr][street]"
               value="{{old('register.addr.street')}}"
               class="@if($errors->register->has('addr.street')) error @endif"
               onkeyup="this.classList.remove('error')"
    {{--           required="required"--}}
        />
        <br />

        <label for="register[addr][city]">@lang('register.form.city')</label>
        <input type="text"
               name="register[addr][city]"
               id="register[addr][city]"
               value="{{old('register.addr.city')}}"
               class="@if($errors->register->has('addr.city')) error @endif"
               onkeyup="this.classList.remove('error')"
    {{--           required="required"--}}
        />
        <br />

        <label for="register[addr][postcode]">@lang('register.form.postcode')</label>
        <input type="text"
               name="register[addr][postcode]"
               id="register[addr][postcode]"
               value="{{old('register.addr.postcode')}}"
               class="@if($errors->register->has('addr.postcode')) error @endif"
               onkeyup="this.classList.remove('error')"
    {{--           required="required"--}}
        />
        <br />

        <label for="register[password]">@lang('register.form.password')</label>
        <input type="password"
               name="register[password]"
               id="register[password]"
               class="@if($errors->register->has('password')) error @endif"
               onkeyup="this.classList.remove('error')"
               required="required"
        />
        <br />

        <label for="register[password_confirmation]">@lang('register.form.password_confirmation')</label>
        <input type="password"
               name="register[password_confirmation]"
               id="register[password_confirmation]"
               class="@if($errors->register->has('password_confirmation')) error @endif"
               onkeyup="this.classList.remove('error')"
               required="required"
        />
        <br />

        <label for="register[agree]">@lang('register.form.agree', ['terms_route' => route('terms')])</label>
        <input type="checkbox"
               name="register[agree]"
               id="register[agree]"
               @if(old("register.agree"))checked="checked"@endif
               class="@if($errors->register->has('agree')) error @endif"
               onkeyup="this.classList.remove('error')"
               value="1"
        />
        <br />

        <button type="submit">@lang('register.form.submit')</button>

    </form>
</x-main>

<x-main>
    <h1>Profile Page</h1>
    <form action="{{route("profile")}}" method="post" autocomplete="off">
        @csrf
        @method('patch')
        @if(session('profile_success'))
            <div class="success">@lang('profile.success')</div>
        @endif
        @if(session('email_verification_sent'))
            <div class="success">@lang('profile.email_verification_sent')</div>
        @endif
        @foreach($errors->profile->all() as $error)
            <div class="error">{{$error}}</div>
        @endforeach

        <label for="profile[addr][fname]">@lang('profile.form.fname')</label>
        <input type="text"
                   class="large-field @if($errors->profile->has('addr.fname')) error @endif"
                   onkeyup="this.classList.remove('error')"
                   value="{{ old('profile.addr.fname', (isset($authUserAddr)? $authUserAddr->fname: ''))}}"
                   name="profile[addr][fname]"
                   required="required" />
        <br />

        <label for="profile[addr][lname]">@lang('profile.form.lname')</label>
        <input type="text"
                   class="large-field @if($errors->profile->has('addr.lname')) error @endif"
                   onkeyup="this.classList.remove('error')"
                   value="{{ old('profile.addr.lname', (isset($authUserAddr)? $authUserAddr->lname: ''))}}"
                   name="profile[addr][lname]"
                   required="required" />
        <br />

        <label for="profile[email_for_confirm]">@lang('profile.form.email')</label>
        <input type="text"
                   class="large-field @if($errors->profile->has('email_for_confirm')) error @endif"
                   onkeyup="this.classList.remove('error')"
                   value="{{ old('profile.email_for_confirm', (isset($authUserAddr)? $authUserAddr->email: ''))}}"
                   name="profile[email_for_confirm]"
                   required="required"  />
        @if($authUser->email_for_confirm)
            <input type="text"
                   class="large-field"
                   value="{{ $authUser->email_for_confirm }}"
                   readonly="readonly"
                   disabled="disabled"  />
        @endif
        <br />

        <label for="profile[addr][phone]">@lang('profile.form.phone')</label>
        <input type="text"
               class="large-field @if($errors->profile->has('addr.phone')) error @endif"
               onkeyup="this.classList.remove('error')"
               value="{{ old('profile.addr.phone', (isset($authUserAddr)? $authUserAddr->phone: ''))}}"
               name="profile[addr][phone]"
{{--               required="required" --}}
        />
        <br />


        <div id="company_profile">
            <label for="profile[addr][company]">@lang('profile.form.company')</label>
            <input type="text"
                   class="large-field @if($errors->profile->has('addr.company')) error @endif"
                   onkeyup="this.classList.remove('error')"
                   value="{{ old('profile.addr.company', (isset($authUserAddr)? $authUserAddr->company: ''))}}"
                   name="profile[addr][company]" />
            <br />

            <label for="profile[addr][orgnum]">@lang('profile.form.orgnum')</label>
            <input type="text"
                   class="large-field @if($errors->profile->has('addr.orgnum')) error @endif"
                   onkeyup="this.classList.remove('error')"
                   value="{{ old('profile.addr.orgnum', (isset($authUserAddr)? $authUserAddr->orgnum: ''))}}"
                   name="profile[addr][orgnum]" />
        </div>

        <label for="profile[addr][street]">@lang('profile.form.street')</label>
        <input type="text"
               class="large-field @if($errors->profile->has('addr.street')) error @endif"
               onkeyup="this.classList.remove('error')"
               value="{{ old('profile.addr.street', (isset($authUserAddr)? $authUserAddr->street: ''))}}"
               name="profile[addr][street]"
{{--               required="required" --}}
        />
        <br />

        <label for="profile[addr][city]">@lang('profile.form.city')</label>
        <input type="text"
               class="large-field @if($errors->profile->has('addr.city')) error @endif"
               onkeyup="this.classList.remove('error')"
               value="{{ old('profile.addr.city', (isset($authUserAddr)? $authUserAddr->city: ''))}}"
               name="profile[addr][city]"
{{--               required="required" --}}
        />
        <br />

        <label for="profile[addr][city]">@lang('profile.form.postcode')</label>
        <input type="text"
               class="large-field @if($errors->profile->has('addr.postcode')) error @endif"
               onkeyup="this.classList.remove('error')"
               value="{{ old('profile.addr.postcode', (isset($authUserAddr)? $authUserAddr->postcode: ''))}}"
               name="profile[addr][postcode]"
{{--               required="required" --}}
        />
        <br />

        <label for="profile[old_password]">@lang('profile.form.old_password')</label>
        <input type="password"
               class="large-field @if($errors->profile->has('old_password')) error @endif"
               onkeyup="this.classList.remove('error')"
               name="profile[old_password]"  />
        <br />

        <label for="profile[password]">@lang('profile.form.password')</label>
        <input type="password"
               class="large-field @if($errors->profile->has('password')) error @endif"
               onkeyup="this.classList.remove('error')"
               name="profile[password]"  />
        <br />

        <label for="profile[password_confirmation]">@lang('profile.form.password_confirmation')</label>
        <input type="password"
               class="large-field @if($errors->profile->has('password_confirmation')) error @endif"
               onkeyup="this.classList.remove('error')"
               name="profile[password_confirmation]" />
        <br />

        <a href="{{route('two-factor')}}">Two Factor authentication</a>
        <br />
        <br />

        <input type="submit" class="button" value="@lang('profile.form.submit')" />
    </form>
</x-main>

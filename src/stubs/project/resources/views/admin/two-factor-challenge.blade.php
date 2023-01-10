<x-admin.login_main>
    <div class="card-header">@lang('admin/two-factor-challenge.title')</div>
    <div class="card-body">

        @if(old('two-factor-challenge-form'))
            @foreach($errors->all() as $error)
                <div class="row">
                    <div class="col-lg-12">
                        <div class="alert alert-danger alert-dismissable">
                            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                            <strong>{{ $error }}</strong>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    {{--    {{ json_encode(Session::getOldInput()) }}--}}
        <form method="POST" action="{{ route('admin.two-factor.login') }}" autocomplete="off">
            @csrf

            <div class="form-group">
                <div class="form-label-group">
                <input type="text"
                       id="code"
                       class="form-control @if(old('two-factor-challenge-form') && $errors->has('code')) error @endif"
                       onkeyup="this.classList.remove('error')"
                       name="code"
                       placeholder="@lang('admin/two-factor-challenge.auth_code')"
                       value="@if(old('two-factor-challenge-form')){{old('code')}}@endif"
{{--                       required="required"--}}
                       autofocus="autofocus" />
                    <label for="code">@lang('admin/two-factor-challenge.auth_code')</label>
                </div>
            </div>

            <button type="submit"
                    class="btn btn-primary btn-block"
                    name="two-factor-challenge-form"
                    value="1">@lang('admin/two-factor-challenge.submit')</button>
        </form>
        <br />

        <form method="POST" action="{{ route('admin.two-factor.login') }}" autocomplete="off">
            @csrf
            <div class="form-group">
                <div class="form-label-group">
                    <input type="text"
                           id="recovery_code"
                           class="form-control @if(old('two-factor-challenge-form') && $errors->has('recovery_code')) error @endif"
                           onkeyup="this.classList.remove('error')"
                           name="recovery_code"
                           placeholder="@lang('admin/two-factor-challenge.recovery_code')"
                           value="@if(old('two-factor-challenge-form')){{old('recovery_code')}}@endif"
{{--                           required="required"--}}
                           autofocus="autofocus" />
                    <label for="recovery_code">@lang('admin/two-factor-challenge.recovery_code')</label>
                </div>
            </div>

            <button type="submit"
                    class="btn btn-primary btn-block"
                    name="two-factor-challenge-form"
                    value="1">@lang('admin/two-factor-challenge.submit')</button>
        </form>
    </div>
</x-admin.login_main>

<x-admin.login_main>
    <div class="card-header">Login</div>
    <div class="card-body">

        @if(old('admin_login'))
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
        <form method="POST" action="{{ route('admin.login') }}">
            @csrf
            <div class="form-group">
                <div class="form-label-group">
                    <input type="email" id="inputEmail"
                           class="form-control @if(old('admin_login') && $errors->has('email')) is-invalid @endif"
                           onkeyup="this.classList.remove('is-invalid')"
                           placeholder="@lang('admin/login.email')"
                           name="email"
                           required="required"
                           value="{{ old('email')}}"
                           autofocus="autofocus" />
                    <label for="inputEmail">@lang('admin/login.email')</label>
                </div>
            </div>
            <div class="form-group">
                <div class="form-label-group">
                    <input type="password"
                           id="inputPassword"
                           class="form-control @if(old('admin_login') && $errors->has('password')) is-invalid @endif"
                           placeholder="@lang('admin/login.password')"
                           required="required"
                           name="password"/>
                    <label for="inputPassword">@lang('admin/login.password')</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        <input type="checkbox"
                               @if(old('admin_login') && old('remember')) checked="checked" @endif
                               name="remember"
                               value="1">
                        @lang('admin/login.remember')
                    </label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block" name="admin_login" value="1">@lang('admin/login.submit')</button>
        </form>
        <div class="text-center">
            <a class="d-block small" href="#">@lang('admin/login.forgot')</a>
        </div>
    </div>
</x-admin.login_main>

@pushonce('below_js')
<script type="text/javascript" src="{{ asset('admin/vendor/ckeditor/ckeditor.js') }}"></script>
<script type="text/javascript" src="{{ asset('admin/vendor/ckeditor/adapters/jquery.js') }}"></script>
@endpushonce
@pushonceOnReady('below_js_on_ready')
<script>
    //text editors
    $(document).on('editor_init', function(e, lang, name) {
        CKEDITOR.replace( name, {
            language: lang,
            filebrowserBrowseUrl : @if(env('APP_DIR')) '/{{env('APP_DIR')}}' + @endif '/elfinder/0/ckeditor'
        });
    });
    //END text editors
    $(document).trigger('editor_init', [
        '{{$language?? app()->getLocale()}}',
        '{{$inputName}}'
    ]);
</script>
@endpushonceOnReady


<textarea name="{{$inputName}}"
          id="{{$inputName}}"
          class="form-control {{implode(' ', $otherClasses)}}"
>{{ $slot }}</textarea>

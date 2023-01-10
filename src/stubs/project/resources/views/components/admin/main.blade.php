<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Marinar') }} Admin</title>
    <meta name="author" content="Marin Ivanvov">

    <!-- Custom fonts for this template-->
    <link href="{{@asset('admin/vendor/fontawesome-free/css/all.min.css')}}" rel="stylesheet" type="text/css"/>

    <!-- Custom styles for this template-->
    <link href="{{@asset('admin/css/sb-admin.css')}}" rel="stylesheet"/>

    @stack('above_css')
    @stack('above_js')
</head>

<body id="page-top">
<x-admin.box_header />
<div id="wrapper">
    <x-admin.box_sidebar />
    <div id="content-wrapper">
        {{$slot}}
        <x-admin.box_footer />
    </div>
</div>

<!-- Scroll to Top Button-->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

@stack('below_templates')

<!-- Bootstrap core JavaScript-->
<script src="{{@asset('admin/vendor/jquery/jquery.min.js')}}"></script>
<script src="{{@asset('admin/vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>

<!-- Core plugin JavaScript-->
<script src="{{@asset('admin/vendor/jquery-easing/jquery.easing.min.js')}}"></script>
<!-- Custom scripts for all pages-->
<script type="text/javascript" src="{{ asset('admin/js/sb-admin.min.js') }}"></script>

@stack('below_js')

<script type="text/javascript">
    (function($) {
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            @stack('below_js_on_ready')
        });
    })(jQuery);
</script>

</body>

</html>

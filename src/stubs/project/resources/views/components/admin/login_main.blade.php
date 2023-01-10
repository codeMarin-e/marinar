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
</head>

<body class="bg-dark">

<div class="container">
    <div class="card card-login mx-auto mt-5">
        {{$slot}}
    </div>
</div>

<!-- Bootstrap core JavaScript-->
<script src="{{@asset('admin/vendor/jquery/jquery.min.js')}}"></script>
<script src="{{@asset('admin/vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>

<!-- Core plugin JavaScript-->
<script src="{{@asset('admin/vendor/jquery-easing/jquery.easing.min.js')}}"></script>

</body>

</html>

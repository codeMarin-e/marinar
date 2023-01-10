<?php
    namespace App\Http\Controllers\Admin;

    use Illuminate\Routing\Controller;

    class DashController extends Controller
    {
        public function index() {
            return view('admin.home');
        }
    }

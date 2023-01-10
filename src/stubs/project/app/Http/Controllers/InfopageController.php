<?php
    namespace App\Http\Controllers;

    use Illuminate\Routing\Controller;

    class InfopageController extends Controller
    {
        public function terms() {
            return view('terms');
        }
    }

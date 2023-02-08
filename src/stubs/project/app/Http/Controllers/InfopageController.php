<?php
namespace App\Http\Controllers;

use App\Models\Infopage;
use Illuminate\Routing\Controller;

class InfopageController extends Controller
{
    public function __construct() {
        $this->middleware([
            \App\Http\Middleware\SlugParameters::class.":".Infopage::class.',info|chInfopage,info2|chInfopage2'
        ])->only(['get', 'sub']);
    }

    public function get(Infopage $chInfopage) {
        // return $chInfopage->getUri();
        return $chInfopage;
    }

    public function sub(Infopage $chInfopage, Infopage $chInfopage2) {
//        return $chInfopage2->getUri();
        return $chInfopage2;
    }

    public function home() {
        return view('home');
    }

    public function terms() {
        return view('terms');
    }
}

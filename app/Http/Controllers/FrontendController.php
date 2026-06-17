<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class FrontendController extends Controller
{
    public function home(): View
    {
        return view('index');
    }
}

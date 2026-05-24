<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    public function howItWorks(): View
    {
        return view('pages.how-it-works');
    }

    public function about(): View
    {
        return view('pages.about');
    }
}

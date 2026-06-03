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

    public function terms(): View
    {
        return view('pages.legal.terms');
    }

    public function privacy(): View
    {
        return view('pages.legal.privacy');
    }

    public function framework(): View
    {
        return view('pages.legal.framework');
    }

    public function notices(): View
    {
        return view('pages.legal.notices');
    }
}

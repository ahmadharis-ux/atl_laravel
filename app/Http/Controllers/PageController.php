<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    public function terms(): View
    {
        return view('pages.terms');
    }

    public function privacy(): View
    {
        return view('pages.privacy');
    }
}

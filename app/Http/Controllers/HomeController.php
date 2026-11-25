<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Show the TempChat homepage.
     */
    public function __invoke(): View
    {
        return view('home');
    }
}

<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Inertia\Inertia;

class Monica_Controller extends Controller
{

    public function index(Request $request)
    {
         return Inertia::render('monica/inde');
    }
}

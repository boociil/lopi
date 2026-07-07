<?php

namespace App\Http\Controllers\Lopi\Pinjam;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Inertia\Inertia;

class Non_BMN_Controller extends Controller
{

    public function index(Request $request)
    {
         return Inertia::render('pinjam/non_bmn');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\Umum\Satuan_Kerja;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\Tim;
use App\Model\Umum\User;
use Illuminate\Http\Request;

class Dashboard_Controller extends Controller
{
    public function dashboard(Request $request)
    {
        return ['satuan_kerja' => Satuan_Kerja::count()
            , 'satuan_kerja_tahun' => Satuan_Kerja_Tahun::count()
            , 'tim' => Tim::count()
            , 'user' => User::count()];
    }
}

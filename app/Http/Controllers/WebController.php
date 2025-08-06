<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\User;
use App\Model\Umum\Peran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class WebController extends Controller
{
    /**
     * Show the profile for the given user.
     *
     * @param  int  $id
     * @return View
     */


    public function __construct()
    {

    }

    public function webshow(Request $request)
    {
        Log::alert('ajkhsas');
        $satuan_kerja_tahun_prov_id  = Satuan_Kerja_Tahun::where('type', 'prov')->get()->pluck('id')->toArray();
        return view('main',
            ['user' => User::where('username', Auth::user()->username)->first() ,
            'all_peran' => Peran::where(function($query){
                    $query->where('tahun', session('tahun_active'))
                        ->orWhere('tahun', null);
                })->where('pemilik_username', Auth::user()->username)->get(),
            "all_user" => User::select('username', 'nama')->get(),
            "tahun_array" => explode(",", env("TAHUN_ARRAY")),
            "tahun_active" => session('tahun_active'),
            "tanggal_now" => Carbon::today()->format('Y-m-d'),
            'satuan_kerja_tahun_prov_id' => $satuan_kerja_tahun_prov_id,
            "VERSI_BACKEND" => env("VERSI_BACKEND"),
            "VERSI_FRONTEND" => env("VERSI_FRONTEND"),
        ]);
    }


}

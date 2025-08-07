<?php

namespace App\Http\Controllers\Jadwal;

use App\Http\Controllers\Controller;
use App\Model\Jadwal\Jadwal;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class Jadwal_Controller extends Controller
{
    public function __construct(Request $request)
    {

    }

    public function index(Request $request)
    {
        $today =  Carbon::today();
        $bulan_ini =  $today->month;
        $month_start = $today->startOfMonth();
        $month_end = $today->endOfMonth();

        $jadwal_bulan = Jadwal::where(function ($query) use ($bulan_ini, $month_start,  $month_end){
            $query->orWhere(function ($query1) use ($bulan_ini){
                $query1->whereNull('end_date');
                $query1->whereMonth("start_date", $bulan_ini);
            });
            $query->orWhere(function ($query1) use ( $month_start,  $month_end){
                $query1->whereNotNull('end_date');
                $query1->where(function ($query2) use ($month_start,  $month_end){
                    $query2->orWhereBetween("start_date", [$month_start->format('Y-m-d'), $month_end->format('Y-m-d')]);
                    $query2->orWhereBetween('end_date', [$month_start->format('Y-m-d'), $month_end->format('Y-m-d')]);
                });
            });
        })->get();
        return Inertia::render('jadwal/index', [
            "jadwal" => $jadwal_bulan,
        ]);
    }

    public function tambah(Request $request)
    {
        //set validation
        $request->validate([
            'title'   => 'required',
            'content' => 'required',
        ]);

        //redirect
        return 1;
    }


}

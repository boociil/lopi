<?php

namespace App\Http\Controllers\Lopi\Pinjam;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Carbon\Carbon;

use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

use App\Model\Lopi\Jadwal;
use App\Model\Umum\Peran;
use App\Model\Umum\User;
use Inertia\Inertia;

class Ruang_Controller extends Controller
{
    public function __construct()
    {
    }

     public function index(Request $request)
    {
         $validated = $request->validate([
            'selectedDate'  => 'date',
            'view'          => 'string',
            'view'          => Rule::in(['day', 'week', 'month', 'year', 'agenda'])
        ]);

        $selectedDate = null;
        $view = null;

        if($request->has('selectedDate') && $request->has('view')){
            $selectedDate = $request->input('selectedDate');
            $view = $request->input('view');
        }else if(session()->has('pinjam_ruang_index')){
            $selectedDate = Carbon::today()->format('Y-m-d');
            $view = session('pinjam_ruang_index')['view'];
        }else{
            $selectedDate = Carbon::today()->format('Y-m-d');
            $view = 'month';
        }

        session()->put('pinjam_ruang_index', ['view' => $view]);

        $tanggal_awal = null;
        $tanggal_akhir = null;

        if($view == 'day'){
            $tanggal_awal = Carbon::parse($selectedDate);
            $tanggal_akhir = Carbon::parse($selectedDate);
        }else  if($view == 'week'){
            $tanggal_awal = Carbon::parse($selectedDate)->startOfWeek(Carbon::SUNDAY);
            $tanggal_akhir = Carbon::parse($selectedDate)->endOfWeek(Carbon::SUNDAY);
        }else if($view == 'month' || $view == 'agenda'){
            $tanggal_awal = Carbon::parse($selectedDate)->startOfMonth();
            $tanggal_akhir = Carbon::parse($selectedDate)->endOfMonth();
        }else if($view == 'year'){
            $tanggal_awal = Carbon::parse($selectedDate)->startOfYear();
            $tanggal_akhir = Carbon::parse($selectedDate)->endOfYear();
        }

        if($tanggal_awal->format('Y-m-d') == $tanggal_akhir->format('Y-m-d') && $view == 'week'){
            $tanggal_akhir = Carbon::parse($tanggal_awal)->addDays(6);
        }

        $have_ruang_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'ruang')
            ->first();

        if($request->isMethod('get')){
            return Inertia::render('pinjam/ruang', [
                "user" => User::all(),
                "have_ruang_peran" => $have_ruang_peran,
            ]);
        }else if($request->isMethod('post')){
            $jadwal_ruang = Jadwal::where(function (Builder $query0) use ($tanggal_awal, $tanggal_akhir){
                    $query0->orWhere(function (Builder $query) use ($tanggal_awal, $tanggal_akhir){
                            $query->where('tipe', 'sekali')
                                ->whereBetween('tanggal', [$tanggal_awal->format('Y-m-d'), $tanggal_akhir->format('Y-m-d')]);
                        })
                        ->orWhere(function (Builder $query) use ($tanggal_awal, $tanggal_akhir) {
                            $query->where('tipe', 'berulang')
                                ->where('berulang_tanggal_awal', '<=', $tanggal_akhir)
                                ->where('berulang_tanggal_akhir', '>=', $tanggal_awal);
                        });
                })
                ->whereNotNull('ruang')
                ->get();


            return [
                "have_ruang_peran" => $have_ruang_peran,
                "jadwal_ruang" => $jadwal_ruang,
            ];
        }
    }

}

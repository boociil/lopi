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
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\Tim;
use App\Model\Umum\User;
use Inertia\Inertia;

class Ruang_Controller extends Controller
{
    public $ruang = ['aula', 'pokja', 'pst'];

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

        $satuan_kerja_tahun_kode = [];
        $peran_satuan_kerja = Peran::get_my_peran_satuan_kerja_kerja_tahun();

        foreach($peran_satuan_kerja as $item){
            if(!in_array($item->parent_id, $satuan_kerja_tahun_kode)){
                array_push($satuan_kerja_tahun_kode, $item->parent_id);
            }
        }

        if(count($satuan_kerja_tahun_kode) != 1){
            return abort(401);
        }

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

        $jadwal_data = Jadwal::where(function (Builder $query0) use ($tanggal_awal, $tanggal_akhir){
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

        if($request->isMethod('get')){
            return Inertia::render('pinjam/ruang', [
                "ruang" => $this->ruang,
                "user" => User::whereHas('peran', function (Builder $query) use ($satuan_kerja_tahun_kode) {
                        $query->where('tipe_peran', 'satuan_kerja_tahun');
                        $query->whereIn('parent_id', $satuan_kerja_tahun_kode);
                    })->get(),
                "have_ruang_peran" => $have_ruang_peran,
                "jadwal_data" => $jadwal_data,
            ]);
        }else if($request->isMethod('post')){

            return [
                "have_ruang_peran" => $have_ruang_peran,
                "jadwal_data" => $jadwal_data,
            ];
        }
    }

}

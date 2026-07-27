<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Model\Lopi\Jadwal;
use App\Model\Lopi\Jadwal_Peserta;
use App\Model\Lopi\Pinjam_Zoom;
use App\Model\Lopi\Sync_Google\Calendar_Google_Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Carbon\Carbon;

use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

use App\Model\Umum\Peran;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\Tim;
use App\Model\Umum\User;
use App\Services\Zoom_Service;
use App\Services\Google_Event_Local_Service;
use Exception;
use Inertia\Inertia;

class Jadwal_Controller extends Controller
{

    public $ruang = ['aula', 'pokja', 'pst'];

    public function __construct(protected Zoom_Service $zoom, protected Google_Event_Local_Service $event_service)
    {
    }

     public function index(Request $request)
    {
        $validated = $request->validate([
            'selectedDate'  => 'date',
            'view' => [
                'string',
                Rule::in(['day', 'week', 'month', 'year', 'agenda']),
            ],
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
        }else if(session()->has('jadwal_index')){
            $selectedDate = Carbon::today()->format('Y-m-d');
            $view = session('jadwal_index')['view'];
        }else{
            $selectedDate = Carbon::today()->format('Y-m-d');
            $view = 'month';
        }

        session()->put('jadwal_index', ['view' => $view]);

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

        $have_zoom_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'zoom')
            ->first();
        $have_ruang_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'ruang')
            ->first();

        $jadwal_daftar = Jadwal::with(['peserta', 'zoom_pinjam'])-> orWhere(function (Builder $query) use ($tanggal_awal, $tanggal_akhir){
                    $query->where('tipe', 'sekali')
                        ->whereBetween('tanggal', [$tanggal_awal->format('Y-m-d'), $tanggal_akhir->format('Y-m-d')]);
                })
                ->orWhere(function (Builder $query) use ($tanggal_awal, $tanggal_akhir) {
                    $query->where('tipe', 'berulang')
                        ->where('berulang_tanggal_awal', '<=', $tanggal_akhir)
                        ->where('berulang_tanggal_akhir', '>=', $tanggal_awal);
                })
                ->get();

        foreach($jadwal_daftar as $item){
            if($item->zoom_pinjam){
                $item->zoom_pinjam->zoom_start_url = null;
            }
        }


        if($request->isMethod('get')){
            $satuan_kerja_tahun = Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))
                        ->whereIn('id', $satuan_kerja_tahun_kode)
                        ->get();

            $tim = Tim::where('tahun', session('tahun_active'))
                        ->whereIn('satuan_kerja_tahun_id', $satuan_kerja_tahun_kode)
                        ->get();

            return Inertia::render('jadwal', [
                "have_zoom_peran" => $have_zoom_peran,
                "have_ruang_peran" => $have_ruang_peran,
                "jadwal_daftar" => $jadwal_daftar,
                "ruang" => $this->ruang,
                "user" => User::whereHas('peran', function (Builder $query) use ($satuan_kerja_tahun_kode) {
                        $query->where('tipe_peran', 'satuan_kerja_tahun');
                        $query->whereIn('parent_id', $satuan_kerja_tahun_kode);
                    })->get(),
                'satuan_kerja_tahun' => $satuan_kerja_tahun,
                'tim' => $tim,
            ]);
        }else if($request->isMethod('post')){
            return [
                "have_zoom_peran" => $have_zoom_peran,
                "have_ruang_peran" => $have_ruang_peran,
                "jadwal_daftar" => $jadwal_daftar,
                "ruang" => $this->ruang,
            ];
        }
    }

    public function tambah(Request $request)
    {
        $validated = $request->validate([
            'kegiatan'          => 'required|string|max:255',
            'description'         => 'string',
            'tipe'              => 'required|in:sekali,berulang',
            'tanggal'           => 'date',
            'tanggal_awal'      => 'date',
            'tanggal_akhir'     => 'date|after_or_equal:tanggal_awal',
            'waktu_awal'        => 'required|date_format:H:i',
            'waktu_akhir'       => 'required|date_format:H:i|after:waktu_awal',

            'ruang_status' => 'in:tidak,menunggu,disetujui,ditolak',

            'zoom_status'  => 'in:tidak,langsung,menunggu,disetujui,ditolak',
            'zoom_langsung_join_url'     => 'nullable|string',
            'zoom_password'          => 'nullable|string',
            'zoom_akun'         => 'nullable|in:statsulbar,sp2020',
        ]);

        if(!$request->input('tanggal') || (!$request->input('tanggal_awal') && !$request->input('tanggal_akhir'))){
            throw ValidationException::withMessages([
                '_status' => 'Jadwal tidak dapat dibuat',
            ]);
        }

        $have_zoom_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'zoom')
            ->first();

        if($have_zoom_peran
            && $request->input('zoom_status') != 'tidak'
            && $request->input('zoom_status') != 'langsung'
            && in_array($request->input('zoom_akun'), ['statsulbar', 'sp2020']))
        {
             if($request->input('tipe') == 'sekali'){
                if(Pinjam_Zoom::staticCheckClash($request->input('zoom_akun'), $request->input('tanggal'), $request->input('tanggal')
                    , $request->input('waktu_awal').':00', $request->input('waktu_akhir').':00'))
                {
                    throw ValidationException::withMessages([
                        'error_clash_zoom' => 'Sudah terdapat peminjaman zoom di tanggal dan waktu tersebut',
                    ]);
                }
             }else{
                if(Pinjam_Zoom::staticCheckClash($request->input('zoom_akun'), $request->input('tanggal_awal'), $request->input('tanggal_akhir')
                    , $request->input('waktu_awal').':00', $request->input('waktu_akhir').':00'))
                {
                    throw ValidationException::withMessages([
                        'error_clash_zoom' => 'Sudah terdapat peminjaman zoom di tanggal dan waktu tersebut',
                    ]);
                }
             }
        }

        $have_ruang_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'ruang')
            ->first();


        if($have_ruang_peran
            && $request->input('ruang_status') != 'tidak'
            && in_array($request->input('ruang'), $this->ruang))
        {
             if($request->input('tipe') == 'sekali'){
                if($this->checkRuangClash($request->input('ruang'), $request->input('tanggal'), $request->input('tanggal')
                    , $request->input('waktu_awal').':00', $request->input('waktu_akhir').':00'))
                {
                    throw ValidationException::withMessages([
                        'error_clash_ruang' => 'Sudah terdapat peminjaman ruang di tanggal dan waktu tersebut',
                    ]);
                }
             }else{
                if($this->checkRuangClash($request->input('ruang'), $request->input('tanggal_awal'), $request->input('tanggal_akhir')
                    , $request->input('waktu_awal').':00', $request->input('waktu_akhir').':00'))
                {
                    throw ValidationException::withMessages([
                        'error_clash_ruang' => 'Sudah terdapat peminjaman ruang di tanggal dan waktu tersebut',
                    ]);
                }
             }
        }

        DB::beginTransaction();
        try
        {
            $jadwal = new Jadwal();
            $jadwal->id = Str::uuid();
            $jadwal->kegiatan = $request->input('kegiatan');
            $jadwal->deskripsi = $request->input('description');
            $jadwal->tipe = $request->input('tipe');

            if($request->input('tipe') == 'sekali'){
                $jadwal->tanggal = $request->input('tanggal');
                $jadwal->waktu_awal = $request->input('waktu_awal');
                $jadwal->waktu_akhir = $request->input('waktu_akhir');
            }else if($request->input('tipe') == 'berulang'){
                $jadwal->berulang_tanggal_awal = $request->input('tanggal_awal');
                $jadwal->berulang_tanggal_akhir = $request->input('tanggal_akhir');
                $jadwal->waktu_awal = $request->input('waktu_awal');
                $jadwal->waktu_akhir = $request->input('waktu_akhir');
            }

            $jadwal->status = 'menunggu';
            $jadwal->pembuat_username = Auth::user()->username;

            $jadwal->ruang_status = 'tidak';
            if($request->input('ruang_status') != 'tidak'){
                $jadwal->ruang_status = 'menunggu';
                $jadwal->ruang = $request->input('ruang');
                if($have_ruang_peran && in_array($request->input('ruang'), $this->ruang)){
                    $jadwal->ruang_status = 'disetujui';
                    $jadwal->ruang_confirm_username = Auth::user()->username;
                }
            }

            $jadwal->zoom_status = 'tidak';
            if($request->input('zoom_status') != 'tidak'){
                if($request->input('zoom_status') == 'langsung' && $request->input('zoom_langsung_join_url')){
                    $jadwal->zoom_status = 'langsung';
                    $jadwal->zoom_langsung_join_url = $request->input('zoom_langsung_join_url');
                }else{
                    $pinjam_zoom = Pinjam_Zoom::helper_tambah(
                        $request->input('kegiatan'),
                        $request->input('tipe'),
                        $request->input('zoom_password'),
                        $request->input('tanggal'),
                        $request->input('tanggal_awal'),
                        $request->input('tanggal_akhir'),
                        $request->input('waktu_awal'),
                        $request->input('waktu_akhir'),
                        $have_zoom_peran,
                        in_array($request->input('zoom_akun'), ['statsulbar', 'sp2020']) ? $request->input('zoom_akun') : null,
                        $this->zoom
                    );

                    $jadwal->zoom_pinjam_id = $pinjam_zoom->id;
                    $jadwal->zoom_status = $pinjam_zoom->status;
                    $jadwal->zoom_confirm_username = $pinjam_zoom->confirm_username;
                }
            }

            if($jadwal->ruang_status == 'disetujui' && in_array($jadwal->zoom_status, ['langsung', 'disetujui'])){
                $jadwal->status = 'disetujui';
            }else if($jadwal->ruang_status == 'tidak' && $jadwal->zoom_status == 'tidak'){
                $jadwal->status = 'disetujui';
            }else{
                $jadwal->status = 'menunggu';
            }

            $jadwal->save();

            Jadwal_Peserta::where('jadwal_id', $jadwal->id)->delete();
            $peserta_arrr = [];
            if($request->input('peserta')){
                foreach($request->input('peserta') as $item){
                    $peserta = new Jadwal_Peserta();
                    $peserta->id = Str::uuid();
                    $peserta->jadwal_id = $jadwal->id;
                    $peserta->peserta_type = $item[0];
                    $peserta->peserta_id = $item[1];
                    $peserta->save();
                    array_push( $peserta_arrr, $peserta);
                }
            }


            $this->event_service->saveEventPeserta(
                $jadwal,
                $peserta_arrr
            );

            DB::commit();
            return [
                "_status" => 'Berhasil membuat peminjaman zoom!',
                "data" => Jadwal::with(['peserta', 'zoom_pinjam'])->where('id', $jadwal->id)->get(),
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal membuat peminjaman zoom!',
            ]);
        }
    }

    public function ubah(Request $request, Jadwal $jadwal)
    {
         $validated = $request->validate([
            'kegiatan'          => 'required|string|max:255',
            'description'         => 'string',
            'tipe'              => 'required|in:sekali,berulang',
            'tanggal'           => 'date',
            'tanggal_awal'      => 'date',
            'tanggal_akhir'     => 'date|after_or_equal:tanggal_awal',
            'waktu_awal'        => 'required|date_format:H:i',
            'waktu_akhir'       => 'required|date_format:H:i|after:waktu_awal',

            'ruang_status' => 'in:tidak,menunggu,disetujui,ditolak',

            'zoom_status'  => 'in:tidak,langsung,menunggu,disetujui,ditolak',
            'zoom_langsung_join_url'     => 'nullable|string',
            'zoom_password'          => 'nullable|string',
            'zoom_akun'         => 'nullable|in:statsulbar,sp2020',
        ]);

        $berubahInti = true;
        if($jadwal->tipe == $request->input('tipe') &&  $jadwal->tanggal == $request->input('tanggal')
            && $jadwal->berulang_tanggal_awal == $request->input('tanggal_awal')
            && $jadwal->berulang_tanggal_akhir == $request->input('tanggal_akhir')
            && $jadwal->waktu_awal == $request->input('waktu_awal')
            && $jadwal->waktu_akhir == $request->input('waktu_akhir')){
             $berubahInti = false;
        }

        if(!$request->input('tanggal') || (!$request->input('tanggal_awal') && !$request->input('tanggal_akhir'))){
            throw ValidationException::withMessages([
                '_status' => 'Jadwal tidak dapat dibuat',
            ]);
        }

        $have_zoom_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'zoom')
            ->first();

        if($have_zoom_peran
            && $request->input('zoom_status') != 'tidak'
            && $request->input('zoom_status') != 'langsung'
            && in_array($request->input('zoom_akun'), ['statsulbar', 'sp2020']))
        {
             if($request->input('tipe') == 'sekali'){
                if(Pinjam_Zoom::staticCheckClash($request->input('zoom_akun'), $request->input('tanggal'), $request->input('tanggal')
                    , $request->input('waktu_awal').':00', $request->input('waktu_akhir').':00', $jadwal->zoom_pinjam_id))
                {
                    throw ValidationException::withMessages([
                        'error_clash_zoom' => 'Sudah terdapat peminjaman zoom di tanggal dan waktu tersebut',
                    ]);
                }
             }else{
                if(Pinjam_Zoom::staticCheckClash($request->input('zoom_akun'), $request->input('tanggal_awal'), $request->input('tanggal_akhir')
                    , $request->input('waktu_awal').':00', $request->input('waktu_akhir').':00', $jadwal->zoom_pinjam_id))
                {
                    throw ValidationException::withMessages([
                        'error_clash_zoom' => 'Sudah terdapat peminjaman zoom di tanggal dan waktu tersebut',
                    ]);
                }
             }
        }

        $have_ruang_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'ruang')
            ->first();


        if($have_ruang_peran
            && $request->input('ruang_status') != 'tidak'
            && in_array($request->input('ruang'), $this->ruang))
        {
             if($request->input('tipe') == 'sekali'){
                if($this->checkRuangClash($request->input('ruang'), $request->input('tanggal'), $request->input('tanggal')
                    , $request->input('waktu_awal').':00', $request->input('waktu_akhir').':00', $jadwal->id))
                {
                    throw ValidationException::withMessages([
                        'error_clash_ruang' => 'Sudah terdapat peminjaman ruang di tanggal dan waktu tersebut',
                    ]);
                }
             }else{
                if($this->checkRuangClash($request->input('ruang'), $request->input('tanggal_awal'), $request->input('tanggal_akhir')
                    , $request->input('waktu_awal').':00', $request->input('waktu_akhir').':00', $jadwal->id))
                {
                    throw ValidationException::withMessages([
                        'error_clash_ruang' => 'Sudah terdapat peminjaman ruang di tanggal dan waktu tersebut',
                    ]);
                }
             }
        }

        if(! ( $jadwal->pembuat_username == Auth::user()->username) ){
            throw ValidationException::withMessages([
                '_status' => 'Gagal mengubah peminjaman zoom!',
            ]);
        }

        DB::beginTransaction();
        try
        {
            $jadwal->kegiatan = $request->input('kegiatan');
            $jadwal->deskripsi = $request->input('description');
            $jadwal->tipe = $request->input('tipe');

            if($request->input('tipe') == 'sekali'){
                $jadwal->tanggal = $request->input('tanggal');
                $jadwal->berulang_tanggal_awal = null;
                $jadwal->berulang_tanggal_akhir = null;
                $jadwal->waktu_awal = $request->input('waktu_awal');
                $jadwal->waktu_akhir = $request->input('waktu_akhir');
            }else if($request->input('tipe') == 'berulang'){
                $jadwal->tanggal = null;
                $jadwal->berulang_tanggal_awal = $request->input('tanggal_awal');
                $jadwal->berulang_tanggal_akhir = $request->input('tanggal_akhir');
                $jadwal->waktu_awal = $request->input('waktu_awal');
                $jadwal->waktu_akhir = $request->input('waktu_akhir');
            }

            $jadwal->status = 'menunggu';

            if($request->input('ruang_status') == 'tidak'){
                $jadwal->ruang_status = 'tidak';
                $jadwal->ruang = null;
                $jadwal->ruang_confirm_username = null;
            }else if($request->input('ruang_status') != 'tidak'){
                if($have_ruang_peran && in_array($request->input('ruang'), $this->ruang)){
                    $jadwal->ruang_status = 'disetujui';
                    $jadwal->ruang = $request->input('ruang');
                    $jadwal->ruang_confirm_username = Auth::user()->username;
                }else if($jadwal->ruang_status == 'disetujui' && $jadwal->ruang == $request->input('ruang') && !$berubahInti ){
                    $jadwal->ruang_status = 'disetujui';
                    $jadwal->ruang = $request->input('ruang');
                }else{
                    $jadwal->ruang_status = 'menunggu';
                    $jadwal->ruang = $request->input('ruang');
                }
            }

            $jadwal->zoom_status = 'tidak';

            if( in_array($jadwal->zoom_status, ['tidak', 'langsung', 'menunggu', 'ditolak']) ){
                if(in_array($jadwal->zoom_status, ['menunggu', 'ditolak'])){
                    $jadwal->zoom_pinjam->delete();
                    $jadwal->zoom_pinjam_id = null;
                }
                $jadwal->zoom_status = 'tidak';
                if($request->input('zoom_status') != 'tidak'){
                    if($request->input('zoom_status') == 'langsung' && $request->input('zoom_langsung_join_url')){
                        $jadwal->zoom_status = 'langsung';
                        $jadwal->zoom_langsung_join_url = $request->input('zoom_langsung_join_url');
                    }else{
                        $pinjam_zoom = Pinjam_Zoom::helper_tambah(
                            $request->input('kegiatan'),
                            $request->input('tipe'),
                            $request->input('zoom_password'),
                            $request->input('tanggal'),
                            $request->input('tanggal_awal'),
                            $request->input('tanggal_akhir'),
                            $request->input('waktu_awal'),
                            $request->input('waktu_akhir'),
                            $have_zoom_peran,
                            in_array($request->input('zoom_akun'), ['statsulbar', 'sp2020']) ? $request->input('zoom_akun') : null,
                            $this->zoom
                        );

                        $jadwal->zoom_pinjam_id = $pinjam_zoom->id;
                        $jadwal->zoom_status = $pinjam_zoom->status;
                        $jadwal->zoom_confirm_username = $pinjam_zoom->confirm_username;
                    }
                }
            }else{
                if(in_array($request->input('zoom_status'), ['tidak']) ){
                    $pinjam_zoom = $jadwal->zoom_pinjam;
                    $pinjam_zoom->helper_hapus($have_zoom_peran, $this->zoom);

                    $jadwal->zoom_pinjam_id = null;
                    $jadwal->zoom_status = $request->input('zoom_status');
                }else if(in_array($request->input('zoom_status'), ['langsung']) ){
                    $pinjam_zoom = $jadwal->zoom_pinjam;
                    $pinjam_zoom->helper_hapus($have_zoom_peran, $this->zoom);

                    $jadwal->zoom_pinjam_id = null;
                    $jadwal->zoom_status = $request->input('zoom_status');
                    $jadwal->zoom_langsung_join_url = $request->input('zoom_langsung_join_url');
                }else if(in_array($request->input('zoom_status'), ['menunggu']) ){
                    $pinjam_zoom = $jadwal->zoom_pinjam;
                    $pinjam_zoom->helper_ubah(
                        $request->input('kegiatan'),
                        $request->input('tipe'),
                        $request->input('zoom_password'),
                        $request->input('tanggal'),
                        $request->input('tanggal_awal'),
                        $request->input('tanggal_akhir'),
                        $request->input('waktu_awal'),
                        $request->input('waktu_akhir'),
                        $have_zoom_peran,
                        in_array($request->input('zoom_akun'), ['statsulbar', 'sp2020']) ? $request->input('zoom_akun') : null,
                        $this->zoom
                    );

                    $jadwal->zoom_pinjam_id = $pinjam_zoom->id;
                    $jadwal->zoom_status = $pinjam_zoom->status;
                    $jadwal->zoom_confirm_username = $pinjam_zoom->confirm_username;
                }
            }

            Jadwal_Peserta::where('jadwal_id', $jadwal->id)->delete();
            if($request->input('peserta')){
                foreach($request->input('peserta') as $item){
                    $peserta = new Jadwal_Peserta();
                    $peserta->id = Str::uuid();
                    $peserta->peserta_type = $item[0];
                    $peserta->peserta_id = $item[1];
                    $peserta->save();
                }
            }

            if($jadwal->ruang_status == 'disetujui' && in_array($jadwal->zoom_status, ['langsung', 'disetujui'])){
                $jadwal->status = 'disetujui';
            }else if($jadwal->ruang_status == 'tidak' && $jadwal->zoom_status == 'tidak'){
                $jadwal->status = 'disetujui';
            }else{
                $jadwal->status = 'menunggu';
            }
            $jadwal->save();

            Jadwal_Peserta::where('jadwal_id', $jadwal->id)->delete();
            $peserta_arrr = [];
            if($request->input('peserta')){
                foreach($request->input('peserta') as $item){
                    $peserta = new Jadwal_Peserta();
                    $peserta->id = Str::uuid();
                    $peserta->jadwal_id = $jadwal->id;
                    $peserta->peserta_type = $item[0];
                    $peserta->peserta_id = $item[1];
                    $peserta->save();
                    array_push( $peserta_arrr, $peserta);
                }
            }


            $this->event_service->saveEventPeserta(
                $jadwal,
                $peserta_arrr
            );
            DB::commit();
            return [
                "_status" => 'Berhasil mengubah peminjaman zoom!',
                "data" => Jadwal::with(['peserta', 'zoom_pinjam'])->where('id', $jadwal->id)->get(),
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal mengubah peminjaman zoom!',
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function hapus(Jadwal $jadwal)
    {
        if(! ($jadwal->pembuat_username == Auth::user()->username) ){
            throw ValidationException::withMessages([
                '_status' => 'Gagal menghapus jadwal!',
            ]);
        }

        $have_zoom_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'zoom')
            ->first();


        DB::beginTransaction();
        try
        {
            if($jadwal->zoom_pinjam_id){
                $zoom_pinjam = $jadwal->zoom_pinjam;
                $zoom_pinjam->helper_hapus($have_zoom_peran, $this->zoom);
            }

            Jadwal_Peserta::where('jadwal_id', $jadwal->id)->delete();
            $jadwal->delete();
            $this->event_service->saveEventPeserta(
                $jadwal,
                []
            );

            DB::commit();
            return [
                "_status" => 'Berhasil menghapus peminjaman zoom!'
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal menghapus peminjaman zoom!',
            ]);
        }
    }

    public function approval_ruang(Request $request, Jadwal $jadwal)
    {
        $validated = $request->validate([
            'ruang_status'      => 'in:disetujui,ditolak',
        ]);

        $have_ruang_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'ruang')
            ->first();

        if(! ($have_ruang_peran) ){
            throw ValidationException::withMessages([
                '_status' => 'Gagal approval peminjaman zoom!',
            ]);
        }

        if($have_ruang_peran
            && $request->input('ruang_status') != 'tidak'
            && in_array($request->input('ruang'), $this->ruang))
        {
             if($request->input('tipe') == 'sekali'){
                if($this->checkRuangClash($jadwal->ruang, $jadwal->tanggal, $jadwal->tanggal
                    , $jadwal->waktu_awal, $jadwal->waktu_akhir, $jadwal->id))
                {
                    throw ValidationException::withMessages([
                        'error_clash_ruang' => 'Sudah terdapat peminjaman zoom di tanggal dan waktu tersebut',
                    ]);
                }
             }else{
                if($this->checkRuangClash($jadwal->ruang, $jadwal->berulang_tanggal_awal, $jadwal->berulang_tanggal_akhir
                    , $jadwal->waktu_awal, $jadwal->waktu_akhir, $jadwal->id))
                {
                    throw ValidationException::withMessages([
                       'error_clash_ruang' => 'Sudah terdapat peminjaman zoom di tanggal dan waktu tersebut',
                    ]);
                }
             }
        }

        DB::beginTransaction();
        try
        {
            if($have_ruang_peran && in_array($request->ruang_status, ['disetujui', 'ditolak'])){
                $jadwal->ruang_status = $request->ruang_status;
                $jadwal->ruang_confirm_username = Auth::user()->username;
            }

            if($jadwal->ruang_status == 'disetujui' && in_array($jadwal->zoom_status, ['langsung', 'disetujui'])){
                $jadwal->status = 'disetujui';
            }else{
                $jadwal->status = 'menunggu';
            }
            $jadwal->save();
            DB::commit();
            return [
                "_status" => 'Berhasil approval peminjaman zoom!',
                "data" => $jadwal
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal approval peminjaman zoom!',
            ]);
        }
    }

    public function approval_zoom(Request $request, Jadwal $jadwal)
    {
        $validated = $request->validate([
            'zoom_status'       => 'in:disetujui,ditolak',
            'zoom_akun'         => 'in:statsulbar,sp2020',
        ]);

        $have_zoom_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'zoom')
            ->first();

        if(! ($have_zoom_peran) ){
            throw ValidationException::withMessages([
                '_status' => 'Gagal approval peminjaman zoom!',
            ]);
        }

        if($have_zoom_peran && $request->input('zoom_status') == 'disetujui'){
           if($jadwal->zoom_pinjam->checkClash($request->input('zoom_akun'))){
                throw ValidationException::withMessages([
                    'error_clash_zoom' => 'Sudah terdapat peminjaman ruang di tanggal dan waktu tersebut',
                ]);
            }
        }


        DB::beginTransaction();
        try
        {
            if($have_zoom_peran){
                if($jadwal->zoom_pinjam_id){
                    $pinjam_zoom = $jadwal->zoom_pinjam;
                    $pinjam_zoom->helper_approval($request->input('zoom_status'), $request->input('zoom_akun'), $this->zoom);

                    $jadwal->zoom_pinjam_id = $pinjam_zoom->id;
                    $jadwal->zoom_status = $pinjam_zoom->status;
                    $jadwal->zoom_confirm_username = $pinjam_zoom->confirm_username;
                }
            }

            if($jadwal->ruang_status == 'disetujui' && in_array($jadwal->zoom_status, ['langsung', 'disetujui'])){
                $jadwal->status = 'disetujui';
            }else{
                $jadwal->status = 'menunggu';
            }
            $jadwal->save();
            DB::commit();
            return [
                "_status" => 'Berhasil approval peminjaman zoom!',
                "data" => $jadwal
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal approval peminjaman zoom!',
            ]);
        }
    }

    public function checkRuangClash($ruang, $tanggal_awal, $tanggal_akhir, $waktu_awal, $waktu_akhir, $ignore_id = null){
        $jadwal_daftar = Jadwal::where('ruang', $ruang)
            ->where('ruang_status', 'disetujui')
            ->when($ignore_id, function($query) use ($ignore_id) {
                return $query->where('id', '!=', $ignore_id);
            })
            ->where(function ($query) use($tanggal_awal, $tanggal_akhir) {
                $query->orWhere(function (Builder $query) use ($tanggal_awal, $tanggal_akhir){
                            $query->where('tipe', 'sekali')
                                ->whereBetween('tanggal', [$tanggal_awal, $tanggal_akhir]);
                        })
                        ->orWhere(function (Builder $query) use ($tanggal_awal, $tanggal_akhir) {
                            $query->where('tipe', 'berulang')
                                ->where('berulang_tanggal_awal', '<=', $tanggal_akhir)
                                ->where('berulang_tanggal_akhir', '>=', $tanggal_awal);
                        });
            })
            ->get();

        $input_waktu_awal = Carbon::createFromFormat('H:i:s', strlen($waktu_awal) == 5 ? $waktu_awal.':00' : $waktu_awal);
        $input_waktu_akhir = Carbon::createFromFormat('H:i:s', strlen($waktu_akhir) == 5 ? $waktu_akhir.':00' : $waktu_akhir);

        $isClash = false;

        foreach($jadwal_daftar as $item){
            $item_waktu_awal = Carbon::createFromFormat('H:i:s', $item->waktu_awal);
            $item_waktu_akhir = Carbon::createFromFormat('H:i:s', $item->waktu_akhir);

            if ($input_waktu_awal < $item_waktu_akhir && $input_waktu_akhir > $item_waktu_awal) {
                $isClash = true;
                break;
            }
        }

        return $isClash;
    }
}

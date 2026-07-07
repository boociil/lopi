<?php

namespace App\Http\Controllers\Lopi\Pinjam;

use App\Http\Controllers\Controller;
use App\Http\Traits\Zoom_Traits;
use App\Model\Lopi\Jadwal;
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
use App\Model\Lopi\Pinjam_Zoom;
use App\Model\Umum\User;
use Exception;
use Inertia\Inertia;

class Ruang_Controller extends Controller
{

    use Zoom_Traits;

    public function __construct()
    {
        $this->define_config();
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
        }else if(session()->has('pinjam_zoom_index')){
            $selectedDate = Carbon::today()->format('Y-m-d');
            $view = session('pinjam_zoom_index')['view'];
        }else{
            $selectedDate = Carbon::today()->format('Y-m-d');
            $view = 'month';
        }

        session()->put('pinjam_zoom_index', ['view' => $view]);

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

        if($request->isMethod('get')){
            return Inertia::render('pinjam/zoom', [
                "user" => User::all(),
                "have_zoom_peran" => $have_zoom_peran,
            ]);
        }else if($request->isMethod('post')){
            $pinjam_zoom_bulan_daftar = Pinjam_Zoom::orWhere(function (Builder $query) use ($tanggal_awal, $tanggal_akhir){
                        $query->where('tipe', 'sekali')
                            ->whereBetween('tanggal', [$tanggal_awal->format('Y-m-d'), $tanggal_akhir->format('Y-m-d')]);
                    })
                    ->orWhere(function (Builder $query) use ($tanggal_awal, $tanggal_akhir) {
                        $query->where('tipe', 'berulang')
                            ->where('berulang_tanggal_awal', '<=', $tanggal_akhir)
                            ->where('berulang_tanggal_akhir', '>=', $tanggal_awal);
                    })
                    ->get();

            foreach($pinjam_zoom_bulan_daftar as $item){
                $item->zoom_start_url = null;
            }

            $pinjam_zoom_statsulbar = $this->zoom_getAllMeeting('statsulbar', $tanggal_awal->format('Y-m-d'), $tanggal_akhir->format('Y-m-d'));
            $pinjam_zoom_sp2020 = $this->zoom_getAllMeeting('sp2020', $tanggal_awal->format('Y-m-d'), $tanggal_akhir->format('Y-m-d'));

            // $pinjam_zoom_statsulbar = ['status' => false, 'data' => []];
            // $pinjam_zoom_sp2020 = ['status' => false, 'data' => []];

            return [
                "have_zoom_peran" => $have_zoom_peran,
                "pinjam_zoom_data" => $pinjam_zoom_bulan_daftar,
                "zoom_statsulbar" => $pinjam_zoom_statsulbar,
                "zoom_sp2020" => $pinjam_zoom_sp2020,
            ];
        }
    }

    public function tambah(Request $request)
    {
        $validated = $request->validate([
            'kegiatan'          => 'required|string|max:255',
            'password'          => 'required|string',
            'tipe'              => 'required|in:sekali,berulang',
            'tanggal'           => 'date',
            'tanggal_awal'      => 'date',
            'tanggal_akhir'     => 'date|after_or_equal:tanggal_awal',
            'waktu_awal'        => 'required|date_format:H:i',
            'waktu_akhir'       => 'required|date_format:H:i|after:waktu_awal',
            'zoom_akun'         => 'nullable|in:statsulbar,sp2020'
        ]);


        if(!$request->input('tanggal') || (!$request->input('tanggal_awal') && !$request->input('tanggal_akhir'))){
            throw ValidationException::withMessages([
                '_status' => 'Zoom tidak dapat dibuat',
            ]);
        }

        $have_zoom_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'zoom')
            ->first();

        if($have_zoom_peran && in_array($request->input('zoom_akun'), ['statsulbar', 'sp2020'])){
             if($request->input('tipe') == 'sekali'){
                if(Pinjam_Zoom::staticCheckClash($request->input('zoom_akun'), $request->input('tanggal'), $request->input('tanggal')
                    , $request->input('waktu_awal').':00', $request->input('waktu_akhir').':00'))
                {
                    throw ValidationException::withMessages([
                        'error_clash' => 'Sudah terdapat peminjaman di tanggal dan waktu tersebut',
                    ]);
                }
             }else{
                if(Pinjam_Zoom::staticCheckClash($request->input('zoom_akun'), $request->input('tanggal_awal'), $request->input('tanggal_akhir')
                    , $request->input('waktu_awal').':00', $request->input('waktu_akhir').':00'))
                {
                    throw ValidationException::withMessages([
                        'error_clash' => 'Sudah terdapat peminjaman di tanggal dan waktu tersebut',
                    ]);
                }
             }
        }

        DB::beginTransaction();
        try
        {

            $pinjam_zoom = Pinjam_Zoom::helper_tambah(
                $request->input('kegiatan'),
                $request->input('tipe'),
                $request->input('password'),
                $request->input('tanggal'),
                $request->input('tanggal_awal'),
                $request->input('tanggal_akhir'),
                $request->input('waktu_awal'),
                $request->input('waktu_akhir'),
                $have_zoom_peran,
                $request->input('zoom_akun')
            );

            DB::commit();
            return [
                "_status" => 'Berhasil membuat peminjaman zoom!',
                "data" => $pinjam_zoom
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal membuat peminjaman zoom!',
            ]);
        }
    }

    public function ubah(Request $request, Pinjam_Zoom $pinjamZoom)
    {
        $validated = $request->validate([
            'kegiatan'          => 'required|string|max:255',
            'tipe'              => 'required|in:sekali,berulang',
            'tanggal'           => 'date',
            'tanggal_awal'      => 'date',
            'tanggal_akhir'     => 'date|after_or_equal:tanggal_awal',
            'waktu_awal'        => 'required|date_format:H:i',
            'waktu_akhir'       => 'required|date_format:H:i|after:waktu_awal',
            'zoom_akun'         => 'nullable|in:statsulbar,sp2020'
        ]);

        if(!$request->input('tanggal') || (!$request->input('tanggal_awal') && !$request->input('tanggal_akhir'))){
            throw ValidationException::withMessages([
                '_status' => 'Zoom tidak dapat dibuat',
            ]);
        }

        $have_zoom_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'zoom')
            ->first();

        if($have_zoom_peran && in_array($request->input('zoom_akun'), ['statsulbar', 'sp2020'])){
             if($request->input('tipe') == 'sekali'){
                if(Pinjam_Zoom::staticCheckClash($request->input('zoom_akun'), $request->input('tanggal'), $request->input('tanggal')
                    , $request->input('waktu_awal').':00', $request->input('waktu_akhir').':00'))
                {
                    throw ValidationException::withMessages([
                        'error_clash' => 'Sudah terdapat peminjaman di tanggal dan waktu tersebut',
                    ]);
                }
             }else{
                if(Pinjam_Zoom::staticCheckClash($request->input('zoom_akun'), $request->input('tanggal_awal'), $request->input('tanggal_akhir')
                    , $request->input('waktu_awal').':00', $request->input('waktu_akhir').':00'))
                {
                    throw ValidationException::withMessages([
                        'error_clash' => 'Sudah terdapat peminjaman di tanggal dan waktu tersebut',
                    ]);
                }
             }
        }

        if(! ($have_zoom_peran || $pinjamZoom->pembuat_username == Auth::user()->username) ){
            throw ValidationException::withMessages([
                '_status' => 'Gagal mengubah peminjaman zoom!',
            ]);
        }

        DB::beginTransaction();
        try
        {
           $pinjamZoom->helper_ubah(
                $request->input('kegiatan'),
                $request->input('tipe'),
                $request->input('password'),
                $request->input('tanggal'),
                $request->input('tanggal_awal'),
                $request->input('tanggal_akhir'),
                $request->input('waktu_awal'),
                $request->input('waktu_akhir'),
                $have_zoom_peran,
                $request->input('zoom_akun')
            );
            DB::commit();
            return [
                "_status" => 'Berhasil mengubah peminjaman zoom!',
                "data" => $pinjamZoom
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
    public function hapus(Pinjam_Zoom $pinjamZoom)
    {
        $have_zoom_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'zoom')
            ->first();

        DB::beginTransaction();
        try
        {
            $pinjamZoom->helper_hapus($have_zoom_peran);
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

    /**
     * Approve a pinjam zoom request
     */
    public function approval(Request $request, Pinjam_Zoom $pinjamZoom)
    {
        $validated = $request->validate([
            'status'              => 'required|in:disetujui,ditolak',
            'zoom_akun'         => 'in:statsulbar,sp2020'
        ]);

        $have_zoom_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'zoom')
            ->first();

        if(! ($have_zoom_peran) ){
            throw ValidationException::withMessages([
                '_status' => 'Gagal approval peminjaman zoom!',
            ]);
        }

        if($have_zoom_peran && $request->input('status') == 'disetujui'){
           if($pinjamZoom->checkClash($request->input('zoom_akun'))){
                throw ValidationException::withMessages([
                    'error_clash' => 'Sudah terdapat peminjaman di tanggal dan waktu tersebut',
                ]);
            }
        }


        DB::beginTransaction();
        try
        {
            $pinjamZoom->status = $request->input('status');
            $pinjamZoom->zoom_akun = $request->input('zoom_akun');
            $pinjamZoom->helper_approval($request->input('status'), $request->input('zoom_akun'));

            $jadwal = Jadwal::where('zoom_pinjam_id', $pinjamZoom->id)->first();
            if($jadwal){
                $jadwal->zoom_status = $pinjamZoom->status;
                if($jadwal->ruang_status == 'disetujui' && in_array($jadwal->zoom_status, ['langsung', 'disetujui'])){
                    $jadwal->status = 'disetujui';
                }else{
                    $jadwal->status = 'menunggu';
                }
                $jadwal->save();
            }
            DB::commit();
            return [
                "_status" => 'Berhasil approval peminjaman zoom!',
                "data" => $pinjamZoom
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal approval peminjaman zoom!',
            ]);
        }
    }

    public function get_mmeting(Request $request)
    {
        $have_zoom_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'zoom')
            ->first();

        $pinjamZoom = Pinjam_Zoom::where('zoom_meeting_id', $request->zoom_meeting_id)->first();

        if($pinjamZoom){
            if(!$pinjamZoom->zoom_meeting_id){
                throw ValidationException::withMessages([
                    '_status' => 'Gagal mendapatkan zoom!',
                ]);
            }

            if(! ($have_zoom_peran || $pinjamZoom->pembuat_username == Auth::user()->username) ){
                throw ValidationException::withMessages([
                    '_status' => 'Gagal mendapatkan zoom!',
                ]);
            }

            return $pinjamZoom->getMeeting();
        }else{
            if(! ($have_zoom_peran) ){
                throw ValidationException::withMessages([
                    '_status' => 'Gagal mendapatkan zoom!',
                ]);
            }

            if($request->zoom_meeting_id && $request->zoom_akun){
                Log::alert($request->zoom_meeting_id);
                Log::alert($request->zoom_akun);

                $pinjamZoom = new Pinjam_Zoom();
                $pinjamZoom->zoom_meeting_id = $request->zoom_meeting_id;
                $pinjamZoom->zoom_akun = $request->zoom_akun;

                return $pinjamZoom->getMeeting();
            }else{
                throw ValidationException::withMessages([
                    '_status' => 'Gagal mendapatkan zoom!',
                ]);
            }


        }
    }
}

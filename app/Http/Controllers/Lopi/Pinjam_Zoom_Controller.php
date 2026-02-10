<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Http\Traits\Zoom_Traits;
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

class Pinjam_Zoom_Controller extends Controller
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
            $view = 'week';
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

        $tanggal_carbon = Carbon::today();

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


        $pinjam_zoom_statsulbar = $this->zoom_getAllMeeting('statsulbar', $tanggal_awal->format('Y-m-d'), $tanggal_akhir->format('Y-m-d'));
        $pinjam_zoom_sp2020 = $this->zoom_getAllMeeting('sp2020', $tanggal_awal->format('Y-m-d'), $tanggal_akhir->format('Y-m-d'));

        // $pinjam_zoom_statsulbar = ['status' => false, 'data' => []];
        // $pinjam_zoom_sp2020 = ['status' => false, 'data' => []];

        for($i=0; $i<count($pinjam_zoom_bulan_daftar); $i++){
            if($pinjam_zoom_bulan_daftar[$i]->tanggal){
                 if (!Carbon::parse($pinjam_zoom_bulan_daftar[$i]->tanggal)->eq($tanggal_carbon)) {
                    $pinjam_zoom_bulan_daftar[$i]->zoom_start_url = null;
                }
            }
             if($pinjam_zoom_bulan_daftar[$i]->berulang_tanggal_awal){
                 if (!$tanggal_carbon->between(Carbon::parse($pinjam_zoom_bulan_daftar[$i]->berulang_tanggal_awal), Carbon::parse($pinjam_zoom_bulan_daftar[$i]->berulang_tanggal_akhir))) {
                    $pinjam_zoom_bulan_daftar[$i]->zoom_start_url = null;
                }
            }
        }

        if($request->isMethod('get')){
            return Inertia::render('pinjam_zoom', [
                "user" => User::all(),
                "have_zoom_peran" => $have_zoom_peran,
                "pinjam_zoom_data" => $pinjam_zoom_bulan_daftar,
                "zoom_statsulbar" => $pinjam_zoom_statsulbar,
                "zoom_sp2020" => $pinjam_zoom_sp2020,
            ]);
        }else if($request->isMethod('post')){
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
            'zoom_akun'         => 'in:statsulbar,sp2020'
        ]);

        if(!is_null($request->input('tanggal')) || (!is_null($request->input('tanggal_awal')) && !is_null($request->input('tanggal_akhir')))){
            throw ValidationException::withMessages([
                '_status' => 'Peran tidak dapat dibuat',
            ]);
        }

        $have_zoom_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'zoom')
            ->first();

        DB::beginTransaction();
        try
        {
            $pinjam_zoom = new Pinjam_Zoom();
            $pinjam_zoom->id = Str::uuid();
            $pinjam_zoom->kegiatan = $request->input('kegiatan');
            $pinjam_zoom->tipe = $request->input('tipe');
            $pinjam_zoom->password = $request->input('password');

            if($request->input('tipe') == 'sekali'){
                $pinjam_zoom->tanggal = $request->input('tanggal');
                $pinjam_zoom->waktu_awal = $request->input('waktu_awal');
                $pinjam_zoom->waktu_akhir = $request->input('waktu_akhir');
            }else if($request->input('tipe') == 'berulang'){
                $pinjam_zoom->berulang_tanggal_awal = $request->input('tanggal_awal');
                $pinjam_zoom->berulang_tanggal_akhir = $request->input('tanggal_akhir');
                $pinjam_zoom->waktu_awal = $request->input('waktu_awal');
                $pinjam_zoom->waktu_akhir = $request->input('waktu_akhir');
            }

            $pinjam_zoom->status = 'menunggu';
            $pinjam_zoom->pembuat_username = Auth::user()->username;

            if($have_zoom_peran && in_array($request->input('zoom_akun'), ['statsulbar', 'sp2020'])){
                $pinjam_zoom->status = 'disetujui';
                $pinjam_zoom->confirm_username = Auth::user()->username;

                if(in_array($request->input('zoom_akun'), ['statsulbar', 'sp2020'])){
                    $this->zoom_meta_createMeeting($request->input('zoom_akun'), $pinjam_zoom);
                }else{
                    throw ValidationException::withMessages([
                        'status' => 'Zoom tidak dapat dibuat',
                    ]);
                }
            }
            $pinjam_zoom->save();

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
            'zoom_akun'         => 'in:statsulbar,sp2020'
        ]);

        if(!is_null($request->input('tanggal')) || (!is_null($request->input('tanggal_awal')) && !is_null($request->input('tanggal_akhir')))){
            throw ValidationException::withMessages([
                '_status' => 'Peran tidak dapat dibuat',
            ]);
        }

        $have_zoom_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'zoom')
            ->first();

        if(! ($have_zoom_peran || $pinjamZoom->pembuat_username == Auth::user()->username) ){
            throw ValidationException::withMessages([
                '_status' => 'Gagal mengubah peminjaman zoom!',
            ]);
        }

        DB::beginTransaction();
        try
        {
            $pinjamZoom->kegiatan = $request->input('kegiatan');
            $pinjamZoom->tipe = $request->input('tipe');
            $pinjamZoom->password = $request->input('password');
            if($request->input('tipe') == 'sekali'){
                $pinjamZoom->tanggal = $request->input('tanggal');
                $pinjamZoom->waktu_awal = $request->input('waktu_awal');
                $pinjamZoom->waktu_akhir = $request->input('waktu_akhir');
            }else if($request->input('tipe') == 'berulang'){
                $pinjamZoom->berulang_tanggal_awal = $request->input('tanggal_awal');
                $pinjamZoom->berulang_tanggal_akhir = $request->input('tanggal_akhir');
                $pinjamZoom->waktu_awal = $request->input('waktu_awal');
                $pinjamZoom->waktu_akhir = $request->input('waktu_akhir');
            }
            $pinjamZoom->status = 'menunggu';
            $pinjamZoom->pembuat_username = Auth::user()->username;

            if($have_zoom_peran && in_array($request->input('zoom_akun'), ['statsulbar', 'sp2020'])){
                $pinjamZoom->status = 'disetujui';
                $pinjamZoom->confirm_username = Auth::user()->username;
                // if($pinjamZoom->zoom_meeting_id){
                //     $this->zoom_meta_updateMeeting($request->input('zoom_akun'), $pinjamZoom);
                // }else
                if(!$pinjamZoom->zoom_meeting_id){
                    $this->zoom_meta_createMeeting($request->input('zoom_akun'), $pinjamZoom);
                }else if($pinjamZoom->zoom_meeting_id && $pinjamZoom->zoom_akun != $request->input('zoom_akun')){
                    $this->zoom_deleteMeeting($request->input('zoom_akun'), $pinjamZoom->zoom_meeting_id);
                    $this->zoom_meta_createMeeting($request->input('zoom_akun'), $pinjamZoom);
                }else if($pinjamZoom->zoom_meeting_id && $pinjamZoom->zoom_akun == $request->input('zoom_akun')){
                    $this->zoom_meta_updateMeeting($request->input('zoom_akun'), $pinjamZoom);
                }
            }else{
                if($pinjamZoom->zoom_meeting_id){
                    $this->zoom_deleteMeeting($pinjamZoom->zoom_akun, "".$pinjamZoom->zoom_meeting_id);
                    throw ValidationException::withMessages([
                        'status' => 'Zoom gagal diubah',
                    ]);

                    $pinjamZoom->zoom_akun = null;
                    $pinjamZoom->zoom_meeting_id = null;
                    $pinjamZoom->zoom_start_url = null;
                    $pinjamZoom->zoom_join_url = null;
                }
            }
            $pinjamZoom->save();
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

        if(! ($have_zoom_peran || $pinjamZoom->pembuat_username == Auth::user()->username) ){
            throw ValidationException::withMessages([
                '_status' => 'Gagal menghapus peminjaman zoom!',
            ]);
        }

        if($pinjamZoom->zoom_meeting_id){
            $zoom_app = $this->zoom_deleteMeeting($pinjamZoom->zoom_akun, "".$pinjamZoom->zoom_meeting_id);
            if(!$zoom_app['status']){
                throw ValidationException::withMessages([
                    'status' => 'Zoom gagal dihapus',
                ]);
            }
        }

        DB::beginTransaction();
        try
        {
            $pinjamZoom->delete();
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


        DB::beginTransaction();
        try
        {
            if($request->input('status') == 'ditolak'){
                if($pinjamZoom->zoom_meeting_id){
                    $zoom_app = $this->zoom_deleteMeeting($pinjamZoom->zoom_akun, "".$pinjamZoom->zoom_meeting_id);
                    if(!$zoom_app['status']){
                        throw ValidationException::withMessages([
                            'status' => 'Zoom gagal dibuat',
                        ]);
                    }

                    $pinjamZoom->status = 1;
                    $pinjamZoom->zoom_akun = null;
                    $pinjamZoom->zoom_meeting_id = null;
                    $pinjamZoom->zoom_start_url = null;
                    $pinjamZoom->zoom_join_url = null;
                }
            }else if($request->input('status') == 'disetujui'){
                if(!$pinjamZoom->zoom_meeting_id){
                    $this->zoom_meta_createMeeting($request->input('zoom_akun'), $pinjamZoom);
                }else {
                    if($pinjamZoom->zoom_akun != $request->input('zoom_akun')){
                        $this->zoom_deleteMeeting($request->input('zoom_akun'), $pinjamZoom->zoom_meeting_id);
                        $this->zoom_meta_createMeeting($request->input('zoom_akun'), $pinjamZoom);
                    }
                }
            }

            $pinjamZoom->status = $request->input('status');
            $pinjamZoom->confirm_username = Auth::user()->username;
            $pinjamZoom->save();
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
}

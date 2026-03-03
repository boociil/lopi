<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Http\Traits\Zoom_Traits;
use App\Model\Lopi\Jadwal;
use App\Model\Lopi\Jadwal_Peserta;
use App\Model\Lopi\Pinjam_Zoom;
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
use App\Model\Umum\User;
use Exception;
use Inertia\Inertia;

class Jadwal_Controller extends Controller
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

        $tanggal_carbon = Carbon::today();

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

        // $jadwal_statsulbar = ['status' => false, 'data' => []];
        // $jadwal_sp2020 = ['status' => false, 'data' => []];

        for($i=0; $i<count($jadwal_daftar); $i++){
            if($jadwal_daftar[$i]->tanggal){
                 if (!Carbon::parse($jadwal_daftar[$i]->tanggal)->eq($tanggal_carbon)) {
                    $jadwal_daftar[$i]->zoom_start_url = null;
                }
            }
             if($jadwal_daftar[$i]->berulang_tanggal_awal){
                 if (!$tanggal_carbon->between(Carbon::parse($jadwal_daftar[$i]->berulang_tanggal_awal), Carbon::parse($jadwal_daftar[$i]->berulang_tanggal_akhir))) {
                    $jadwal_daftar[$i]->zoom_start_url = null;
                }
            }
        }


        if($request->isMethod('get')){
            return Inertia::render('jadwal', [
                "have_zoom_peran" => $have_zoom_peran,
                "have_ruang_peran" => $have_ruang_peran,
                "jadwal_daftar" => $jadwal_daftar,
                "ruang" => $this->ruang,
                "user" => User::all(),
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
                '_status' => 'Peran tidak dapat dibuat',
            ]);
        }

        $have_zoom_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'zoom')
            ->first();
        $have_ruang_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'ruang')
            ->first();

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
                if($request->input('zoom_status') == 'langsung' && $request->input('zoom_join_url_langsung')){
                    $jadwal->zoom_status = 'langsung';
                    $jadwal->zoom_join_url = $request->input('zoom_join_url_langsung');
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
                    $peserta->jadwal_id = $jadwal->id;
                    $peserta->username = $item;
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

            DB::commit();
            return [
                "_status" => 'Berhasil membuat peminjaman zoom!',
                "data" => $jadwal
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

        if(!$request->input('tanggal') || (!$request->input('tanggal_awal') && !$request->input('tanggal_akhir'))){
            throw ValidationException::withMessages([
                '_status' => 'Peran tidak dapat dibuat',
            ]);
        }

        $have_zoom_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'zoom')
            ->first();
        $have_ruang_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'ruang')
            ->first();

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
                $jadwal->waktu_awal = $request->input('waktu_awal');
                $jadwal->waktu_akhir = $request->input('waktu_akhir');
            }else if($request->input('tipe') == 'berulang'){
                $jadwal->berulang_tanggal_awal = $request->input('tanggal_awal');
                $jadwal->berulang_tanggal_akhir = $request->input('tanggal_akhir');
                $jadwal->waktu_awal = $request->input('waktu_awal');
                $jadwal->waktu_akhir = $request->input('waktu_akhir');
            }

            $jadwal->status = 'menunggu';


            $jadwal->ruang_status = 'tidak';
            if($request->input('ruang_status') == 'tidak'){
                $jadwal->ruang_status = 'tidak';
                $jadwal->ruang = null;
                $jadwal->ruang_confirm_username = null;
            }else if($request->input('ruang_status') != 'tidak'){
                if($have_ruang_peran && in_array($request->input('ruang'), $this->ruang)){
                    $jadwal->ruang_status = 'disetujui';
                    $jadwal->ruang = $request->input('ruang');
                    $jadwal->ruang_confirm_username = Auth::user()->username;
                }else if($jadwal->ruang_status == 'disetujui' && $jadwal->ruang == $request->input('ruang')){
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
                    if($request->input('zoom_status') == 'langsung' && $request->input('zoom_join_url_langsung')){
                        $jadwal->zoom_status = 'langsung';
                        $jadwal->zoom_join_url = $request->input('zoom_join_url_langsung');
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
                        );

                        $jadwal->zoom_pinjam_id = $pinjam_zoom->id;
                        $jadwal->zoom_status = $pinjam_zoom->status;
                        $jadwal->zoom_confirm_username = $pinjam_zoom->confirm_username;
                    }
                }
            }else{
                if(in_array($request->input('zoom_status'), ['tidak']) ){
                    $pinjam_zoom = $jadwal->zoom_pinjam;
                    $pinjam_zoom->helper_hapus($have_zoom_peran);

                    $jadwal->zoom_pinjam_id = null;
                    $jadwal->zoom_status = $request->input('zoom_status');
                }else if(in_array($request->input('zoom_status'), ['langsung']) ){
                    $pinjam_zoom = $jadwal->zoom_pinjam;
                    $pinjam_zoom->helper_hapus($have_zoom_peran);

                    $jadwal->zoom_pinjam_id = null;
                    $jadwal->zoom_status = $request->input('zoom_status');
                    $jadwal->zoom_join_url = $request->input('zoom_join_url_langsung');
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
                    $peserta->jadwal_id = $jadwal->id;
                    $peserta->username = $item;
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
            DB::commit();
            return [
                "_status" => 'Berhasil mengubah peminjaman zoom!',
                "data" => $jadwal
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
                $zoom_pinjam->helper_hapus($have_zoom_peran);
            }

            Jadwal_Peserta::where('jadwal_id', $jadwal->id)->delete();
            $jadwal->delete();
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
    public function approval(Request $request, Jadwal $jadwal)
    {
        $validated = $request->validate([
            'zoom_status'       => 'in:disetujui,ditolak',
            'zoom_akun'         => 'in:statsulbar,sp2020',
            'ruang_status'      => 'in:disetujui,ditolak',
        ]);

        $have_zoom_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'zoom')
            ->first();
        $have_ruang_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'ruang')
            ->first();

        if(! ($have_zoom_peran || $have_ruang_peran) ){
            throw ValidationException::withMessages([
                '_status' => 'Gagal approval peminjaman zoom!',
            ]);
        }


        DB::beginTransaction();
        try
        {
            if($have_zoom_peran){
                if($jadwal->zoom_pinjam_id){
                    $pinjam_zoom = $jadwal->zoom_pinjam;
                    $pinjam_zoom->helper_approval($request->input('zoom_status'), $request->input('zoom_status'));

                    $jadwal->zoom_pinjam_id = $pinjam_zoom->id;
                    $jadwal->zoom_status = $pinjam_zoom->status;
                    $jadwal->zoom_confirm_username = $pinjam_zoom->confirm_username;
                }
            }

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
}

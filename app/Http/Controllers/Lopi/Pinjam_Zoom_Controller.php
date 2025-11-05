<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Model\Lopi\Pinjam_Zoom;
use App\Model\Umum\Peran;
use App\Model\Umum\User;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

use GuzzleHttp\Client;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Exception;

use function PHPUnit\Framework\isNull;

class Pinjam_Zoom_Controller extends Controller
{

    private $pinjam_zoom_config ;

    public function __construct(Request $request)
    {
        $this->zoom_config = [
                'statsulbar' => [
                    'account_id' => env('STATSULBAR_ZOOM_ACCOUNT_ID'),
                    'client_id' => env('STATSULBAR_ZOOM_CLIENT_ID'),
                    'client_secret' => env('STATSULBAR_ZOOM_CLIENT_SECRET'),
                ],
                'sp2020' => [
                    'account_id' => env('SP2020_ZOOM_ACCOUNT_ID'),
                    'client_id' => env('SP2020_ZOOM_CLIENT_ID'),
                    'client_secret' => env('SP2020_ZOOM_CLIENT_SECRET'),
                ]
            ];
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

        $pinjam_zoom_statsulbar = ['status' => false, 'data' => []];
        $pinjam_zoom_sp2020 = ['status' => false, 'data' => []];

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

        for($i=0; $i<count($pinjam_zoom_statsulbar['data']); $i++){
            if($pinjam_zoom_statsulbar['data'][$i]->tanggal){
                 if (!Carbon::parse($pinjam_zoom_statsulbar['data'][$i]->tanggal)->eq($tanggal_carbon)) {
                    $pinjam_zoom_statsulbar['data'][$i]->zoom_start_url = null;
                }
            }
             if($pinjam_zoom_statsulbar['data'][$i]->berulang_tanggal_awal){
                 if (!$tanggal_carbon->between(Carbon::parse($pinjam_zoom_statsulbar['data'][$i]->berulang_tanggal_awal), Carbon::parse($pinjam_zoom_statsulbar['data'][$i]->berulang_tanggal_akhir))) {
                    $pinjam_zoom_statsulbar['data'][$i]->zoom_start_url = null;
                }
            }
        }

        for($i=0; $i<count($pinjam_zoom_sp2020['data']); $i++){
            if($pinjam_zoom_sp2020['data'][$i]->tanggal){
                 if (!Carbon::parse($pinjam_zoom_sp2020['data'][$i]->tanggal)->eq($tanggal_carbon)) {
                    $pinjam_zoom_sp2020['data'][$i]->zoom_start_url = null;
                }
            }
             if($pinjam_zoom_sp2020['data'][$i]->berulang_tanggal_awal){
                 if (!$tanggal_carbon->between(Carbon::parse($pinjam_zoom_sp2020['data'][$i]->berulang_tanggal_awal), Carbon::parse($pinjam_zoom_sp2020['data'][$i]->berulang_tanggal_akhir))) {
                    $pinjam_zoom_sp2020['data'][$i]->zoom_start_url = null;
                }
            }
        }

        if($request->isMethod('get')){
            return Inertia::render('pinjam_zoom', [
                "user" => User::all(),
                "have_zoom_peran" => $have_zoom_peran,
                "zoom_bulan_daftar" => $pinjam_zoom_bulan_daftar,
                "zoom_statsulbar" => $pinjam_zoom_statsulbar,
                "zoom_sp2020" => $pinjam_zoom_sp2020,
            ]);
        }else if($request->isMethod('post')){
            return [
                "have_zoom_peran" => $have_zoom_peran,
                "zoom_bulan_daftar" => $pinjam_zoom_bulan_daftar,
                "zoom_statsulbar" => $pinjam_zoom_statsulbar,
                "zoom_sp2020" => $pinjam_zoom_sp2020,
            ];
        }
    }

    public function tambah1(Request $request)
    {
        $validated = $request->validate([
            'topic' => 'required|string',
            'password' => 'required|string',
            'tipe'              => 'required|in:sekali,berulang',
            'tanggal'           => 'date',
            'tanggal_awal'      => 'date',
            'tanggal_akhir'     => 'date|after_or_equal:tanggal_awal',
            'waktu_awal'        => 'required|date_format:H:i',
            'waktu_akhir'       => 'required|date_format:H:i|after:waktu_awal',
            'zoom_status' => ['integer', Rule::in([0, 1, 2])],
            'zoom_akun' => ['string', Rule::in(['', 'statsulbar', 'sp2020'])]
        ]);

        if(!isNull($request->input('tanggal')) || (!isNull($request->input('tanggal_awal')) && !isNull($request->input('tanggal_akhir')))){
            throw ValidationException::withMessages([
                '_status' => 'Peran tidak dapat dibuat',
            ]);
        }

        $have_zoom_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('peran', 'mobil')
            ->first();

        DB::beginTransaction();
        try
        {
            $return_data = [];
            if($request->input('tipe') == 'sekali'){
                $pinjam_zoom = new Pinjam_Zoom();
                $pinjam_zoom->id = Str::uuid();
                $pinjam_zoom->topic = $request->input('topic');
                $pinjam_zoom->password = $request->input('password');
                $pinjam_zoom->tipe = $request->input('tipe');

                $pinjam_zoom->tanggal = $request->input('tanggal');
                $pinjam_zoom->waktu_awal = $request->input('waktu_awal');
                $pinjam_zoom->waktu_akhir = $request->input('waktu_akhir');
                $pinjam_zoom->status = 'menunggu';
                $pinjam_zoom->pembuat_username = Auth::user()->username;

                if($have_zoom_peran){
                    $pinjam_zoom->status = 'disetujui';
                    $pinjam_zoom->confirm_username = Auth::user()->username;
                }
                $pinjam_zoom->save();
                array_push($return_data, $pinjam_zoom);

            }else if($request->input('tipe') == 'berulang'){
                $pinjam_zoom = new Pinjam_Zoom();
                $pinjam_zoom->id = Str::uuid();
                $pinjam_zoom->topic = $request->input('topic');
                $pinjam_zoom->password = $request->input('password');
                $pinjam_zoom->tipe = $request->input('tipe');

                $pinjam_zoom->berulang_tanggal_awal = $request->input('tanggal_awal');
                $pinjam_zoom->berulang_tanggal_akhir = $request->input('tanggal_akhir');
                $pinjam_zoom->waktu_awal = $request->input('waktu_awal');
                $pinjam_zoom->waktu_akhir = $request->input('waktu_akhir');
                $pinjam_zoom->status = 'menunggu';
                $pinjam_zoom->pembuat_username = Auth::user()->username;

                if($have_zoom_peran){
                    $pinjam_zoom->status = 'disetujui';
                    $pinjam_zoom->confirm_username = Auth::user()->username;
                }
                $pinjam_zoom->save();
                array_push($return_data, $pinjam_zoom);
            }

            if($have_zoom_peran ){
                $pinjam_zoom->zoom_status = $request->input('zoom_status');
                if($request->input('zoom_status') == 2 && in_array($request->input('zoom_akun'), ['statsulbar', 'sp2020'])){
                    $pinjam_zoom_mmet = $this->zoom_createMeeting($request->input('zoom_akun'), [
                        "agenda" => $request->input('topic'),
                        "topic" => $request->input('topic'),
                        "default_password" => false,
                        "password" => $request->input('password'),
                        "start_time" => $request->input('start_time'),
                        "timezone" => "Asia/Singapore",
                        "duration" => $request->input('duration'),
                        "type" => 2
                    ]);

                    if($pinjam_zoom_mmet['status']){
                        $pinjam_zoom->zoom_status = 2;
                        $pinjam_zoom->zoom_akun = $request->input('zoom_akun');
                        $pinjam_zoom->zoom_meeting_id = $pinjam_zoom_mmet['data']['id'];
                        $pinjam_zoom->zoom_start_url = $pinjam_zoom_mmet['data']['start_url'];
                        $pinjam_zoom->zoom_join_url =$pinjam_zoom_mmet['data']['join_url'];
                    }else{
                        throw ValidationException::withMessages([
                            'status' => 'Zoom tidak dapat dibuat',
                        ]);
                    }
                }
            }else{
                $pinjam_zoom->zoom_status = 0;
            }

            if(count($return_data) != 1){
                throw new Exception('Return Kosong');
            }
            DB::commit();
            return [
                "_status" => 'Berhasil membuat peminjaman mobil!',
                "data" => $return_data[0]
            ];
        }catch(Exception $ex){
            DB::rollBack();
            Log::error($ex);
            throw ValidationException::withMessages([
                '_status' => 'Gagal membuat peminjaman mobil!',
            ]);
        }
    }

    public function tambah(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'topic' => 'required|string',
            'password' => 'required|string',
            'start_time'=>'required|after_or_equal:'.Carbon::today()->format('Y-m-d'),
            'duration' => 'required|integer|max:1440',
            'start_time_penyesuaian' => 'required|integer',
            'zoom_status' => ['integer', Rule::in([0, 1, 2])],
            'zoom_akun' => ['string', Rule::in(['', 'statsulbar', 'sp2020'])]
        ]);

        $have_zoom_peran =  Peran::where('pemilik_username', Auth::user()->username)
                ->where('tipe_peran', 'global')
                ->where('peran', 'zoom')
                ->whereNull('tahun', null)
                ->first();

        $pinjam_zoom = new Pinjam_Zoom();
        $pinjam_zoom->id = Str::uuid();
        $pinjam_zoom->topic = $request->input('topic');
        $pinjam_zoom->password = $request->input('password');
        $pinjam_zoom->start_time = Carbon::parse($request->input('start_time'))
            ->addMinutes($request->input('start_time_penyesuaian')* -1)
            ->toDateTimeString();
        $pinjam_zoom->duration = $request->input('duration');
        $pinjam_zoom->end_time = Carbon::parse($request->input('start_time'))
            ->addMinutes($request->input('start_time_penyesuaian') * -1)
            ->addMinutes($request->input('duration'))
            ->toDateTimeString();
        $pinjam_zoom->pembuat_username = Auth::user()->username;
        $pinjam_zoom->is_recurrence = 0;
        $pinjam_zoom->recurrence_opt = null;

        throw ValidationException::withMessages([
            'status' => 'Zoom tidak dapat dibuat',
        ]);

        if($have_zoom_peran ){
            $pinjam_zoom->zoom_status = $request->input('zoom_status');
            if($request->input('zoom_status') == 2 && in_array($request->input('zoom_akun'), ['statsulbar', 'sp2020'])){
                $pinjam_zoom_mmet = $this->zoom_createMeeting($request->input('zoom_akun'), [
                    "agenda" => $request->input('topic'),
                    "topic" => $request->input('topic'),
                    "default_password" => false,
                    "password" => $request->input('password'),
                    "start_time" => $request->input('start_time'),
                    "timezone" => "Asia/Singapore",
                    "duration" => $request->input('duration'),
                    "type" => 2
                ]);

                if($pinjam_zoom_mmet['status']){
                    $pinjam_zoom->zoom_status = 2;
                    $pinjam_zoom->zoom_akun = $request->input('zoom_akun');
                    $pinjam_zoom->zoom_meeting_id = $pinjam_zoom_mmet['data']['id'];
                    $pinjam_zoom->zoom_start_url = $pinjam_zoom_mmet['data']['start_url'];
                    $pinjam_zoom->zoom_join_url =$pinjam_zoom_mmet['data']['join_url'];
                }else{
                    throw ValidationException::withMessages([
                        'status' => 'Zoom tidak dapat dibuat',
                    ]);
                }
            }
        }else{
            $pinjam_zoom->zoom_status = 0;
        }

        $pinjam_zoom->save();
        //redirect
        return redirect()->route('zoom-index');
    }

    public function ubah(Request $request, $pinjam_zoom_id)
    {
        $validated = $request->validate([
            'topic' => 'required|string',
            'password' => 'required|string',
            'start_time'=>'required|after_or_equal:'.Carbon::today()->format('Y-m-d'),
            'duration' => 'required|integer|max:1440',
            'start_time_penyesuaian' => 'required|integer',
            'zoom_status' => ['integer', Rule::in([0, 1, 2])],
            'zoom_akun' => ['string', Rule::in(['', 'statsulbar', 'sp2020'])]
        ]);

        $pinjam_zoom = Pinjam_Zoom::where('id', $pinjam_zoom_id)->first();
        if(!$pinjam_zoom){
            throw ValidationException::withMessages([
                'status' => 'Zoom tidak ditemukan',
            ]);
        }

        $have_zoom_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('tipe_peran', 'global')
            ->where('peran', 'zoom')
            ->whereNull('tahun', null)
            ->first();

        if(! ($have_zoom_peran || $pinjam_zoom->pembuat_username == Auth::user()->username) ){
            throw ValidationException::withMessages([
                'status' => 'Tidak memiliki akses',
            ]);
        }

        $pinjam_zoom->topic = $request->input('topic');
        $pinjam_zoom->password = $request->input('password');

        $start_time_awal = $pinjam_zoom->start_time;
        $duration_awal = $pinjam_zoom->duration;
        $pinjam_zoom_akun_awal = $pinjam_zoom->zoom_akun;

        $pinjam_zoom->start_time = Carbon::parse($request->input('start_time'))
            ->addMinutes($request->input('start_time_penyesuaian')* -1)
            ->toDateTimeString();
        $pinjam_zoom->duration = $request->input('duration');
        $pinjam_zoom->end_time = Carbon::parse($request->input('start_time'))
            ->addMinutes($request->input('start_time_penyesuaian') * -1)
            ->addMinutes($request->input('duration'))
            ->toDateTimeString();
        $pinjam_zoom->pembuat_username = Auth::user()->username;
        $pinjam_zoom->is_recurrence = 0;
        $pinjam_zoom->recurrence_opt = null;

        $pinjam_zoom->pembuat_username = Auth::user()->username;
        $pinjam_zoom->is_recurrence = 0;
        $pinjam_zoom->recurrence_opt = null;

        if( $request->input('zoom_status') == 0 || $request->input('zoom_status') == 1 ){
            if($pinjam_zoom->zoom_meeting_id == null){
                $pinjam_zoom->zoom_status = $request->input('zoom_status');
            }else{
                $pinjam_zoom_app = $this->zoom_deleteMeeting($pinjam_zoom->zoom_akun, "".$pinjam_zoom->zoom_meeting_id);
                throw ValidationException::withMessages([
                    'status' => 'Zoom gagal diubah',
                ]);
            }
        }

        if( $request->input('zoom_status') == 2 && $pinjam_zoom->zoom_meeting_id){
            if(! ($start_time_awal ==$pinjam_zoom->start_time
                && $duration_awal == $pinjam_zoom->duration
                && $pinjam_zoom_akun_awal == $request->input('zoom_akun')) )
            {
                if($have_zoom_peran && $pinjam_zoom_akun_awal == $request->input('zoom_akun')){
                    $pinjam_zoom_app = $this->zoom_updateMeeting($pinjam_zoom->zoom_akun, "".$pinjam_zoom->zoom_meeting_id,[
                            "agenda" => $request->input('topic'),
                            "topic" => $request->input('topic'),
                            "default_password" => false,
                            "password" => $request->input('password'),
                            "start_time" => $request->input('start_time'),
                            "timezone" => "Asia/Singapore",
                            "duration" => $request->input('duration'),
                            "type" => 2
                        ]);
                    if(!$pinjam_zoom_app['status']){
                        throw ValidationException::withMessages([
                            'status' => 'Zoom gagal diubah',
                        ]);
                    }
                }else if($have_zoom_peran && $pinjam_zoom_akun_awal != $request->input('zoom_akun')){
                    $pinjam_zoom_app = $this->zoom_deleteMeeting($pinjam_zoom->zoom_akun, "".$pinjam_zoom->zoom_meeting_id);
                    if(!$pinjam_zoom_app['status']){
                        throw ValidationException::withMessages([
                            'status' => 'Zoom gagal diubah',
                        ]);
                    }
                    $pinjam_zoom_mmet = $this->zoom_createMeeting($request->input('zoom_akun'), [
                        "agenda" => $request->input('topic'),
                        "topic" => $request->input('topic'),
                        "default_password" => false,
                        "password" => $request->input('password'),
                        "start_time" => $request->input('start_time'),
                        "timezone" => "Asia/Singapore",
                        "duration" => $request->input('duration'),
                        "type" => 2
                    ]);

                    if($pinjam_zoom_mmet['status']){
                        $pinjam_zoom->zoom_akun = $request->input('zoom_akun');
                        $pinjam_zoom->zoom_meeting_id = $pinjam_zoom_mmet['data']['id'];
                        $pinjam_zoom->zoom_start_url = $pinjam_zoom_mmet['data']['start_url'];
                        $pinjam_zoom->zoom_join_url =$pinjam_zoom_mmet['data']['join_url'];
                    }else{
                        throw ValidationException::withMessages([
                            'status' => 'Zoom gagal diubah',
                        ]);
                    }
                }else{
                    $pinjam_zoom_app = $this->zoom_deleteMeeting($pinjam_zoom->zoom_akun, "".$pinjam_zoom->zoom_meeting_id);
                    throw ValidationException::withMessages([
                        'status' => 'Zoom gagal diubah',
                    ]);

                    $pinjam_zoom->zoom_status = 0;
                    $pinjam_zoom->zoom_akun = null;
                    $pinjam_zoom->zoom_meeting_id = null;
                    $pinjam_zoom->zoom_start_url = null;
                    $pinjam_zoom->zoom_join_url = null;
                }
            }else{
                 $pinjam_zoom_app = $this->zoom_updateMeeting($pinjam_zoom->zoom_akun, "".$pinjam_zoom->zoom_meeting_id,[
                        "agenda" => $request->input('topic'),
                        "topic" => $request->input('topic'),
                        "default_password" => false,
                        "password" => $request->input('password'),
                        "start_time" => $request->input('start_time'),
                        "timezone" => "Asia/Singapore",
                        "duration" => $request->input('duration'),
                        "type" => 2
                    ]);
                if(!$pinjam_zoom_app['status']){
                    throw ValidationException::withMessages([
                        'status' => 'Zoom gagal diubah',
                    ]);
                }
            }
        }

        $pinjam_zoom->save();
        redirect()->route('zoom-index');
    }

    public function hapus(Request $request, $pinjam_zoom_id)
    {
        $pinjam_zoom = Pinjam_Zoom::where('id', $pinjam_zoom_id)->first();
        if(!$pinjam_zoom){
            return abort(404);
        }

        $have_zoom_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('tipe_peran', 'global')
            ->where('peran', 'zoom')
            ->whereNull('tahun', null)
            ->first();

        if(! ($have_zoom_peran || $pinjam_zoom->pembuat_username == Auth::user()->username) ){
            throw ValidationException::withMessages([
                'status' => 'Tidak memiliki akses',
            ]);
        }

        if($pinjam_zoom->zoom_meeting_id){
            $pinjam_zoom_app = $this->zoom_deleteMeeting($pinjam_zoom->zoom_akun, "".$pinjam_zoom->zoom_meeting_id);
            if(!$pinjam_zoom_app['status']){
                throw ValidationException::withMessages([
                    'status' => 'Zoom gagal dihapus',
                ]);
            }
        }

        $pinjam_zoom->delete();

        //redirect
        redirect()->route('zoom-index');
    }

    public function approval(Request $request, $pinjam_zoom_id)
    {
        $validated = $request->validate([
            'zoom_status' => ['integer', Rule::in([0, 1, 2])],
            'zoom_akun' => ['string', Rule::in(['', 'statsulbar', 'sp2020'])]
        ]);

        $have_zoom_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('tipe_peran', 'global')
            ->where('peran', 'zoom')
            ->whereNull('tahun', null)
            ->first();

        if(! ($have_zoom_peran) ){
            return abort(401, 'Tidak memiliki akses');
        }

        $pinjam_zoom = Pinjam_Zoom::where('id', $pinjam_zoom_id)->first();

        if($request->input('zoom_status') == '1'){
            if($pinjam_zoom->zoom_meeting_id){
                $pinjam_zoom_app = $this->zoom_deleteMeeting($pinjam_zoom->zoom_akun, "".$pinjam_zoom->zoom_meeting_id);
                if(!$pinjam_zoom_app['status']){
                    throw ValidationException::withMessages([
                        'status' => 'Zoom gagal dibuat',
                    ]);
                }

                $pinjam_zoom->zoom_status = 1;
                $pinjam_zoom->zoom_akun = null;
                $pinjam_zoom->zoom_meeting_id = null;
                $pinjam_zoom->zoom_start_url = null;
                $pinjam_zoom->zoom_join_url = null;
                $pinjam_zoom->save();
            }
        }else if($request->input('zoom_status') == '2'){
            if(!$pinjam_zoom->zoom_meeting_id){
                 $pinjam_zoom_mmet = $this->zoom_createMeeting($request->input('zoom_akun'), [
                    "agenda" => $pinjam_zoom->topic,
                    "topic" => $pinjam_zoom->topic,
                    "default_password" => false,
                    "password" => $pinjam_zoom->password,
                    "start_time" => $pinjam_zoom->start_time,
                    "timezone" => "Asia/Singapore",
                    "duration" => $pinjam_zoom->duration,
                    "type" => 2
                ]);

                if($pinjam_zoom_mmet['status']){
                    $pinjam_zoom->zoom_status = 2;
                    $pinjam_zoom->zoom_akun = $request->input('zoom_akun');
                    $pinjam_zoom->zoom_meeting_id = $pinjam_zoom_mmet['data']['id'];
                    $pinjam_zoom->zoom_start_url = $pinjam_zoom_mmet['data']['start_url'];
                    $pinjam_zoom->zoom_join_url =$pinjam_zoom_mmet['data']['join_url'];
                    $pinjam_zoom->save();
                }else{
                    throw ValidationException::withMessages([
                        'status' => 'Zoom tidak dapat dibuat',
                    ]);
                }
            }else {
               if($pinjam_zoom->zoom_akun != $request->input('zoom_akun')){
                    $pinjam_zoom_app = $this->zoom_deleteMeeting($pinjam_zoom->zoom_akun, "".$pinjam_zoom->zoom_meeting_id);
                    if(!$pinjam_zoom_app['status']){
                        throw ValidationException::withMessages([
                            'status' => 'Zoom gagal diubah',
                        ]);
                    }
                    $pinjam_zoom_mmet = $this->zoom_createMeeting($request->input('zoom_akun'), [
                        "agenda" => $request->input('topic'),
                        "topic" => $request->input('topic'),
                        "default_password" => false,
                        "password" => $request->input('password'),
                        "start_time" => $request->input('start_time'),
                        "timezone" => "Asia/Singapore",
                        "duration" => $request->input('duration'),
                        "type" => 2
                    ]);

                    if($pinjam_zoom_mmet['status']){
                        $pinjam_zoom->zoom_status = 2;
                        $pinjam_zoom->zoom_akun = $request->input('zoom_akun');
                        $pinjam_zoom->zoom_meeting_id = $pinjam_zoom_mmet['data']['id'];
                        $pinjam_zoom->zoom_start_url = $pinjam_zoom_mmet['data']['start_url'];
                        $pinjam_zoom->zoom_join_url =$pinjam_zoom_mmet['data']['join_url'];
                        $pinjam_zoom->save();
                    }else{
                        throw ValidationException::withMessages([
                            'status' => 'Zoom gagal diubah',
                        ]);
                    }
                }
            }
        }

        //redirect
        redirect()->route('zoom-index');
    }

    // ------------------ ZOOM API ------------------

    protected function zoom_getAccessToken($path)
    {
        $client = new Client([
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->zoom_config[$path]['client_id'] . ':' . $this->zoom_config[$path]['client_secret']),
                'Host' => 'zoom.us',
            ],
        ]);

        $response = $client->request('POST', "https://zoom.us/oauth/token", [
            'form_params' => [
                'grant_type' => 'account_credentials',
                'account_id' =>$this->zoom_config[$path]['account_id'],
            ],
        ]);

        $responseBody = json_decode($response->getBody(), true);
        return $responseBody['access_token'];
    }

     protected function zoom_getClient($path)
    {
        $accessToken = $this->zoom_getAccessToken($path);

        $client = new Client([
            'base_uri' => 'https://api.zoom.us/v2/',
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ],
        ]);

        return $client;
    }

    public function zoom_getAllMeeting($path, $from, $to)
    {
        try {
            $client = $this->zoom_getClient($path);
            $response = $client->request('GET', 'users/me/meetings',
                ['query' => [
                    'page_size' => 300,
                    'from' => $from,
                    'to' => $to,
                    'timezone', 'Asia/Singapore',
                ]
            ]);
            $data = json_decode($response->getBody(), true);
            return [
                'status' => true,
                'data' => $data,
            ];
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => $th->getMessage(),
            ];
        }
    }

    public function zoom_createMeeting($path, array $data)
    {
        try {
            $client = $this->zoom_getClient($path);
            $response = $client->request('GET', 'users/me/meetings',
                ['json' => $data]
            );
            $res = json_decode($response->getBody(), true);
            return [
                'status' => true,
                'data' => $res,
            ];
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => $th->getMessage(),
            ];
        }
    }

     // update meeting
    public function zoom_updateMeeting(string $path, string $meetingId, array $data)
    {
        try {
            $client = $this->zoom_getClient($path);
            $response = $client->request('PATCH', 'meetings/' . $meetingId, [
                'json' => $data,
            ]);
            $res = json_decode($response->getBody(), true);
            return [
                'status' => true,
                'data' => $res,
            ];
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => $th->getMessage(),
            ];
        }
    }

    // delete meeting
    public function zoom_deleteMeeting(string $path, string $meetingId)
    {
        try {
            $client = $this->zoom_getClient($path);
            $response = $client->request('DELETE', 'meetings/' . $meetingId);
            if ($response->getStatusCode() === 204) {
                return [
                    'status' => true,
                    'message' => 'Meeting Deleted Successfully',
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Something went wrong',
                ];
            }
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => $th->getMessage(),
            ];
        }

    }
}

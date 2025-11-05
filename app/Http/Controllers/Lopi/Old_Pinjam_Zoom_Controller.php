<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Model\Lopi\Pinjam_Zoom;
use App\Model\Umum\Peran;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

use GuzzleHttp\Client;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class Pinjam_Zoom_Controller extends Controller
{

    private $zoom_config ;

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
        $month =  Carbon::today()->month;
        if($request->has('bulan')){
            $month = $request->input('bulan');
        }
        $today_str = Carbon::today()->format('Y-m-d');

        $date = Carbon::create(session('tahun_active'), $month, 1);
        $month_start = Carbon::create(session('tahun_active'), $month, 1)->startOfMonth();
        $month_end =  Carbon::create(session('tahun_active'), $month, 1)->endOfMonth();

        $zoom_bulan_daftar = Pinjam_Zoom::with(['pembuat'])->whereMonth("start_time", $month)->whereYear("start_time", $date->year)->get();
        for($i=0; $i<count($zoom_bulan_daftar); $i++){
            if($zoom_bulan_daftar[$i]->pembuat){
                $zoom_bulan_daftar[$i]->pembuat_nama = $zoom_bulan_daftar[$i]->pembuat->nama;
            }
            if(Carbon::parse($zoom_bulan_daftar[$i]->start_time)->format('Y-m-d') != $today_str){
                $zoom_bulan_daftar[$i]->zoom_start_url = null;
            }
        }
        // $zoom_statsulbar = $this->zoom_getAllMeeting('statsulbar', $month_start->format('Y-m-d'), $month_end->format('Y-m-d'));
        // $zoom_sp2020 = $this->zoom_getAllMeeting('sp2020', $month_start->format('Y-m-d'), $month_end->format('Y-m-d'));
        $zoom_statsulbar = ['status' => false];
        $zoom_sp2020 = ['status' => false];

        return Inertia::render('zoom/index', [
            "bulan"=>$month,
            "zoom_bulan_daftar" => $zoom_bulan_daftar,
            "zoom_statsulbar" => $zoom_statsulbar,
            "zoom_sp2020" => $zoom_sp2020,
        ]);
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

        $zoom = new Pinjam_Zoom();
        $zoom->id = Str::uuid();
        $zoom->topic = $request->input('topic');
        $zoom->password = $request->input('password');
        $zoom->start_time = Carbon::parse($request->input('start_time'))
            ->addMinutes($request->input('start_time_penyesuaian')* -1)
            ->toDateTimeString();
        $zoom->duration = $request->input('duration');
        $zoom->end_time = Carbon::parse($request->input('start_time'))
            ->addMinutes($request->input('start_time_penyesuaian') * -1)
            ->addMinutes($request->input('duration'))
            ->toDateTimeString();
        $zoom->pembuat_username = Auth::user()->username;
        $zoom->is_recurrence = 0;
        $zoom->recurrence_opt = null;

        throw ValidationException::withMessages([
            'status' => 'Zoom tidak dapat dibuat',
        ]);

        if($have_zoom_peran ){
            $zoom->zoom_status = $request->input('zoom_status');
            if($request->input('zoom_status') == 2 && in_array($request->input('zoom_akun'), ['statsulbar', 'sp2020'])){
                $zoom_mmet = $this->zoom_createMeeting($request->input('zoom_akun'), [
                    "agenda" => $request->input('topic'),
                    "topic" => $request->input('topic'),
                    "default_password" => false,
                    "password" => $request->input('password'),
                    "start_time" => $request->input('start_time'),
                    "timezone" => "Asia/Singapore",
                    "duration" => $request->input('duration'),
                    "type" => 2
                ]);

                if($zoom_mmet['status']){
                    $zoom->zoom_status = 2;
                    $zoom->zoom_akun = $request->input('zoom_akun');
                    $zoom->zoom_meeting_id = $zoom_mmet['data']['id'];
                    $zoom->zoom_start_url = $zoom_mmet['data']['start_url'];
                    $zoom->zoom_join_url =$zoom_mmet['data']['join_url'];
                }else{
                    throw ValidationException::withMessages([
                        'status' => 'Zoom tidak dapat dibuat',
                    ]);
                }
            }
        }else{
            $zoom->zoom_status = 0;
        }

        $zoom->save();
        //redirect
        return redirect()->route('zoom-index');
    }

    public function ubah(Request $request, $zoom_id)
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

        $zoom = Pinjam_Zoom::where('id', $zoom_id)->first();
        if(!$zoom){
            throw ValidationException::withMessages([
                'status' => 'Zoom tidak ditemukan',
            ]);
        }

        $have_zoom_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('tipe_peran', 'global')
            ->where('peran', 'zoom')
            ->whereNull('tahun', null)
            ->first();

        if(! ($have_zoom_peran || $zoom->pembuat_username == Auth::user()->username) ){
            throw ValidationException::withMessages([
                'status' => 'Tidak memiliki akses',
            ]);
        }

        $zoom->topic = $request->input('topic');
        $zoom->password = $request->input('password');

        $start_time_awal = $zoom->start_time;
        $duration_awal = $zoom->duration;
        $zoom_akun_awal = $zoom->zoom_akun;

        $zoom->start_time = Carbon::parse($request->input('start_time'))
            ->addMinutes($request->input('start_time_penyesuaian')* -1)
            ->toDateTimeString();
        $zoom->duration = $request->input('duration');
        $zoom->end_time = Carbon::parse($request->input('start_time'))
            ->addMinutes($request->input('start_time_penyesuaian') * -1)
            ->addMinutes($request->input('duration'))
            ->toDateTimeString();
        $zoom->pembuat_username = Auth::user()->username;
        $zoom->is_recurrence = 0;
        $zoom->recurrence_opt = null;

        $zoom->pembuat_username = Auth::user()->username;
        $zoom->is_recurrence = 0;
        $zoom->recurrence_opt = null;

        if( $request->input('zoom_status') == 0 || $request->input('zoom_status') == 1 ){
            if($zoom->zoom_meeting_id == null){
                $zoom->zoom_status = $request->input('zoom_status');
            }else{
                $zoom_app = $this->zoom_deleteMeeting($zoom->zoom_akun, "".$zoom->zoom_meeting_id);
                throw ValidationException::withMessages([
                    'status' => 'Zoom gagal diubah',
                ]);
            }
        }

        if( $request->input('zoom_status') == 2 && $zoom->zoom_meeting_id){
            if(! ($start_time_awal ==$zoom->start_time
                && $duration_awal == $zoom->duration
                && $zoom_akun_awal == $request->input('zoom_akun')) )
            {
                if($have_zoom_peran && $zoom_akun_awal == $request->input('zoom_akun')){
                    $zoom_app = $this->zoom_updateMeeting($zoom->zoom_akun, "".$zoom->zoom_meeting_id,[
                            "agenda" => $request->input('topic'),
                            "topic" => $request->input('topic'),
                            "default_password" => false,
                            "password" => $request->input('password'),
                            "start_time" => $request->input('start_time'),
                            "timezone" => "Asia/Singapore",
                            "duration" => $request->input('duration'),
                            "type" => 2
                        ]);
                    if(!$zoom_app['status']){
                        throw ValidationException::withMessages([
                            'status' => 'Zoom gagal diubah',
                        ]);
                    }
                }else if($have_zoom_peran && $zoom_akun_awal != $request->input('zoom_akun')){
                    $zoom_app = $this->zoom_deleteMeeting($zoom->zoom_akun, "".$zoom->zoom_meeting_id);
                    if(!$zoom_app['status']){
                        throw ValidationException::withMessages([
                            'status' => 'Zoom gagal diubah',
                        ]);
                    }
                    $zoom_mmet = $this->zoom_createMeeting($request->input('zoom_akun'), [
                        "agenda" => $request->input('topic'),
                        "topic" => $request->input('topic'),
                        "default_password" => false,
                        "password" => $request->input('password'),
                        "start_time" => $request->input('start_time'),
                        "timezone" => "Asia/Singapore",
                        "duration" => $request->input('duration'),
                        "type" => 2
                    ]);

                    if($zoom_mmet['status']){
                        $zoom->zoom_akun = $request->input('zoom_akun');
                        $zoom->zoom_meeting_id = $zoom_mmet['data']['id'];
                        $zoom->zoom_start_url = $zoom_mmet['data']['start_url'];
                        $zoom->zoom_join_url =$zoom_mmet['data']['join_url'];
                    }else{
                        throw ValidationException::withMessages([
                            'status' => 'Zoom gagal diubah',
                        ]);
                    }
                }else{
                    $zoom_app = $this->zoom_deleteMeeting($zoom->zoom_akun, "".$zoom->zoom_meeting_id);
                    throw ValidationException::withMessages([
                        'status' => 'Zoom gagal diubah',
                    ]);

                    $zoom->zoom_status = 0;
                    $zoom->zoom_akun = null;
                    $zoom->zoom_meeting_id = null;
                    $zoom->zoom_start_url = null;
                    $zoom->zoom_join_url = null;
                }
            }else{
                 $zoom_app = $this->zoom_updateMeeting($zoom->zoom_akun, "".$zoom->zoom_meeting_id,[
                        "agenda" => $request->input('topic'),
                        "topic" => $request->input('topic'),
                        "default_password" => false,
                        "password" => $request->input('password'),
                        "start_time" => $request->input('start_time'),
                        "timezone" => "Asia/Singapore",
                        "duration" => $request->input('duration'),
                        "type" => 2
                    ]);
                if(!$zoom_app['status']){
                    throw ValidationException::withMessages([
                        'status' => 'Zoom gagal diubah',
                    ]);
                }
            }
        }

        $zoom->save();
        redirect()->route('zoom-index');
    }

    public function hapus(Request $request, $zoom_id)
    {
        $zoom = Pinjam_Zoom::where('id', $zoom_id)->first();
        if(!$zoom){
            return abort(404);
        }

        $have_zoom_peran =  Peran::where('pemilik_username', Auth::user()->username)
            ->where('tipe_peran', 'global')
            ->where('peran', 'zoom')
            ->whereNull('tahun', null)
            ->first();

        if(! ($have_zoom_peran || $zoom->pembuat_username == Auth::user()->username) ){
            throw ValidationException::withMessages([
                'status' => 'Tidak memiliki akses',
            ]);
        }

        if($zoom->zoom_meeting_id){
            $zoom_app = $this->zoom_deleteMeeting($zoom->zoom_akun, "".$zoom->zoom_meeting_id);
            if(!$zoom_app['status']){
                throw ValidationException::withMessages([
                    'status' => 'Zoom gagal dihapus',
                ]);
            }
        }

        $zoom->delete();

        //redirect
        redirect()->route('zoom-index');
    }

    public function approval(Request $request, $zoom_id)
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

        $zoom = Pinjam_Zoom::where('id', $zoom_id)->first();

        if($request->input('zoom_status') == '1'){
            if($zoom->zoom_meeting_id){
                $zoom_app = $this->zoom_deleteMeeting($zoom->zoom_akun, "".$zoom->zoom_meeting_id);
                if(!$zoom_app['status']){
                    throw ValidationException::withMessages([
                        'status' => 'Zoom gagal dibuat',
                    ]);
                }

                $zoom->zoom_status = 1;
                $zoom->zoom_akun = null;
                $zoom->zoom_meeting_id = null;
                $zoom->zoom_start_url = null;
                $zoom->zoom_join_url = null;
                $zoom->save();
            }
        }else if($request->input('zoom_status') == '2'){
            if(!$zoom->zoom_meeting_id){
                 $zoom_mmet = $this->zoom_createMeeting($request->input('zoom_akun'), [
                    "agenda" => $zoom->topic,
                    "topic" => $zoom->topic,
                    "default_password" => false,
                    "password" => $zoom->password,
                    "start_time" => $zoom->start_time,
                    "timezone" => "Asia/Singapore",
                    "duration" => $zoom->duration,
                    "type" => 2
                ]);

                if($zoom_mmet['status']){
                    $zoom->zoom_status = 2;
                    $zoom->zoom_akun = $request->input('zoom_akun');
                    $zoom->zoom_meeting_id = $zoom_mmet['data']['id'];
                    $zoom->zoom_start_url = $zoom_mmet['data']['start_url'];
                    $zoom->zoom_join_url =$zoom_mmet['data']['join_url'];
                    $zoom->save();
                }else{
                    throw ValidationException::withMessages([
                        'status' => 'Zoom tidak dapat dibuat',
                    ]);
                }
            }else {
               if($zoom->zoom_akun != $request->input('zoom_akun')){
                    $zoom_app = $this->zoom_deleteMeeting($zoom->zoom_akun, "".$zoom->zoom_meeting_id);
                    if(!$zoom_app['status']){
                        throw ValidationException::withMessages([
                            'status' => 'Zoom gagal diubah',
                        ]);
                    }
                    $zoom_mmet = $this->zoom_createMeeting($request->input('zoom_akun'), [
                        "agenda" => $request->input('topic'),
                        "topic" => $request->input('topic'),
                        "default_password" => false,
                        "password" => $request->input('password'),
                        "start_time" => $request->input('start_time'),
                        "timezone" => "Asia/Singapore",
                        "duration" => $request->input('duration'),
                        "type" => 2
                    ]);

                    if($zoom_mmet['status']){
                        $zoom->zoom_status = 2;
                        $zoom->zoom_akun = $request->input('zoom_akun');
                        $zoom->zoom_meeting_id = $zoom_mmet['data']['id'];
                        $zoom->zoom_start_url = $zoom_mmet['data']['start_url'];
                        $zoom->zoom_join_url =$zoom_mmet['data']['join_url'];
                        $zoom->save();
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

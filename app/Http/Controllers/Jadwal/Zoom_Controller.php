<?php

namespace App\Http\Controllers\Jadwal;

use App\Http\Controllers\Controller;
use App\Model\Jadwal\Jadwal;
use App\Model\Jadwal\Zoom;
use App\Model\Umum\Peran;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Str;

class Zoom_Controller extends Controller
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

        $date = Carbon::create(session('tahun_active'), $month, 1);
        $month_start = Carbon::create(session('tahun_active'), $month, 1)->startOfMonth();
        $month_end =  Carbon::create(session('tahun_active'), $month, 1)->endOfMonth();

        $zoom_bulan_daftar = Zoom::with(['pembuat'])->whereMonth("start_time", $month)->whereYear("start_time", $date->year)->get();
        for($i=0; $i<count($zoom_bulan_daftar); $i++){
            if($zoom_bulan_daftar[$i]->pembuat){
                $zoom_bulan_daftar[$i]->pembuat_nama = $zoom_bulan_daftar[$i]->pembuat->nama;
            }
        }
        $zoom_statsulbar = $this->zoom_getAllMeeting('statsulbar', $month_start->format('Y-m-d'), $month_end->format('Y-m-d'));
        $zoom_sp2020 = $this->zoom_getAllMeeting('sp2020', $month_start->format('Y-m-d'), $month_end->format('Y-m-d'));
        // $zoom_statsulbar = ['status' => false];
        // $zoom_sp2020 = ['status' => false];

        return Inertia::render('zoom/index', [
            "bulan"=>$month,
            "peran"=> fn () => Peran::get_my_peran(),
            "zoom_bulan_daftar" => $zoom_bulan_daftar,
            "zoom_statsulbar" => $zoom_statsulbar,
            "zoom_sp2020" => $zoom_sp2020,
        ]);
    }

    public function tambah(Request $request)
    {
        $zoom = new Zoom();
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
        $zoom->save();
        //redirect
        redirect('zoom');
    }

    public function ubah(Request $request)
    {
        $zoom = Zoom::where('id', $request->input('jadwal_id'))->first();
        if(!$zoom){
            return abort(404);
        }
        $zoom->agenda = $request->input('agenda');
        $zoom->topic = $request->input('topic');
        $zoom->password = $request->input('password');
        $zoom->start_time = $request->input('start_time');
        $zoom->duration = $request->input('duration');
        $zoom->pembuat_username = Auth::user()->username;
        $zoom->is_recurrence = $request->input('is_recurrence');
        $zoom->recurrence_opt = $request->input('recurrence_opt');
        $zoom->save();
        return 1;
    }

    public function hapus(Request $request)
    {
        $zoom = Zoom::where('id', $request->input('jadwal_id'))->first();
        if(!$zoom){
            return abort(404);
        }
        $zoom->delete();

        //redirect
        return 1;
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

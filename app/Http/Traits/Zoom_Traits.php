<?php

namespace App\Http\Traits;

use App\Model\Lopi\Pinjam_Zoom;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

trait Zoom_Traits
{
    private $zoom_config;

    public function define_config()
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
        $data1 = [];
        $data2 = [];

        $status = true;
        if(Carbon::today() < Carbon::parse($from)  && Carbon::today() < Carbon::parse($to)){
            $get1 = $this->zoom_getAllMeeting_helper($path, $from, $to, 'previous_meetings');
            $data1 = $get1['data'];
            $status = $status && $get1['status'];
        }else if(Carbon::parse($from) < Carbon::today() && Carbon::parse($to) < Carbon::today() ){
            $get2 = $this->zoom_getAllMeeting_helper($path, $from, $to, 'upcoming_meetings');
            $data2 = $get2['data'];
            $status = $status && $get2['status'];
        }else {
            $get1 = $this->zoom_getAllMeeting_helper($path, $from, $to, 'previous_meetings');
            $data1 = $get1['data'];
            $status = $status && $get1['status'];

            $get2 = $this->zoom_getAllMeeting_helper($path, $from, $to, 'upcoming_meetings');
            $data2 = $get2['data'];
            $status = $status && $get2['status'];
        }

        $return_data = [];
        foreach($data1 as $item){
            array_push($return_data, $item);
        }
        foreach($data2 as $item){
            array_push($return_data, $item);
        }

        return [
            'status' => $status,
            'data' => $return_data,
        ];
    }

     public function zoom_getAllMeeting_helper($path, $from, $to, $type)
    {
        try {
            $client = $this->zoom_getClient($path);
            $response = $client->request('GET', 'users/me/meetings',
                ['query' => [
                    'type' => $type,
                    'page_size' => 300,
                    'from' => $from,
                    'to' => $to,
                    'timezone' => 'Asia/Singapore',
                ]
            ]);
            $data = json_decode($response->getBody(), true);

            for($i=0; $i < count($data['meetings']); $i++){
                $data['meetings'][$i]['start_time'] = Carbon::parse($data['meetings'][$i]['start_time'])->timezone($data['meetings'][$i]['timezone'])->format('Y-m-d H:i:s');
            }

            $return_data = [
                'status' => true,
                'data' => $data['meetings'],
            ];

            Cache::put($path.'--'.$from.'--'.$to.'--'.$type, $return_data, now()->addHour());
            return $return_data;
        } catch (\Throwable $th) {
            Log::alert($th);

            if (Cache::has($path.'--'.$from.'--'.$to.'--'.$type)) {
                return  Cache::get($path.'--'.$from.'--'.$to.'--'.$type);
            }else{
                return [
                    'status' => false,
                    'data' => [],
                    'message' => $th->getMessage(),
                ];
            }
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

    public function zoom_meta_createMeeting($path, $instance)
    {
        if($instance instanceof Pinjam_Zoom){
            if($instance->tipe == 'sekali'){
                $zoom_mmet = $this->zoom_createMeeting($path, [
                    "agenda" => $instance->topic,
                    "topic" => $instance->topic,
                    "default_password" => false,
                    "password" => $instance->password,
                    "start_time" => $instance->tanggal. " " .$instance->waktu_awal,
                    "timezone" => "Asia/Singapore",
                    "duration" => Carbon::parse($instance->tanggal. " " .$instance->waktu_awal)->diffInMinutes($instance->tanggal. " " .$instance->waktu_akhir),
                    "type" => 2
                ]);

                if($zoom_mmet['status']){
                    $instance->zoom_akun = $path;
                    $instance->zoom_meeting_id = $zoom_mmet['data']['id'];
                    $instance->zoom_start_url = $zoom_mmet['data']['start_url'];
                    $instance->zoom_join_url =$zoom_mmet['data']['join_url'];
                }else{
                    throw ValidationException::withMessages([
                        'status' => 'Zoom tidak dapat dibuat',
                    ]);
                }
            }else if($instance->tipe == 'berulang'){
                 $zoom_mmet = $this->zoom_createMeeting($path, [
                    "agenda" => $instance->topic,
                    "topic" => $instance->topic,
                    "default_password" => false,
                    "password" => $instance->password,
                    "start_time" => $instance->berulang_tanggal_awal. " " .$instance->waktu_awal,
                    "timezone" => "Asia/Singapore",
                    "duration" => Carbon::parse($instance->berulang_tanggal_awal. " " .$instance->waktu_awal)->diffInMinutes($instance->berulang_tanggal_awal. " " .$instance->waktu_akhir),
                    "type" => 8,
                    "recurrence" => [
                        "type" => 1,
                        "repeat_interval" => 1,
                        "end_date_time" => $instance->berulang_tanggal_akhir. " " .$instance->waktu_akhir
                    ]
                ]);

                if($zoom_mmet['status']){
                    $instance->zoom_akun = $path;
                    $instance->zoom_meeting_id = $zoom_mmet['data']['id'];
                    $instance->zoom_start_url = $zoom_mmet['data']['start_url'];
                    $instance->zoom_join_url =$zoom_mmet['data']['join_url'];
                }else{
                    throw ValidationException::withMessages([
                        'status' => 'Zoom tidak dapat dibuat',
                    ]);
                }
            }

        }
    }

    public function zoom_meta_updateMeeting($path, $instance)
    {
        if($instance instanceof Pinjam_Zoom){
            if($instance->tipe == 'sekali'){
                $zoom_mmet = $this->zoom_updateMeeting($path, "".$instance->zoom_meeting_id ,[
                    "agenda" => $instance->topic,
                    "topic" => $instance->topic,
                    "default_password" => false,
                    "password" => $instance->password,
                    "start_time" => $instance->tanggal. " " .$instance->waktu_awal,
                    "timezone" => "Asia/Singapore",
                    "duration" => Carbon::parse($instance->tanggal. " " .$instance->waktu_awal)->diffInMinutes($instance->tanggal. " " .$instance->waktu_akhir),
                    "type" => 2
                ]);

                if($zoom_mmet['status']){
                    $instance->zoom_akun = $path;
                    $instance->zoom_meeting_id = $zoom_mmet['data']['id'];
                    $instance->zoom_start_url = $zoom_mmet['data']['start_url'];
                    $instance->zoom_join_url =$zoom_mmet['data']['join_url'];
                }else{
                    throw ValidationException::withMessages([
                        'status' => 'Zoom tidak dapat dibuat',
                    ]);
                }
            }else if($instance->tipe == 'berulang'){
                $zoom_mmet = $this->zoom_updateMeeting($path, "".$instance->zoom_meeting_id ,[
                    "agenda" => $instance->topic,
                    "topic" => $instance->topic,
                    "default_password" => false,
                    "password" => $instance->password,
                    "start_time" => $instance->berulang_tanggal_awal. " " .$instance->waktu_awal,
                    "timezone" => "Asia/Singapore",
                    "duration" => Carbon::parse($instance->berulang_tanggal_awal. " " .$instance->waktu_awal)->diffInMinutes($instance->berulang_tanggal_awal. " " .$instance->waktu_akhir),
                    "type" => 8,
                    "recurrence" => [
                        "type" => 1,
                        "repeat_interval" => 1,
                        "end_date_time" => $instance->berulang_tanggal_akhir. " " .$instance->waktu_akhir
                    ]
                ]);

                if($zoom_mmet['status']){
                    $instance->zoom_akun = $path;
                    $instance->zoom_meeting_id = $zoom_mmet['data']['id'];
                    $instance->zoom_start_url = $zoom_mmet['data']['start_url'];
                    $instance->zoom_join_url =$zoom_mmet['data']['join_url'];
                }else{
                    throw ValidationException::withMessages([
                        'status' => 'Zoom tidak dapat dibuat',
                    ]);
                }
            }

        }
    }
}

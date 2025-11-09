<?php

namespace App\Http\Traits;

use GuzzleHttp\Client;

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

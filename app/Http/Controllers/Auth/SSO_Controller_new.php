<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Model\Umum\User;
use App\Services\Google_Event_Local_Service;
use Exception;

// Keycloack baru untuk SSO Baru
use App\Services\SSO\KeycloakNew;

// yang lama ga dipake lagi
// use JKD\SSO\Client\Provider\Keycloak;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SSO_Controller_new extends Controller
{
    private $provider;

    public function __construct(protected Google_Event_Local_Service $service)
    {
        $this->provider = new KeycloakNew([
            'authServerUrl' => env('SSO_Url_new'),
            'realm'         => env('SSO_Realm_new'),
            'clientId'      => env('Client_Id_new'),
            'clientSecret'  => env('Client_Secret_new'),
            'redirectUri'   => env('Client_URL_new'),
        ]);
    }

    private function logSsoException(string $stage, Exception $e): void
    {
        $context = [
            'stage'       => $stage,
            'exception'   => get_class($e),
            'message'     => $e->getMessage(),
            'auth_server' => env('SSO_Url_new'),
            'realm'       => env('SSO_Realm_new'),
            'client_id'   => env('Client_Id_new'),
            'redirect_uri' => env('Client_URL_new'),
        ];

        if (method_exists($e, 'getResponseBody')) {
            $body = $e->getResponseBody();
            $context['response_body'] = Str::limit(
                is_string($body) ? $body : json_encode($body),
                1000
            );
        } elseif (method_exists($e, 'getResponse')) {
            $response = $e->getResponse();

            if ($response) {
                $context['status'] = $response->getStatusCode();
                $context['response_body'] = Str::limit((string) $response->getBody(), 1000);
            }
        }

        Log::error("SSO {$stage} gagal", $context);
    }

    public function showLoginForm(): Response
    {
         return Inertia::render('auth/login_sso', [
            "tahun_array" => explode(",", env("TAHUN_ARRAY"))
            , "tahun_default" => env("TAHUN_DEFAULT")
        ]);
    }

    public function sso(Request $request)
    {
        Log::info('SSO callback', [
            'has_code'      => $request->has('code'),
            'state_request' => $request->get('state'),
            'state_session' => $request->session()->get('oauth2state'),
        ]);
        if(Auth::check()){
            // Log::info('auth');
            return redirect('/');
        }
        if (!$request->input('code')) {
            if($request->tahun){
                $request->session()->put('tahun_active', $request->tahun);
            }else{
                Log::warning('SSO: tahun belum di set', [
                    'query' => $request->query(),
                    'all'   => $request->all(),
                ]);
                return redirect('/login')->withErrors(['tahun' => 'Tahun belum di set']);
            }

            // Untuk mendapatkan authorization code
            $authUrl = $this->provider->getAuthorizationUrl();
            $request->session()->put('oauth2state', $this->provider->getState());
            return Inertia::location($authUrl);

        }  else if (!$request->has('state') || ($request->get('state') !== $request->session()->get('oauth2state'))) {
            log::warning('SSO: state tidak valid', [
                'query' => $request->query(),
                'all'   => $request->all(),
            ]);
            $request->session()->forget('oauth2state');
            return redirect('/login')->withErrors(['user' => 'Terjadi kesalahan']);

        } else {
            Log::info('123123');
            try {
                $token = $this->provider->getAccessToken('authorization_code', [
                    'code' => $request->get('code')
                ]);
            } catch (Exception $e) {
                $this->logSsoException('token_exchange', $e);
                return redirect('/login')->withErrors(['user' => 'Terjadi kesalahan']);
            }


            try {
                $data_owner = $this->provider->getResourceOwner($token);
            } catch (Exception $e) {
                $this->logSsoException('userinfo', $e);
                return redirect('/login')->withErrors(['user' => 'Terjadi kesalahan']);
            }

            $data_owner_arr = $data_owner->toArray();

            Log::info('SSO user data keys', array_keys($data_owner_arr));

            // [2026-10-15 15:14:54] local.INFO: SSO user data keys ["sub","email_verified",
            // "name","preferred_username","locale",
            // "given_name","family_name","email"]

            $user = User::where('username',  $data_owner_arr['preferred_username'])
                ->where('active', 1)
                ->first();

            if($user) {
                try {
                    DB::beginTransaction();
                    if (array_key_exists("name",$data_owner_arr) && $data_owner_arr['name'])
                    {
                        $user->nama = $data_owner_arr['name'];
                    }
                    if (array_key_exists("nip",$data_owner_arr) && $data_owner_arr['nip'])
                    {
                        $user->nip_baru = $data_owner_arr['nip'];//nip
                    }
                    if (array_key_exists("nip-lama",$data_owner_arr) && $data_owner_arr['nip-lama'])
                    {
                        $user->nip_lama = $data_owner_arr['nip-lama'];//nip-lama
                    }
                    if (array_key_exists("foto",$data_owner_arr) && $data_owner_arr['foto'])
                    {
                        $user->bps_photo_url = $data_owner_arr['foto'];//foto
                    }
                    if (array_key_exists("jabatan",$data_owner_arr) && $data_owner_arr['jabatan'])
                    {
                        $user->jabatan = $data_owner_arr['jabatan'];
                    }
                    if (array_key_exists("golongan",$data_owner_arr) && $data_owner_arr['golongan'])
                    {
                        $user->golonagan = $data_owner_arr['golongan'];
                    }

                    $this->service->create_calender($user);

                    $user->save();
                    DB::commit();
                }catch (Exception $e) {
                    DB::rollBack();
                    // return abort(403, $e->getMessage());
                    Log::info("dua");
                    return redirect('/login')->withErrors(['username' => 'User tidak ditemukan']);
                }
                if(Auth::loginUsingId($user->id, true)){
                    return redirect()->intended("/");
                }
            }else{
                Log::info("tiga");
                return redirect('/login')->withErrors(['username' => 'Anda tidak mempunyai akses untuk aplikasi ini']);
            }
        }
    }

    public function logout(Request $request)
    {
        $request->session()->forget('oauth2state');

        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    // public function back(Request $request)
    // {
    //     $request->session()->forget('oauth2state');

    //     Auth::logout();

    //     $request->session()->invalidate();

    //     $request->session()->regenerateToken();

    //     $request->session()->put('tahun_active', 2026);

    //     $user = User::where('username', 'uhatdies')->first();

    //     Log::alert($user);

    //     if(Auth::loginUsingId($user->id, true)){
    //         Log::alert('asasasasasa');
    //         return redirect()->intended("/");
    //     }
    // }

}

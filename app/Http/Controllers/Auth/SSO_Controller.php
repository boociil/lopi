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
use JKD\SSO\Client\Provider\Keycloak;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SSO_Controller extends Controller
{
    private $provider;

    public function __construct(protected Google_Event_Local_Service $service)
    {
        $this->provider = new Keycloak([
            'authServerUrl'         => env('SSO_Url'),
            'realm'                 => env('SSO_Realm'),
            'clientId'              => env('Client_Id'),
            'clientSecret'          => env('Client_Secret'),
            'redirectUri'           => env('Client_URL')
        ]);
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
        if(Auth::check()){
            // Log::info('auth');
            return redirect('/');
        }
        if (!$request->input('code')) {
            if($request->tahun){
                $request->session()->put('tahun_active', $request->tahun);
            }else{
                return redirect('/login')->withErrors(['tahun' => 'Tahun belum di set']);
            }

            // Untuk mendapatkan authorization code
            $authUrl = $this->provider->getAuthorizationUrl();
            $request->session()->put('oauth2state', $this->provider->getState());
            return Redirect::to($authUrl);
        }  else if (!$request->has('state') || ($request->get('state') !== $request->session()->get('oauth2state'))) {

            $request->session()->forget('oauth2state');
            return redirect('/login')->withErrors(['user' => 'Terjadi kesalahan']);

        } else {
            Log::info('123123');
            try {
                $token = $this->provider->getAccessToken('authorization_code', [
                    'code' => $request->get('code')
                ]);
            } catch (Exception $e) {
                Log::error('Gagal mendapatkan akses token : '.$e->getMessage());
                return redirect('/login')->withErrors(['user' => 'Terjadi kesalahan']);
            }


            $data_owner = $this->provider->getResourceOwner($token);
            $data_owner_arr = $data_owner->toArray();

            $user = User::where('username',  $data_owner_arr['username'])
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

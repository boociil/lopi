<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Model\Umum\User;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    public function showLoginForm(Request $request): Response
    {
         return Inertia::render('auth/login', [
            "tahun_array" => explode(",", env("TAHUN_ARRAY")),
            "tahun_default" => env("TAHUN_DEFAULT"),
            'status' => $request->input('status'),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        // $request->request->add(['remember' => 1]);

        // $this->validateLogin($request);
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'tahun_active' => 'required|numeric',
        ]);


        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        // if (method_exists($this, 'hasTooManyLoginAttempts') &&
        //     $this->hasTooManyLoginAttempts($request)) {
        //     $this->fireLockoutEvent($request);

        //     return $this->sendLockoutResponse($request);
        // }

        // $request->merge(['remember' => true]);

        Log::alert('masasasas');

        if (Auth::attempt(['username'=>$request->username, 'password' => $request->password], true)) {
            session()->put('tahun_active', $request->tahun_active);
            return redirect()->intended($this->redirectTo());
        }

        if(User::where('username', $request->username)->count()){
            return redirect()->route('login', ['status'=> 'User dan Password tidak sesuai']);
        }else{
            return redirect()->route('login', ['status'=> 'User tidak ditemukan']);
        }
        // return $this->sendFailedLoginResponse($request);
    }

    protected function redirectTo(){
        return '/';
    }
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

}

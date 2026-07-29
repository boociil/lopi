<?php

namespace App\Http\Controllers\Lopi;

use App\Model\Lopi\Sync_Google\Calendar_User;

use App\Http\Controllers\Controller;
use App\Model\Lopi\User_Setting;
use App\Model\Umum\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class Profile_Controller extends Controller
{
    public function index(Request $request)
    {
        $profile_no_wa = User_Setting::where('category', 'profile')
            ->where('key', 'no_wa')
            ->where('username', Auth::user()->username)
            ->first();
        $no_whatsapp = null;
        if($profile_no_wa){
            $no_whatsapp = $profile_no_wa->data;
        }

        $calendar_id = "";
        $Calendar_User = Calendar_User::where('username', Auth::user()->username)
            ->first();
        if($Calendar_User){
            $calendar_id = $Calendar_User->google_calendar_id;
        }

        return Inertia::render('profile', [
                "no_whatsapp" => $no_whatsapp,
                'calendar_id' => $calendar_id,
            ]);
    }


    public function proxy_image($username)
    {

        // Tentukan path cache di storage lokal
        $cachePath = 'image-cache/' . $username;

        // 1. Cek apakah gambar sudah ada di cache lokal
        if (Storage::disk('public')->exists($cachePath)) {
            return response()->file(storage_path('app/public/' . $cachePath));
        }

        $user = User::where('username', $username)->first();
        // 2. Jika belum ada, definisikan URL asli di luar (yang butuh VPN)
        // Anda bisa memetakan $filename kembali ke URL asli atau menyimpannya berdasar parameter
        $targetUrl =  "https://simpeg.bps.go.id/apis/pegawai/avatar/" . $user->nip_lama ;

        try {
            // 3. Ambil gambar dari server luar (pastikan server inside yang menjalankan ini memiliki akses VPN)
            // Tambahkan timeout atau header tambahan jika diperlukan
            $response = Http::timeout(15)->get($targetUrl);

            if ($response->failed()) {
                abort(404, 'Image not found on remote server.');
            }

            $imageContent = $response->body();

            // 4. Deteksi MIME type secara otomatis dari binary data gambar asli
            // (mengatasi masalah URL tanpa mime type)
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageContent);

            // Validasi apakah benar-benar file gambar
            if (!str_starts_with($mimeType, 'image/')) {
                abort(400, 'Invalid image format.');
            }

            // 5. Simpan ke storage lokal (cache server inside)
            Storage::disk('public')->put($cachePath, $imageContent);

            // 6. Return file beserta MIME type yang benar ke browser
            return response($imageContent, 200)
                ->header('Content-Type', $mimeType)
                ->header('Cache-Control', 'public, max-age=31536000'); // Cache browser 1 tahun

        } catch (\Exception $e) {
            abort(500, 'Failed to fetch image: ' . $e->getMessage());
        }
    }
}

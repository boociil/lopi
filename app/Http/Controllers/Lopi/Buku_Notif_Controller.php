<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Model\Lopi\Buku_Pinjam;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Endpoint badge khusus menu Buku.
 *
 * Dipisahkan dari Notif_Controller supaya badge Buku tidak ikut menunggu
 * proses notifikasi menu lain (termasuk panggilan eksternal yang lambat).
 */
class Buku_Notif_Controller extends Controller
{
    public function count()
    {
        if (!in_array(Auth::user()->role_buku ?? 'umum', ['admin_buku', 'super_admin'], true)) {
            return response()->json(['count' => 0]);
        }

        $count = Buku_Pinjam::where('status', 'menunggu')->count()
            + Buku_Pinjam::where('status', 'dipinjam')
                ->whereNotNull('tanggal_jatuh_tempo')
                ->whereDate('tanggal_jatuh_tempo', '<', Carbon::today())
                ->count();

        return response()->json(['count' => $count]);
    }
}

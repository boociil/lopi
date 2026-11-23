<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Model\Lopi\Buku;
use App\Model\Lopi\Buku_Pinjam;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class Buku_Controller extends Controller
{
    /**
     * Katalog buku untuk semua pegawai + riwayat peminjaman milik sendiri.
     */
    public function index(Request $request)
    {
        $buku = Buku::orderBy('judul')->get();

        $riwayat = Buku_Pinjam::with(['buku'])
            ->where('peminjam_username', Auth::user()->username)
            ->orderByDesc('tanggal_pengajuan')
            ->limit(100)
            ->get();

        return Inertia::render('buku/list', [
            'buku' => $buku,
            'riwayat' => $riwayat,
        ]);
    }

    /**
     * Detail buku satu layar penuh.
     */
    public function detail(Request $request, Buku $buku)
    {
        $pinjam = Buku_Pinjam::where('buku_id', $buku->id)
            ->where('peminjam_username', Auth::user()->username)
            ->whereIn('status', ['menunggu', 'dipinjam'])
            ->first();

        return Inertia::render('buku/detail', [
            'buku' => $buku,
            'pinjam' => $pinjam,
        ]);
    }

    /**
     * Pengajuan peminjaman 1 klik.
     */
    public function pinjam(Request $request, Buku $buku)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'durasi' => 'required|integer|min:1|max:14',
        ]);

        if ($buku->stok <= 0) {
            throw ValidationException::withMessages([
                '_status' => 'Stok buku ini sedang habis.',
            ]);
        }

        $sudahAda = Buku_Pinjam::where('buku_id', $buku->id)
            ->where('peminjam_username', $user->username)
            ->whereIn('status', ['menunggu', 'dipinjam'])
            ->exists();

        if ($sudahAda) {
            throw ValidationException::withMessages([
                '_status' => 'Kamu sudah mengajukan atau sedang meminjam buku ini.',
            ]);
        }

        $pinjam = Buku_Pinjam::create([
            'buku_id' => $buku->id,
            'peminjam_username' => $user->username,
            'tanggal_pengajuan' => Carbon::now(),
            'durasi_pinjam' => (int) $validated['durasi'],
            'status' => 'menunggu',
        ]);

        return [
            '_status' => 'Pengajuan peminjaman berhasil dikirim.',
            'data' => $pinjam->load('buku'),
        ];
    }

    /**
     * Batalkan pengajuan yang masih menunggu.
     */
    public function batal(Request $request, Buku_Pinjam $pinjam)
    {
        if ($pinjam->peminjam_username !== Auth::user()->username || $pinjam->status !== 'menunggu') {
            throw ValidationException::withMessages([
                '_status' => 'Pengajuan ini tidak dapat dibatalkan.',
            ]);
        }

        $pinjam->delete();

        return ['_status' => 'Pengajuan peminjaman dibatalkan.'];
    }

    /**
     * Sajikan cover buku dari storage (aman untuk prefix /lopi).
     */
    public function cover(Buku $buku)
    {
        if (!$buku->cover_path) {
            abort(404);
        }

        $path = storage_path('app/public/' . $buku->cover_path);

        if (!is_file($path)) {
            abort(404);
        }

        return response()->file($path);
    }
}

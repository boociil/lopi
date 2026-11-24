<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Model\Lopi\Buku_Admin_Log;
use App\Model\Umum\Peran;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class Buku_Manajemen_Admin_Controller extends Controller
{
    /**
     * Daftar admin buku. Admin buku hanya bisa melihat,
     * perubahan hanya boleh dilakukan super admin.
     */
    public function index(Request $request)
    {
        $admins = User::whereIn('role_buku', ['admin_buku', 'super_admin'])
            ->orderBy('nama')
            ->get(['id', 'username', 'nama', 'jabatan', 'role_buku']);

        // Daftar pegawai (umum) hanya diperlukan super admin untuk promosi,
        // dan dibatasi hanya pegawai BPS Provinsi Sulawesi Barat.
        // Admin buku cukup melihat daftar admin saja.
        $users = Auth::user()->role_buku === 'super_admin'
            ? User::where('role_buku', 'umum')
                ->whereIn('username', $this->usernamesProvinsi())
                ->orderBy('nama')
                ->get(['id', 'username', 'nama', 'jabatan', 'role_buku'])
            : collect();

        $logs = $this->riwayatLogs();

        return Inertia::render('buku/manajemen-admin', [
            'admins' => $admins,
            'users' => $users,
            'logs' => $logs,
            'role_buku' => Auth::user()->role_buku,
            'super_admin_count' => User::where('role_buku', 'super_admin')->count(),
        ]);
    }

    /**
     * Riwayat perubahan admin (dipanggil via AJAX dari halaman manajemen).
     */
    public function logs()
    {
        return response()->json([
            'logs' => $this->riwayatLogs(),
        ]);
    }

    /**
     * Hapus entri riwayat perubahan admin.
     * Hanya super admin yang bisa menghapus riwayat.
     */
    public function hapus(Request $request, $id)
    {
        // Cari log berdasarkan ID dari URL parameter
        $log = Buku_Admin_Log::find($id);

        if (!$log) {
            throw ValidationException::withMessages([
                '_status' => 'Riwayat perubahan admin tidak ditemukan.',
            ]);
        }

        // Hanya super admin yang bisa menghapus riwayat
        if (Auth::user()->role_buku !== 'super_admin') {
            throw ValidationException::withMessages([
                '_status' => 'Hanya super admin yang dapat menghapus riwayat perubahan admin.',
            ]);
        }

        $log->delete();

        return [
            '_status' => 'Riwayat perubahan admin berhasil dihapus.',
        ];
    }

    /**
     * Riwayat perubahan admin lengkap dengan nama (bukan hanya username).
     */
    private function riwayatLogs()
    {
        $logs = Buku_Admin_Log::orderByDesc('id')->limit(30)->get();

        $usernames = $logs->pluck('actor_username')
            ->merge($logs->pluck('target_username'))
            ->filter()
            ->unique()
            ->values();

        $nama = $usernames->isEmpty()
            ? collect()
            : User::whereIn('username', $usernames)->pluck('nama', 'username');

        return $logs->map(function ($log) use ($nama) {
            return [
                'id' => $log->id,
                'actor_username' => $log->actor_username,
                'actor_nama' => $nama[$log->actor_username] ?? $log->actor_username,
                'target_username' => $log->target_username,
                'target_nama' => $nama[$log->target_username] ?? $log->target_username,
                'aksi' => $log->aksi,
                'keterangan' => $log->keterangan,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at,
                'updated_at' => $log->updated_at,
            ];
        })->values();
    }

    /**
     * Super admin menjadikan user sebagai admin buku.
     */
    public function jadikan(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|exists:siakip2_users,username',
        ]);

        $target = User::where('username', $validated['username'])->firstOrFail();

        if ($target->role_buku === 'super_admin') {
            throw ValidationException::withMessages([
                '_status' => 'Super admin tidak dapat diubah.',
            ]);
        }

        if (!$this->usernamesProvinsi()->contains($target->username)) {
            throw ValidationException::withMessages([
                '_status' => 'Hanya pegawai BPS Provinsi Sulawesi Barat yang dapat dijadikan admin buku.',
            ]);
        }

        if ($target->role_buku === 'admin_buku') {
            return [
                '_status' => 'User sudah menjadi admin buku.',
                'data' => $target,
            ];
        }

        $target->role_buku = 'admin_buku';
        $target->save();

        $this->log($request, $target->username, 'jadikan_admin', 'Menjadikan ' . $target->nama . ' sebagai admin buku');

        return [
            '_status' => 'Berhasil menjadikan admin buku.',
            'data' => $target->fresh(),
        ];
    }

    /**
     * Super admin mengangkat pengganti menjadi super admin.
     * Berguna agar akses super admin tetap ada saat admin lama pindah tugas.
     */
    public function jadikanSuperAdmin(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|exists:siakip2_users,username',
        ]);

        $target = User::where('username', $validated['username'])->firstOrFail();

        if ($target->role_buku === 'super_admin') {
            return [
                '_status' => 'User sudah menjadi super admin.',
                'data' => $target,
            ];
        }

        if (!$this->usernamesProvinsi()->contains($target->username)) {
            throw ValidationException::withMessages([
                '_status' => 'Hanya pegawai BPS Provinsi Sulawesi Barat yang dapat dijadikan super admin buku.',
            ]);
        }

        $target->role_buku = 'super_admin';
        $target->save();

        $this->log($request, $target->username, 'jadikan_super_admin', 'Menjadikan ' . $target->nama . ' sebagai super admin buku');

        return [
            '_status' => 'Berhasil menjadikan ' . $target->nama . ' sebagai super admin buku.',
            'data' => $target->fresh(),
        ];
    }

    /**
     * Super admin mencabut akses admin buku.
     * Super admin tidak dapat dicabut oleh siapa pun.
     */
    public function cabut(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|exists:siakip2_users,username',
        ]);

        $target = User::where('username', $validated['username'])->firstOrFail();

        if ($target->role_buku === 'super_admin') {
            throw ValidationException::withMessages([
                '_status' => 'Super admin tidak dapat dicabut oleh siapa pun.',
            ]);
        }

        if ($target->role_buku !== 'admin_buku') {
            throw ValidationException::withMessages([
                '_status' => 'User ini bukan admin buku.',
            ]);
        }

        $target->role_buku = 'umum';
        $target->save();

        $this->log($request, $target->username, 'cabut_admin', 'Mencabut akses admin buku dari ' . $target->nama);

        return [
            '_status' => 'Akses admin buku berhasil dicabut.',
            'data' => $target->fresh(),
        ];
    }

    /**
     * Super admin menurunkan dirinya sendiri menjadi admin buku.
     * Hanya boleh jika masih ada super admin lain.
     */
    public function turunkanSuperAdmin(Request $request)
    {
        $user = Auth::user();

        if ($user->role_buku !== 'super_admin') {
            abort(403, 'Forbidden.');
        }

        // Wajib menyisakan minimal 1 super admin.
        // Transaksi + lock mencegah dua super admin terakhir turun bersamaan.
        DB::beginTransaction();
        try {
            $jumlahSuperAdmin = User::where('role_buku', 'super_admin')
                ->lockForUpdate()
                ->count();

            if ($jumlahSuperAdmin <= 1) {
                throw ValidationException::withMessages([
                    '_status' => 'Minimal harus ada satu super admin buku. Kamu adalah super admin terakhir.',
                ]);
            }

            $user->role_buku = 'admin_buku';
            $user->save();

            $this->log($request, $user->username, 'turunkan_super_admin', 'Menurunkan diri sendiri menjadi admin buku');

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return ['_status' => 'Kamu sekarang menjadi admin buku.'];
    }

    /**
     * Username pegawai BPS Provinsi Sulawesi Barat pada tahun aktif.
     * Keanggotaan satuan kerja diambil dari siakip2_peran (tipe satuan_kerja_tahun).
     */
    private function usernamesProvinsi(): \Illuminate\Support\Collection
    {
        $tahun = session('tahun_active') ?: DB::table('siakip2_satuan_kerja_tahun')->max('tahun');

        $provIds = Satuan_Kerja_Tahun::where('tahun', $tahun)
            ->where('kode_satuan_kerja', '7600')
            ->pluck('id');

        return Peran::where('tipe_peran', 'satuan_kerja_tahun')
            ->where('tahun', $tahun)
            ->whereIn('parent_id', $provIds)
            ->pluck('pemilik_username')
            ->unique()
            ->values();
    }

    private function log(Request $request, string $targetUsername, string $aksi, string $keterangan): void
    {
        Buku_Admin_Log::create([
            'actor_username' => Auth::user()->username,
            'target_username' => $targetUsername,
            'aksi' => $aksi,
            'keterangan' => $keterangan,
            'ip_address' => $request->ip(),
        ]);
    }
}

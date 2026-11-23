<?php

namespace App\Http\Controllers\Lopi;

use App\Http\Controllers\Controller;
use App\Model\Lopi\Buku;
use App\Model\Lopi\Buku_Pinjam;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class Buku_Admin_Controller extends Controller
{
    /**
     * Master: monitoring pengajuan, persetujuan, dan keterlambatan.
     */
    public function master(Request $request)
    {
        $pinjam = Buku_Pinjam::with(['buku', 'peminjam'])
            ->orderByDesc('tanggal_pengajuan')
            ->get();

        $terlambat = Buku_Pinjam::where('status', 'dipinjam')
            ->whereNotNull('tanggal_jatuh_tempo')
            ->whereDate('tanggal_jatuh_tempo', '<', Carbon::today())
            ->count();

        return Inertia::render('buku/master', [
            'pinjam' => $pinjam,
        ]);
    }

    public function setujui(Request $request, Buku_Pinjam $pinjam)
    {
        if ($pinjam->status !== 'menunggu') {
            throw ValidationException::withMessages([
                '_status' => 'Pengajuan ini sudah diproses.',
            ]);
        }

        DB::beginTransaction();
        try {
            $buku = Buku::whereKey($pinjam->buku_id)->lockForUpdate()->first();

            if (!$buku || $buku->stok <= 0) {
                throw ValidationException::withMessages([
                    '_status' => 'Stok buku sudah habis.',
                ]);
            }

            $pinjam->status = 'dipinjam';
            $pinjam->tanggal_persetujuan = Carbon::now();
            $durasi = (int) ($pinjam->durasi_pinjam ?: $buku->durasi_pinjam);
            $pinjam->tanggal_jatuh_tempo = Carbon::today()
                ->addDays(max(1, min(14, $durasi)))
                ->format('Y-m-d');
            $pinjam->diproses_oleh = Auth::user()->username;
            $pinjam->save();

            $buku->stok = max(0, $buku->stok - 1);
            $buku->save();

            DB::commit();

            return [
                '_status' => 'Peminjaman disetujui.',
                'data' => $pinjam->fresh(['buku', 'peminjam']),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function tolak(Request $request, Buku_Pinjam $pinjam)
    {
        if ($pinjam->status !== 'menunggu') {
            throw ValidationException::withMessages([
                '_status' => 'Pengajuan ini sudah diproses.',
            ]);
        }

        $pinjam->status = 'ditolak';
        $pinjam->catatan_admin = null;
        $pinjam->diproses_oleh = Auth::user()->username;
        $pinjam->save();

        return [
            '_status' => 'Pengajuan ditolak.',
            'data' => $pinjam->fresh(['buku', 'peminjam']),
        ];
    }

    public function kembali(Request $request, Buku_Pinjam $pinjam)
    {
        if ($pinjam->status !== 'dipinjam') {
            throw ValidationException::withMessages([
                '_status' => 'Peminjaman ini tidak sedang berjalan.',
            ]);
        }

        DB::beginTransaction();
        try {
            $pinjam->status = 'dikembalikan';
            $pinjam->tanggal_kembali = Carbon::today()->format('Y-m-d');
            $pinjam->save();

            $buku = Buku::whereKey($pinjam->buku_id)->lockForUpdate()->first();
            if ($buku) {
                $buku->stok = $buku->stok + 1;
                $buku->save();
            }

            DB::commit();

            return [
                '_status' => 'Buku berhasil ditandai kembali.',
                'data' => $pinjam->fresh(['buku', 'peminjam']),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Manajemen Buku: CRUD katalog.
     */
    public function manajemen(Request $request)
    {
        return Inertia::render('buku/manajemen', [
            'buku' => Buku::orderBy('judul')->get(),
        ]);
    }

    public function tambah(Request $request)
    {
        $data = $this->validateBuku($request);
        unset($data['cover']);

        if ($request->hasFile('cover')) {
            $data['cover_path'] = $this->simpanCover($request);
        }

        $buku = Buku::create($data);

        return [
            '_status' => 'Buku berhasil ditambahkan.',
            'data' => $buku->fresh(),
        ];
    }

    public function ubah(Request $request, Buku $buku)
    {
        $data = $this->validateBuku($request);
        unset($data['cover']);

        if ($request->hasFile('cover')) {
            $this->hapusCover($buku);
            $data['cover_path'] = $this->simpanCover($request);
        }

        $buku->update($data);

        return [
            '_status' => 'Buku berhasil diubah.',
            'data' => $buku->fresh(),
        ];
    }

    public function hapus(Buku $buku)
    {
        $masihAktif = $buku->pinjam()
            ->whereIn('status', ['menunggu', 'dipinjam'])
            ->exists();

        if ($masihAktif) {
            throw ValidationException::withMessages([
                '_status' => 'Buku masih memiliki pengajuan atau peminjaman aktif.',
            ]);
        }

        $this->hapusCover($buku);
        $buku->delete();

        return ['_status' => 'Buku berhasil dihapus.'];
    }

    private function validateBuku(Request $request): array
    {
        return $request->validate([
            'judul' => 'required|string|max:255',
            'pengarang' => 'nullable|string|max:255',
            'jumlah_halaman' => 'nullable|integer|min:1|max:100000',
            'tahun_terbit' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'bahasa' => 'nullable|string|max:50',
            'kategori' => 'nullable|string|max:100',
            'sinopsis' => 'nullable|string|max:5000',
            'stok' => 'required|integer|min:0|max:100000',
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:500',
        ]);
    }

    private function simpanCover(Request $request): string
    {
        $file = $request->file('cover');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = Str::uuid()->toString() . '.' . $extension;

        return $file->storeAs('buku', $filename, 'public');
    }

    private function hapusCover(Buku $buku): void
    {
        if ($buku->cover_path) {
            Storage::disk('public')->delete($buku->cover_path);
        }
    }
}
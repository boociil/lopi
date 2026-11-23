<?php

namespace App\Model\Lopi;

use App\Model\Umum\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Buku_Pinjam extends Model
{
    protected $table = 'siakip2_buku_pinjam';

    protected $fillable = [
        'buku_id',
        'peminjam_username',
        'tanggal_pengajuan',
        'durasi_pinjam',
        'tanggal_persetujuan',
        'tanggal_jatuh_tempo',
        'tanggal_kembali',
        'status',
        'diproses_oleh',
    ];

    protected $casts = [
        'tanggal_pengajuan' => 'datetime',
        'tanggal_persetujuan' => 'datetime',
        'durasi_pinjam' => 'integer',
    ];

    protected $appends = ['is_terlambat', 'hari_terlambat', 'sisa_hari', 'is_kembali_terlambat', 'hari_kembali_terlambat'];

    public function buku()
    {
        return $this->hasOne(Buku::class, 'id', 'buku_id');
    }

    public function peminjam()
    {
        return $this->hasOne(User::class, 'username', 'peminjam_username');
    }

    public function getIsTerlambatAttribute(): bool
    {
        if ($this->status !== 'dipinjam' || !$this->tanggal_jatuh_tempo) {
            return false;
        }

        return Carbon::parse($this->tanggal_jatuh_tempo)->startOfDay()->lt(Carbon::today());
    }

    public function getHariTerlambatAttribute(): int
    {
        if (!$this->is_terlambat) {
            return 0;
        }

        return (int) Carbon::parse($this->tanggal_jatuh_tempo)
            ->startOfDay()
            ->diffInDays(Carbon::today());
    }

    /**
     * Sisa hari menuju jatuh tempo (0 = hari ini, 1 = besok, dst).
     * Null jika bukan peminjaman aktif atau belum ada jatuh tempo.
     */
    public function getSisaHariAttribute(): ?int
    {
        if ($this->status !== 'dipinjam' || !$this->tanggal_jatuh_tempo) {
            return null;
        }

        return (int) Carbon::today()->diffInDays(
            Carbon::parse($this->tanggal_jatuh_tempo)->startOfDay(),
            false,
        );
    }

    /**
     * Apakah buku dikembalikan melewati tanggal jatuh tempo.
     */
    public function getIsKembaliTerlambatAttribute(): bool
    {
        if ($this->status !== 'dikembalikan' || !$this->tanggal_kembali || !$this->tanggal_jatuh_tempo) {
            return false;
        }

        return Carbon::parse($this->tanggal_kembali)->startOfDay()
            ->gt(Carbon::parse($this->tanggal_jatuh_tempo)->startOfDay());
    }

    /**
     * Jumlah hari keterlambatan saat buku dikembalikan (0 jika tepat waktu).
     */
    public function getHariKembaliTerlambatAttribute(): int
    {
        if (!$this->is_kembali_terlambat) {
            return 0;
        }

        return (int) Carbon::parse($this->tanggal_jatuh_tempo)->startOfDay()
            ->diffInDays(Carbon::parse($this->tanggal_kembali)->startOfDay());
    }
}

<?php

namespace App\Model\Lopi;

use Illuminate\Database\Eloquent\Model;

class Buku extends Model
{
    protected $table = 'siakip2_buku';

    protected $fillable = [
        'judul',
        'pengarang',
        'jumlah_halaman',
        'tahun_terbit',
        'bahasa',
        'kategori',
        'sinopsis',
        'durasi_pinjam',
        'stok',
        'cover_path',
    ];

    protected $casts = [
        'jumlah_halaman' => 'integer',
        'tahun_terbit' => 'integer',
        'durasi_pinjam' => 'integer',
        'stok' => 'integer',
    ];

    protected $appends = ['cover_url'];

    public function pinjam()
    {
        return $this->hasMany(Buku_Pinjam::class, 'buku_id', 'id');
    }

    public function getCoverUrlAttribute(): ?string
    {
        if (!$this->cover_path) {
            return null;
        }

        return route('buku-cover', ['buku' => $this->id]);
    }
}
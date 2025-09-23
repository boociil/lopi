<?php

namespace App\Model\Lopi;

use App\Model\Umum\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pinjam_Mobil extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_pinjam_mobil';
    protected $primaryKey = 'id';
    public $incrementing = false; // karena pakai string UUID
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'kegiatan',
        'tipe',
        'berulang_id',
        'tanggal_awal',
        'tanggal_akhir',
        'waktu_awal',
        'waktu_akhir',
        'status',
        'pembuat_username',
        'confirm_username',
    ];

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'pembuat_username', 'username');
    }

    public function confirm()
    {
        return $this->belongsTo(User::class, 'confirm_username', 'username');
    }
}

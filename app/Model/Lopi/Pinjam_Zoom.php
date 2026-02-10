<?php

namespace App\Model\Lopi;

use App\Model\Umum\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pinjam_Zoom extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_pinjam_zoom';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'id',
        'kegiatan',
        'password',
        'tipe',
        'tanggal',
        'berulang_tanggal_awal',
        'berulang_tanggal_akhir',
        'waktu_awal',
        'waktu_akhir',
        'status',
        'pembuat_username',
        'confirm_username',
        'zoom_akun',
        'zoom_meeting_id',
        'zoom_start_url',
        'zoom_join_url',
        'jadwal_id',
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

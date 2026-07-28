<?php

namespace App\Model\Lopi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OneTap_Page extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_one_tap_pages';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'pembuat_username',
        'satuan_kerja_tahun_id',
        'judul',
        'slug',
        'deskripsi',
        'akses',
        'tema',
        'foto_url',
        'warna_latar',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function buttons(): HasMany
    {
        return $this->hasMany(
            OneTap_Button::class,
            'one_tap_page_id',
            'id'
        );
    }
}

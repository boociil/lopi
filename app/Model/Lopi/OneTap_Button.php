<?php

namespace App\Model\Lopi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OneTap_Button extends Model
{
    protected $table = 'siakip2_one_tap_buttons';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'one_tap_page_id',
        'tipe',
        'judul',
        'url',
        'ikon',
        'warna',
        'aktif',
        'urutan',
    ];

    protected $casts = [
        'aktif' => 'boolean',
        'urutan' => 'integer',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(
            OneTap_Page::class,
            'one_tap_page_id',
            'id'
        );
    }
}

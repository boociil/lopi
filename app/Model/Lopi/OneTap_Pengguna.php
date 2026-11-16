<?php

namespace App\Model\Lopi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OneTap_Pengguna extends Model
{
    protected $table = 'siakip2_one_tap_pengguna';

    protected $fillable = ['page_id', 'pengguna_username'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(OneTap_Page::class, 'page_id');
    }
}

<?php

namespace App\Model\Lopi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Link_Item extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_kesehatan';
    public $timestamps = false;

    protected $fillable = ['username', 'tanggal', 'berat_badan'
        , 'linkar_perut', 'tekanan_darah', 'asam_urat', 'gula_darah', 'gula_darah_tipe', 'kolestrol'];

     public function peserta(): MorphTo
    {
        return $this->morphTo();
    }
}

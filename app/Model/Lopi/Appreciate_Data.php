<?php

namespace App\Model\Lopi;

use App\Model\Umum\User;
use Illuminate\Database\Eloquent\Model;

class Appreciate_Data extends Model
{
    protected $table = 'siakip2_appreciate_data';

    protected $fillable = [
        'master_id', 'dari', 'tujuan', 'status_anonim', 'status', 'pesan'
    ];

    public function dariUser()
    {
        return $this->belongsTo(User::class, 'dari', 'username');
    }

    public function tujuanUser()
    {
        return $this->belongsTo(User::class, 'tujuan', 'username');
    }
}

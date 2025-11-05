<?php

namespace App\Model\Lopi;

use App\Model\Umum\User;
use Illuminate\Database\Eloquent\Model;

class Appreciate_Master extends Model
{
    protected $table = 'siakip2_appreciate_master';

    protected $fillable = [
        'nama', 'isi_awal', 'isi_akhir'
    ];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'username', 'username');
    }

    public function target()
    {
        return $this->hasMany(Appreciate_Target::class, 'master_id', 'id');
    }

    public function data()
    {
        return $this->hasMany(Appreciate_Data::class, 'master_id', 'id');
    }
}

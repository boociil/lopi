<?php

namespace App\Model\Lopi;

use App\Model\Umum\User;
use Illuminate\Database\Eloquent\Model;

class Appreciate_Master extends Model
{
    protected $table = 'appreciate_master';

    protected $fillable = [
        'nama', 'isi_awal', 'isi_akhir'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'username', 'username');
    }

    public function target()
    {
        return $this->hasMany(Appreciate_Target::class, 'id', 'master_id');
    }
}

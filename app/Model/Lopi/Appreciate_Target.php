<?php

namespace App\Model\Lopi;

use App\Model\Umum\User;
use Illuminate\Database\Eloquent\Model;

class Appreciate_Target extends Model
{
    protected $table = 'siakip2_appreciate_target';

    protected $fillable = [
        'master_id', 'username'
    ];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'username', 'username');
    }
}

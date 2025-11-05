<?php

namespace App\Model\Lopi;

use App\Model\Umum\User;
use Illuminate\Database\Eloquent\Model;

class Appreciate_Target extends Model
{
    protected $table = 'appreciate_target';

    protected $fillable = [
        'master_id', 'username'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'username', 'username');
    }
}

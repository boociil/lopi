<?php

namespace App\Model\Lopi;

use Illuminate\Database\Eloquent\Model;

class Buku_Admin_Log extends Model
{
    protected $table = 'siakip2_buku_admin_log';

    protected $fillable = [
        'actor_username',
        'target_username',
        'aksi',
        'keterangan',
        'ip_address',
    ];
}

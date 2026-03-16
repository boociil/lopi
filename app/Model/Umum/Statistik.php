<?php

namespace App\Model\Umum;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Statistik extends Model
{
    protected $table = 'siakip2_statistik';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = ['id', 'method', 'username','ip_address', 'path'];

    public static function helper_tambah($request)
    {
        if (Auth::check()) {
            $baru =  new Statistik;
            $baru->method = $request->method();
            $baru->username = Auth::user()->username;
            $baru->ip_address = $request->ip();
            $baru->path = 'lopi/'.$request->path();
            $baru->save();
            return $baru;
        }else{
            $baru =  new Statistik;
            $baru->method = $request->method();
            $baru->username = null;
            $baru->ip_address = $request->ip();
            $baru->path = 'lopi/'.$request->path();
            $baru->save();
            return $baru;
        }
    }
}

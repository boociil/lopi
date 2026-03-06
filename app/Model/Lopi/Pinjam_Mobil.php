<?php

namespace App\Model\Lopi;

use App\Model\Umum\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Pinjam_Mobil extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_pinjam_mobil';
    protected $primaryKey = 'id';
    public $incrementing = false; // karena pakai string UUID
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'kegiatan',
        'tipe',
        'tanggal',
        'berulang_tanggal_awal',
        'berulang_tanggal_akhir',
        'waktu_awal',
        'waktu_akhir',
        'status',
        'pembuat_username',
        'confirm_username',
    ];

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'pembuat_username', 'username');
    }

    public function confirm()
    {
        return $this->belongsTo(User::class, 'confirm_username', 'username');
    }

    public function checkClash(){
        if($this->type == 'sekali'){
            return Pinjam_Mobil::staticCheckClash($this->tanggal, $this->tanggal, $this->waktu_awal, $this->waktu_akhir);
        }else if($this->type == 'berulang'){
            return Pinjam_Mobil::staticCheckClash($this->berulang_tanggal_awal, $this->berulang_tanggal_akhir, $this->waktu_awal, $this->waktu_akhir);
        }
    }

    public static function staticCheckClash($tanggal_awal, $tanggal_akhir, $waktu_awal, $waktu_akhir){
        $jadwal_daftar = Pinjam_Mobil::where('status', 'disetujui')
            ->where(function ($query0) use($tanggal_awal, $tanggal_akhir) {
                $query0->orWhere(function (Builder $query) use ($tanggal_awal, $tanggal_akhir){
                            $query->where('tipe', 'sekali')
                                ->whereBetween('tanggal', [$tanggal_awal, $tanggal_akhir]);
                        })
                        ->orWhere(function (Builder $query) use ($tanggal_awal, $tanggal_akhir) {
                            $query->where('tipe', 'berulang')
                                ->where('berulang_tanggal_awal', '<=', $tanggal_akhir)
                                ->where('berulang_tanggal_akhir', '>=', $tanggal_awal);
                        });
            })
            ->get();


        $awalCarrbon = Carbon::createFromFormat('Y-m-d H:i:s',  $tanggal_awal . ' ' . $waktu_awal);
        $akhirCarrbon = Carbon::createFromFormat('Y-m-d H:i:s',  $tanggal_akhir . ' ' . $waktu_akhir);

        $isClash = false;

        foreach($jadwal_daftar as $item){
            if($item->tipe == 'sekali'){
                if( ( $awalCarrbon < Carbon::createFromFormat('Y-m-d H:i:s', $item->tanggal. ' ' .$item->waktu_akhir)
                    && $akhirCarrbon > Carbon::createFromFormat('Y-m-d H:i:s', $item->tanggal. ' ' .$item->waktu_awal)))
                {
                    $isClash=true;
                }
            }else if($item->tipe == 'berulang'){
                if( ( $awalCarrbon < Carbon::createFromFormat('Y-m-d H:i:s', $item->tanggal_akhir. ' ' .$item->waktu_akhir)
                    && $akhirCarrbon > Carbon::createFromFormat('Y-m-d H:i:s', $item->tanggal_awal. ' ' .$item->waktu_awal)))
                {
                    $isClash=true;
                }
            }
        }

        return $isClash;
    }
}

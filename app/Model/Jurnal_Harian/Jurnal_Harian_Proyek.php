<?php

namespace App\Model\Jurnal_Harian;

use App\Model\Umum\Fungsi;
use App\Model\Umum\Peran;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\Tim;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Webpatser\Uuid\Uuid;
use Exception;

class Jurnal_Harian_Proyek extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_jurnal_harian_proyek';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = ['id', 'tahun', 'status', 'tanggal_awal', 'tanggal_akhir'
        , 'satuan_kerja_tahun_id', 'fungsi_id', 'tim_id', 'pembuat_username', 'nama'];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('default_sort', function (Builder $builder) {
            $builder->orderBy('tanggal_akhir', 'asc');
            $builder->orderBy('tanggal_awal', 'asc');
        });
    }

    public static function helper_tambah(?Satuan_Kerja_Tahun $satuan_Kerja_Tahun, ?Tim $tim, $data)
    {
        if($data->tanggal_awal && $data->tanggal_akhir){
            if(\Carbon\Carbon::parse($data->tanggal_awal) > \Carbon\Carbon::parse($data->tanggal_akhir)){
                throw new Exception("Tanggal awal tidak boleh lebih dari tanggal akhir");
            }
        }

        $baru =  new Jurnal_Harian_Proyek;
        $baru->id =  (string) Uuid::generate();
        $baru->tahun = session('tahun_active');
        $baru->status = $data->status;
        $baru->tanggal_awal = $data->tanggal_awal;
        $baru->tanggal_akhir = $data->tanggal_akhir;

        if($tim){
            $baru->satuan_kerja_tahun_id = $tim->satuan_kerja_tahun_id;
            $baru->fungsi_id = null;
            $baru->tim_id = $tim->id;
        }else if($satuan_Kerja_Tahun){
            $baru->satuan_kerja_tahun_id = $satuan_Kerja_Tahun->id;
            $baru->fungsi_id = null;
            $baru->tim_id = null;
        }

        $baru->pembuat_username = Auth::user()->username;
        $baru->nama = $data->nama;
        $baru->save();
        return $baru;
    }

    public function helper_ubah($data)
    {
        if($data->tanggal_awal && $data->tanggal_akhir){
            if(\Carbon\Carbon::parse($data->tanggal_awal) > \Carbon\Carbon::parse($data->tanggal_akhir)){
                throw new Exception("Tanggal awal tidak boleh lebih dari tanggal akhir");
            }
        }

        $this->tanggal_awal = $data->tanggal_awal;
        $this->tanggal_akhir = $data->tanggal_akhir;
        $this->status = $data->status;
        $this->nama = $data->nama;
        $this->save();
    }

    public function helper_hapus()
    {
        if(Jurnal_Harian_Item::where('jurnal_harian_proyek_id', $this->id)->count() == 0){
            Peran::where('parent_id', $this->id)->delete();
            $this->delete();
        }else {
            throw new Exception("Hapus item terlebih dahulu");
        }
    }

    public function jurnal_harian_item()
    {
        return $this->hasMany(Jurnal_Harian_Item::class, 'jurnal_harian_proyek_id', 'id');
    }

}

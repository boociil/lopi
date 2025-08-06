<?php

namespace App\Model\Jurnal_Harian;

use App\Model\Umum\URL_Model;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Webpatser\Uuid\Uuid;

class Jurnal_Harian_Berkas extends Model
{
    use SoftDeletes;

    protected $table = 'siakip2_jurnal_harian_berkas';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = ['id', 'jurnal_harian_item_id'
        , 'username_pengirim', 'username_approval', 'status_approval'
        , 'type_berkas', 'text', 'nilai_baru', 'file_id'];

    protected $casts = [
        'nilai_baru' => 'array',
    ];

    protected $appends = ['updated_at_tulis'];

    public function getUpdatedAtTulisAttribute()
    {
        return \Carbon\Carbon::parse($this->attributes['updated_at'])
            ->format('d M Y, H:i');
    }

    public static function helper_tambah(Jurnal_Harian_Item $Jurnal_Harian_Item, $data, $have_auth){
        $baru =  new Jurnal_Harian_Berkas;

        $baru->id =  (string) Uuid::generate();
        $baru->jurnal_harian_item_id = $Jurnal_Harian_Item->id;
        $baru->tahun = $Jurnal_Harian_Item->tahun;

        $baru->username_pengirim = Auth::user()->username;
        $baru->username_approval = null;

        $baru->type_berkas = $data->type_berkas;

        if($baru->type_berkas == 'text'){
            if(!$have_auth['text']){
                abort(403);
            }
            $baru->text = $data->text;
        }else if($baru->type_berkas == 'url'){
            if(!$have_auth['text']){
                abort(403);
            }
            if(!$data->file){
                abort(400, 'input kurang');
            }
            $url_baru = URL_Model::helper_tambah($data);
            $baru->file_id = $url_baru->id;
        }

        $baru->save();

        return $baru;
    }
    
    public function helper_hapus(){
        $this->delete();
    }

    public function file()
    {
        return $this->hasOne(URL_Model::class, 'id', 'file_id');
    }

    public function Jurnal_Harian_Item()
    {
        return $this->hasOne(Jurnal_Harian_Item::class, 'id', 'jurnal_harian_item_id');
    }

}

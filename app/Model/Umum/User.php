<?php

namespace App\Model\Umum;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'siakip2_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    public $timestamps = true;

    protected $fillable = [
        'username', 'email', 'nip_baru', 'nip_lama', 'nama', 'jabatan', 'golonagan'
        , 'photo_url_id', 'bps_photo_url', 'active'
    ];

    public function photo_url()
    {
        return $this->hasOne(URL_Model::class, 'id', 'photo_url_id');
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token'
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('default_sort', function (Builder $builder) {
            $builder->orderBy('created_at', 'asc');
        });
        static::saved(function (User $user) {
            User::set_data_all();
        });
        static::deleted(function (User $user) {
            User::set_data_all();
        });
    }

    public static function helper_getBy_Username($username){
        return User::where('username', $username);
    }

    public static function helper_tambah($data)
    {
        $baru =  new User;
        $baru->username = $data->username;
        $baru->email = $data->email;
        $baru->nip_baru = $data->nip_baru;
        $baru->nip_lama = $data->nip_lama;
        $baru->nama = $data->nama;
        $baru->password = Hash::make($data->password);
        $baru->jabatan = $data->jabatan;
        $baru->golonagan = $data->golonagan;

        $baru->photo_url_id = $data->photo_url_id;
        $baru->active = 1;

        User_Prop::helper_user($baru, $data->prop);
        $baru->save();
        return $baru;
    }

    public function helper_ubah_profil($data)
    {
        if($data->password){
            $this->password = Hash::make($data->password);
        }
        $this->photo_url_id = $data->photo_url_id;
        $this->save();
    }

    public function helper_ubah($data)
    {
        if($this->username !== $data->username){
            User::helper_ubah_username($this->username, $data->username);
            $this->username = $data->username;
        }
        $this->email = $data->email;
        $this->nip_baru = $data->nip_baru;
        $this->nip_lama = $data->nip_lama;
        $this->nama = $data->nama;
        if($data->password){
            $this->password = Hash::make($data->password);
        }
        $this->jabatan = $data->jabatan;
        $this->golonagan = $data->golonagan;

        $this->photo_url_id = $data->photo_url_id;
        $this->active = $data->active;

        if($data->prop){
            User_Prop::helper_user($this, $data->prop);
        }

        $this->save();
    }

    public function helper_hapus($nip_lama)
    {
        $this->active = 0;
        $this->save();
    }

    public function user_prop()
    {
        return $this->hasMany(User_Prop::class,'username','username');
    }

    public function peran_super_admin()
    {
        return $this->hasMany(Peran::class,'pemilik_username','username')
            ->where('tipe_peran', 'global')
            ->where('peran', 'admin');
    }

    //perlu perbaikan

    public function get_id_as_ketua_tim()
    {
        $return_value = [];
        foreach($this->team_anggota as $item_team_anggota){
            if($item_team_anggota->status == 2){
                array_push($return_value, $item_team_anggota->team_id);
            }
        }
        return $return_value;
    }

    public function check_is_ketua_team($team_id)
    {
        if(!$team_id){
            return false;
        }
        foreach($this->team_anggota as $item_team_anggota){
            if($item_team_anggota->team_id === $team_id
                && $item_team_anggota->status == 2){
                    return true;
                }
        }
        return false;
    }

    public function check_is_pj_team($team_id)
    {
        if(!$team_id){
            return false;
        }
        foreach($this->team_anggota as $item_team_anggota){
            if($item_team_anggota->team_id === $team_id
                && $item_team_anggota->status == 1){
                    return true;
                }
        }
        return false;
    }

    public function check_anggota_team($team_id)
    {
        if(!$team_id){
            return false;
        }
        foreach($this->team_anggota as $item_team_anggota){
            if($item_team_anggota->team_id === $team_id){
                return true;
            }
        }
        return false;
    }

    public static function set_data_all()
    {
        Cache::forget('arr_user');
        Cache::forget('dict_user');
        $arr_all_user = User::all()->toArray();
        $dict_all_user = [];
        foreach($arr_all_user as $item_user){
            $dict_all_user[$item_user['username']] = $item_user;
        }
        Cache::rememberForever('arr_user', function () use ($arr_all_user){
            return $arr_all_user;
        });
        Cache::rememberForever('dict_user', function () use ($dict_all_user) {
            return $dict_all_user;
        });
    }

    public static function get_data_all($type = 'arr')
    {
        if( !(Cache::has('arr_user') && Cache::has('dict_user')) )
        {
            User::set_data_all();
        }
        if($type == 'arr'){
            return Cache::get('arr_user', []);
        }else if($type == 'dict'){
            return Cache::get('dict_user', []);
        }
        return [];
    }

    public static function find_data($username, $type = 'arr')
    {
        if(!is_array($username))
        {
             $username = [$username];
        }
        if( !(Cache::has('arr_user') && Cache::has('dict_user')) )
        {
            User::set_data_all();
        }

        $return_data = [];
        $data_dict = Cache::get('dict_user', []);

        foreach($username as $item_username)
        {
            if(array_key_exists($item_username, $data_dict))
            {
                if($type == 'arr')
                {
                    array_push($return_data, $data_dict[$item_username]);
                }else if($type == 'dict')
                {
                    $return_data[$item_username] = $data_dict[$item_username];
                }
            }
        }
        return $return_data;
    }

    public function peran()
    {
        return $this->hasMany(Peran::class,'pemilik_username','username');
    }

    public static function getDataDict() {
        $data = self::all();
        $return_data = [];
        foreach($data as $item){
            $return_data[$item->username] = $$item;
        }
        return $return_data;
    }

}

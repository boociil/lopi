<?php

namespace App\Http\Controllers\Lopi;

use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\Controller;
use App\Model\Lopi\Jadwal;
use App\Model\Lopi\Sync_Google\Calendar_Google_Event;
use App\Model\Lopi\Sync_Google\Calendar_User;
use App\Model\Umum\Peran;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\Tim;
use App\Model\Umum\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Str;

class Google_Sync_Calandar extends Controller
{
    public function create_calender(){
        $user_calender = Calendar_User::where('username', Auth::user()->username)->first();

        if(!$user_calender){
            $client = new \Google\Client();
            $client->setAuthConfig('lopiservice-48fe7bfdaa73.json');
            $client->addScope(\Google\Service\Calendar::CALENDAR);

            $service = new \Google\Service\Calendar($client);

            $calendar = new \Google\Service\Calendar\Calendar([
                'summary' => 'Lopi - '.Auth::user()->nama,
                'timeZone' => 'Asia/Makassar',
            ]);

            $created = $service->calendars->insert($calendar);

            $rule = new \Google\Service\Calendar\AclRule([
                'scope' => [
                    'type' => 'default',
                ],
                'role' => 'reader',
            ]);

            $service->acl->insert($created->getId(), $rule);



            $calendar = new Calendar_User();
            $calendar->id = Str::uuid();
            $calendar->google_calendar_id = $created->getId();
            $calendar->username = Auth::user()->username;
            $calendar->save();

            $parent_id_arr_before_pluck = Peran::where('pemilik_username', Auth::user()->username)
                ->whereIn('tipe_peran', ['satuan_kerja_tahun', 'tim'])
                ->where('tahun', session('tahun_active'))
                ->select('tipe_peran', 'parent_id', 'peran')
                ->distinct()
                ->get();
            $parent_id = [];
            foreach($parent_id_arr_before_pluck as $item){
                array_push($parent_id, $item->parent_id);
            }

            $tanggal_awal = Carbon::now()->startOfMonth();

            $jadwal_daftar = Jadwal::with(['zoom_pinjam', 'peserta'])
                ->whereHas('peserta', function (Builder $query) use ($parent_id){
                        $query->orWhereIn('peserta_id', $parent_id);
                        $query->orWhere('peserta_id', Auth::user()->username);
                    })
                ->where(function (Builder $query0) use ($tanggal_awal){
                    $query0->orWhere(function (Builder $query) use ($tanggal_awal){
                        $query->where('tipe', 'sekali')
                            ->where('tanggal', $tanggal_awal->format('Y-m-d'));
                    })
                    ->orWhere(function (Builder $query) use ($tanggal_awal) {
                        $query->where('tipe', 'berulang')
                            ->where('berulang_tanggal_akhir', '>=', $tanggal_awal);
                    });
                })
                ->get();

            $user = User::all();
            $user_dict = [];
            foreach($user as $item){
                $user_dict[$item->username] = $item->nama;
            }

            $satuan_kerja_tahun = Satuan_Kerja_Tahun::where('tahun', session('tahun_active'))->get();
            $satuan_kerja_tahun_dict = [];
            foreach($satuan_kerja_tahun as $item){
                $satuan_kerja_tahun_dict[$item->id] = $item->nama;
            }

            $tim = Tim::where('tahun', session('tahun_active'))->get();
            $tim_dict = [];
            foreach($tim as $item){
                $tim_dict[$item->id] = $item->nama;
            }

            foreach($jadwal_daftar as $item_daftar){
                Calendar_Google_Event::saveEvent($item_daftar, $service,  $calendar->google_calendar_id
                    , Auth::user()->username, $user_dict, $satuan_kerja_tahun_dict, $tim_dict);
            }
        }
    }
}

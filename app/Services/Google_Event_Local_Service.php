<?php

namespace App\Services;

use App\Model\Lopi\Jadwal;
use App\Model\Lopi\Jadwal_Peserta;
use App\Model\Lopi\Sync_Google\Calendar_Google_Event;
use App\Model\Lopi\Sync_Google\Calendar_User;
use App\Model\Umum\Peran;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\Tim;
use App\Model\Umum\User;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class Google_Event_Local_Service
{
    protected $client;
    protected $service;

    public function __construct()
    {
        $this->client = new Client();
        // It's best practice to use storage_path() or env() for credential files
        $this->client->setAuthConfig(base_path('lopiservice-48fe7bfdaa73.json'));
        $this->client->addScope(Calendar::CALENDAR);

        $this->service = new Calendar($this->client);
    }

    public function deleteEvent(Calendar_Google_Event $eventModel)
    {
        try {
            $this->service->events->delete($eventModel->google_calendar_id, $eventModel->event_id);
        } catch (\Exception $e) {
            // Consider logging the error here: \Log::error($e->getMessage());
        }

        $eventModel->delete();
    }

   public function saveEventPeserta(Jadwal $jadwal, $peserta_arr, $peserta_arr_req)
    {
        $peserta_ids = collect($peserta_arr)->pluck('peserta_id')->toArray();
        $peran = Peran::whereIn('parent_id', $peserta_ids)->get();

        $peserta_arr_full = array_merge(
            $peserta_ids,
            $peran->pluck('pemilik_username')->toArray()
        );

        $peserta_arr_full_uni = array_unique($peserta_arr_full);
        $calendar_user_add = Calendar_User::whereIn('username', $peserta_arr_full_uni)->get();

        $calendar_google_event = Calendar_Google_Event::where('parent_id', $jadwal->id)->get();

        $this->client->setUseBatch(true);
        $batch = $this->service->createBatch();;

        $i = 0;
        foreach ($calendar_google_event as $item) {
            try {
                $delRequest = $this->service->events->delete($item->google_calendar_id, $item->event_id);
                $batch->add($delRequest, "del_$i");
                $i++;
            } catch (\Exception $e) {}
        }

        Calendar_Google_Event::where('parent_id', $jadwal->id)->delete();

        $insert_keys = [];


        if(count($peserta_arr) > 0) {
            $event_obj = $this->buildEvent($jadwal, $peserta_arr_req);
            $i = 0;
            foreach ($calendar_user_add as $item) {
                $insRequest = $this->service->events->insert(
                    $item->google_calendar_id,
                    $event_obj
                );
                $batchKey = "ins_$i";
                $batch->add($insRequest, $batchKey);

                $insert_keys[$batchKey] = [
                    'username' => $item->username,
                    'calendar_id' => $item->google_calendar_id,
                    'jadwal_id' => $jadwal->id,
                ];
                $i++;
            }
        }

        $results = $batch->execute();
        $this->client->setUseBatch(false);

        foreach ($results as $responseId => $response) {
            $requestId = str_replace('response-', '', $responseId);

            if (!array_key_exists($requestId, $insert_keys)) {
                continue;
            }

            if ($response instanceof \Google\Service\Exception) {
                // Consider logging API exceptions here
                continue;
            }

            $info = $insert_keys[$requestId];
            $event_id = $response->id;

            Calendar_Google_Event::create([
                'id' => Str::uuid(),
                'parent_tipe' => 'jadwal',
                'parent_id' => $jadwal->id,
                'google_calendar_id' => $info['calendar_id'],
                'event_id' => $event_id,
                'username' => $info['username'],
            ]);
        }
    }

    private function buildEvent(Jadwal $jadwal, $peserta_arr_req)
    {
        $zoom_url = '';
        if ($jadwal->zoom_langsung_join_url) {
            $zoom_url = "\nLink Zoom : \n" . $jadwal->zoom_langsung_join_url. "\n";
        } else if ($jadwal->zoom_pinjam) {
            $zoom_url = "\nLink Zoom : \n" . $jadwal->zoom_pinjam->zoom_join_url. "\n";
        }

        $description = '';
        if($jadwal->description){
            $description = $jadwal->description. "\n";
        }

        $peserta = "";
        if (count($peserta_arr_req) > 0) {
            $peserta = "Daftar Peserta : \n";
            foreach ($peserta_arr_req as $item_peserta) {
                $peserta .= "- " . $item_peserta['peserta_nama'] . "\n";
            }
        }

        $tanggal_mulai = $jadwal->tanggal ?: $jadwal->berulang_tanggal_awal;
        $tanggal_akhir = $jadwal->tanggal ?: $jadwal->berulang_tanggal_akhir;

        $start = Carbon::parse("{$tanggal_mulai} {$jadwal->waktu_awal}", 'Asia/Makassar');
        $end = Carbon::parse("{$tanggal_akhir} {$jadwal->waktu_akhir}", 'Asia/Makassar');

        $event = new Event([
            'summary' => $jadwal->kegiatan,
            'location' => $jadwal->ruang ?: '',
            'description' => $description. $zoom_url . $peserta,
            'start' => [
                'dateTime' => $start->toRfc3339String(),
                'timeZone' => 'UTC',
            ],
            'end' => [
                'dateTime' => $end->toRfc3339String(),
                'timeZone' => 'UTC',
            ],
        ]);

        if ($jadwal->tipe == 'berulang') {
            $untilDate = Carbon::parse("{$jadwal->berulang_tanggal_akhir} {$jadwal->waktu_akhir}")
                            ->setTimezone('UTC')
                            ->format('Ymd\THis\Z');

            $rule = "RRULE:FREQ=DAILY;UNTIL={$untilDate}";
            $event->setRecurrence([$rule]);
        }

        return $event;
    }

    public function create_calender(User $user){
        $user_calender = Calendar_User::where('username', $user->username)->first();

        if(!$user_calender){
            Log::alert($user->username);
            $calendar = new \Google\Service\Calendar\Calendar([
                'summary' => 'Lopi - '.$user->nama,
                'timeZone' => 'Asia/Makassar',
            ]);

            $created = $this->service->calendars->insert($calendar);

            $rule = new \Google\Service\Calendar\AclRule([
                'scope' => [
                    'type' => 'default',
                ],
                'role' => 'reader',
            ]);

            $this->service->acl->insert($created->getId(), $rule);

            $calendar = new Calendar_User();
            $calendar->id = Str::uuid();
            $calendar->google_calendar_id = $created->getId();
            $calendar->username = $user->username;
            $calendar->save();

            $parent_id_arr_before_pluck = Peran::where('pemilik_username', $user->username)
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
                ->whereHas('peserta', function (Builder $query) use ($parent_id, $user){
                        $query->orWhereIn('peserta_id', $parent_id);
                        $query->orWhere('peserta_id', $user->username);
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
                $peserta_list = Jadwal_Peserta::with(['satuan_kerja_tahun', 'tim', 'user'])->where('jadwal_id', $item_daftar->id)->get();
                $peserta_arrr = [];
                $pesertaWithName = [];

                foreach($peserta_list as $item){
                    array_push($peserta_arrr, $item);
                    if($item->peserta_type == 'satuan_kerja_tahun' && $item->satuan_kerja_tahun){
                        array_push($pesertaWithName, ['peserta_id' => $item->peserta_id, 'peserta_nama' => $item->satuan_kerja_tahun->nama]);
                    }else if($item->peserta_type == 'tim' && $item->tim){
                        array_push($pesertaWithName, ['peserta_id' => $item->peserta_id, 'peserta_nama' => $item->tim->nama]);
                    }else if($item->peserta_type == 'user' && $item->user){
                        array_push($pesertaWithName, ['peserta_id' => $item->peserta_id, 'peserta_nama' => $item->user->nama]);
                    }
                }
                $this->saveEventPeserta(
                    $item_daftar,
                    $peserta_arrr,
                    $pesertaWithName
                );
            }
            sleep(12);
            return $calendar->google_calendar_id;
        }
    }

    public function create_calender_7600(){
        $user_all = User::all();

        foreach($user_all as $item_user){
            $this->create_calender($item_user);
        }

    }
}

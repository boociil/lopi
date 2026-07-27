<?php

namespace App\Services;

use App\Model\Lopi\Jadwal;
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
use Google\Http\Batch;
use Illuminate\Support\Str;

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

   public function saveEventPeserta(Jadwal $jadwal, $peserta_arr)
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
        $batch = new Batch($this->client);

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
            $user_dict = User::getDataDict();
            $satuan_kerja_tahun_dict = Satuan_Kerja_Tahun::getDataDict();
            $tim_dict = Tim::getDataDict();

            $event_obj = $this->buildEvent($jadwal, $peserta_arr, $user_dict, $satuan_kerja_tahun_dict, $tim_dict);

            foreach ($calendar_user_add as $item) {
                $insRequest = $this->service->events->insert(
                    $item->google_calendar_id,
                    $event_obj,
                    ['fields' => 'id']
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

    private function buildEvent(Jadwal $jadwal, $peserta_arr, $user_dict, $satuan_kerja_tahun_dict, $tim_dict)
    {
        $zoom_url = '';
        if ($jadwal->zoom_langsung_join_url) {
            $zoom_url = "\nLink Zoom : \n" . $jadwal->zoom_langsung_join_url;
        } else if ($jadwal->zoom_pinjam) {
            $zoom_url = "\nLink Zoom : \n" . $jadwal->zoom_pinjam->zoom_join_url;
        }

        $peserta = "";
        if (count($peserta_arr) > 0) {
            $peserta = "\n\nDaftar Peserta : \n";
            foreach ($peserta_arr as $item_peserta) {
                if (array_key_exists($item_peserta->peserta_id, $satuan_kerja_tahun_dict)) {
                    $peserta .= "- " . $satuan_kerja_tahun_dict[$item_peserta->peserta_id] . "\n";
                } else if (array_key_exists($item_peserta->peserta_id, $tim_dict)) {
                    $peserta .= "- " . $tim_dict[$item_peserta->peserta_id] . "\n";
                } else if (array_key_exists($item_peserta->peserta_id, $user_dict)) {
                    $peserta .= "- " . $user_dict[$item_peserta->peserta_id] . "\n";
                } else {
                    $peserta .= "- \n";
                }
            }
        }

        $tanggal_mulai = $jadwal->tanggal ?: $jadwal->berulang_tanggal_awal;
        $tanggal_akhir = $jadwal->tanggal ?: $jadwal->berulang_tanggal_akhir;

        $start = Carbon::parse("{$tanggal_mulai} {$jadwal->waktu_awal}", 'Asia/Makassar');
        $end = Carbon::parse("{$tanggal_akhir} {$jadwal->waktu_akhir}", 'Asia/Makassar');

        $event = new Event([
            'summary' => $jadwal->kegiatan,
            'location' => $jadwal->ruang ?: '',
            'description' => $jadwal->description . "\n" . $zoom_url . $peserta,
            'start' => [
                'dateTime' => $start->toRfc3339String(),
                'timeZone' => 'Asia/Makassar',
            ],
            'end' => [
                'dateTime' => $end->toRfc3339String(),
                'timeZone' => 'Asia/Makassar',
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
}

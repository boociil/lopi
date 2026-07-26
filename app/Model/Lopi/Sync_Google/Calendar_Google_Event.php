<?php

namespace App\Model\Lopi\Sync_Google;

use App\Model\Lopi\Jadwal;
use App\Model\Umum\Peran;
use App\Model\Umum\Satuan_Kerja_Tahun;
use App\Model\Umum\Tim;
use App\Model\Umum\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Google\Service\Calendar\Event;
use Illuminate\Support\Str;

class Calendar_Google_Event extends Model
{
    protected $table = 'siakip2_calendar_google_event';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['id', 'google_calendar_id', 'event_tipe', 'event_id', 'username'];

    public function deleteEvent()
    {
        $client = new \Google\Client();
        $client->setAuthConfig('lopiservice-48fe7bfdaa73.json');
        $client->addScope(\Google\Service\Calendar::CALENDAR);

        $service = new \Google\Service\Calendar($client);

        try {
            $service->events->delete($this->google_calendar_id, $this->event_id);
        } catch (\Exception $e) {

        }
        $this->delete();
    }

    public static function saveEventPeserta(Jadwal $jadwal, $peserta_arr)
    {
        $client = new \Google\Client();
        $client->setAuthConfig('lopiservice-48fe7bfdaa73.json');
        $client->addScope(\Google\Service\Calendar::CALENDAR);

        $service = new \Google\Service\Calendar($client);

        $peserta_ids = collect($peserta_arr)->pluck('peserta_id')->toArray();
        $peran = Peran::whereIn('parent_id', $peserta_ids)->get();

        $peserta_arr_full = array_merge(
            $peserta_ids,
            $peran->pluck('pemilik_username')->toArray()
        );

        $peserta_arr_full_uni = array_unique($peserta_arr_full);

        $calendar_user_add = Calendar_User::whereIn('username', $peserta_arr_full_uni)->get();

        $calendar_google_event = Calendar_Google_Event::where('event_id', $jadwal->id)->get();

        $client->setUseBatch(true);
        $batch = new \Google\Http\Batch($client);

        $i = 0;
        foreach ($calendar_google_event as $item) {
            try {
                $delRequest = $service->events->delete($item->google_calendar_id, $item->event_id);
                $batch->add($delRequest, "del_$i");
                $i++;
            } catch (\Exception $e) {}
        }

        if(count($peserta_arr) == 0){
            return;
        }

        $user_dict = User::getDataDict();
        $satuan_kerja_tahun_dict = Satuan_Kerja_Tahun::getDataDict();
        $tim_dict = Tim::getDataDict();

        $event_obj = self::build_event($jadwal, $peserta_arr, $user_dict, $satuan_kerja_tahun_dict, $tim_dict);
        Calendar_Google_Event::where('event_id', $jadwal->id)->delete();
        $insert_keys = [];

        foreach ($calendar_user_add as $item) {
            $insRequest = $service->events->insert(
                $item->google_calendar_id,
                $event_obj,
                ['fields' => 'id']
            );
            $batchKey = "ins_$i";
            $batch->add($insRequest, $batchKey);

            $insert_keys[$batchKey] = [
                'username' => $item->username,
                'calendar_id' => $item->google_calendar_id,
                'jadwal_id' => $jadwal->id
            ];
            $i++;
        }

        $results = $batch->execute();

        foreach ($results as $responseId => $response) {
            $requestId = str_replace('response-', '', $responseId);

            if (!array_key_exists($requestId, $insert_keys)) {
                continue;
            }

            if ($response instanceof \Google\Service\Exception) {
                continue;
            }

            $info = $insert_keys[$requestId];
            $google_event_id = $response->id;

            $calendar_Google_Event = new Calendar_Google_Event();
            $calendar_Google_Event->id = Str::uuid();
            $calendar_Google_Event->google_calendar_id = $info['calendar_id'];
            $calendar_Google_Event->event_tipe = 'jadwal';
            $calendar_Google_Event->event_id = $google_event_id;
            $calendar_Google_Event->username = $info['username'];

            $calendar_Google_Event->save();

            Jadwal::where('id', $info['jadwal_id'])->update([
                'google_event_id' => $google_event_id,
            ]);

            echo "Calendar : {$info['calendar_id']}\n";
            echo "Google Event : {$google_event_id}\n";
        }
    }

    public static function build_event(Jadwal $jadwal, $peserta_arr, $user_dict, $satuan_kerja_tahun_dict, $tim_dict)
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
            $untilDate = Carbon::parse($jadwal->berulang_tanggal_akhir)
                            ->setTimezone('UTC')
                            ->format('Ymd\THis\Z');

            $rule = "RRULE:FREQ=DAILY;UNTIL={$untilDate}";
            $event->setRecurrence([$rule]);
        }
        return $event;
    }
}

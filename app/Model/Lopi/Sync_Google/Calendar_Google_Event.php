<?php

namespace App\Model\Lopi\Sync_Google;

use App\Model\Lopi\Jadwal;
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

    public static function saveEvent(Jadwal $jadwal, $service, $calender_id, $username, $user_dict, $satuan_kerja_tahun_dict, $tim_dict)
    {
        $zoom_url = '';
        if($jadwal->zoom_langsung_join_url){
            $zoom_url = "\nLink Zoom : \n".$jadwal->zoom_langsung_join_url;
        }else if($jadwal->zoom_pinjam){
            $zoom_url =  "\nLink Zoom : \n".$jadwal->zoom_pinjam->zoom_join_url;
        }

        $peserta = "";
        if (count($jadwal->peserta) > 0){
            $peserta = "\n\nDaftar Peserta : \n";
            foreach($jadwal->peserta as $item_peserta){
                if(array_key_exists($item_peserta->peserta_id, $satuan_kerja_tahun_dict)){
                    $peserta .= "- ".$satuan_kerja_tahun_dict[$item_peserta->peserta_id]."\n";
                }else if(array_key_exists($item_peserta->peserta_id, $tim_dict)){
                    $peserta .=  "- ".$tim_dict[$item_peserta->peserta_id]."\n";
                }else if(array_key_exists($item_peserta->peserta_id, $user_dict)){
                    $peserta .=  "- ".$user_dict[$item_peserta->peserta_id]."\n";
                }else {
                    $peserta .= "- \n";
                }

            }
        }


        $start = Carbon::parse(
            "{($jadwal->tanggal) ? $jadwal->tanggal :  $jadwal->berulang_tanggal_awal} {$jadwal->waktu_awal}",
            'Asia/Makassar'
        );

        $end = Carbon::parse(
            "{($jadwal->tanggal) ? $jadwal->tanggal :  $jadwal->berulang_tanggal_akhir} {$jadwal->waktu_akhir}",
            'Asia/Makassar'
        );

        $event = new Event([
            'summary' => $jadwal->kegiatan,
            'location' => ($jadwal->ruang) ? $jadwal->ruang : '',
            'description' => $jadwal->description."\n".$zoom_url.$peserta,
            'start' => [
                'dateTime' => $start->toRfc3339String(),
                'timeZone' => 'Asia/Makassar',
            ],
            'end' => [
                'dateTime' => $end->toRfc3339String(),
                'timeZone' => 'Asia/Makassar',
            ],
        ]);

        /*
         * Recurring Event
         */
         if ($jadwal->tipe == 'berulang') {
            $rule = "RRULE:FREQ=DAILY";
            $start_date = Carbon::parse($jadwal->berulang_tanggal_awal);
            $end_date = Carbon::parse($jadwal->berulang_tanggal_akhir);
            $daysUntil = $end_date->diffInDays($start_date, true);
            $rule .= ";UNTIL={$daysUntil}";
            $event->setRecurrence([$rule]);
        }

        $createdEvent = $service->events->insert(
            $calender_id,
            $event
        );

        $calendar_Google_Event = new Calendar_Google_Event();
        $calendar_Google_Event->id = Str::uuid();
        $calendar_Google_Event->google_calendar_id = $calender_id;
        $calendar_Google_Event->event_tipe = 'jadwal';
        $calendar_Google_Event->event_id = $createdEvent->id();
        $calendar_Google_Event->username = $username;
        $calendar_Google_Event->save();
        return $event;
    }

}

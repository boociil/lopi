import { format, parseISO } from "date-fns";
import { formatDate, formatTime } from "@/components/calender_modules/helpers";
import { IEvent, IUser } from "@/components/calender_modules/interfaces";
import { User } from "@/types";
import { v4 as uuidv4 } from "uuid";
import { TEventColor } from "@/components/calender_modules/types";

export type PinjamZoomProps = {
    pinjam_zoom: pinjam_zoom[];
    user: User[];
    havePinjamMovilRole: boolean;
};

export type PinjamZoomContextType = PinjamZoomProps & {
    eventDialog: pinjam_zoom_dialog_event;
    setEventDialog: (eventDialog: pinjam_zoom_dialog_event) => void;
};


export interface zoom_app {
    id: number;
    type: number;
    topic: string;
    start_time: string;
    duration: number;
    join_url: string;
}

export interface zoom_app_with_status {
    status: boolean;
    data: zoom_app[];
}

export interface pinjam_zoom {
    id: string;
    kegiatan: string;
    password: string;
    tipe: string;
    tanggal: string | null;
    berulang_tanggal_awal: string | null;
    berulang_tanggal_akhir: string | null;
    waktu_awal: string;
    waktu_akhir: string;
    status: "menunggu" | "disetujui" | "ditolak" | "diapp";
    pembuat_username: string;
    confirm_username: string | null;
    zoom_akun: 'statsulbar' | 'sp2020' | null;
    zoom_meeting_id: string | null;
    zoom_start_url: string | null;
    zoom_join_url: string | null;
    jadwal_id: string | null;
}

export interface pinjam_zoom_req {
    id: string;
    kegiatan: string;
    password: string;
    tipe: string;
    tanggal: string | null;
    tanggal_awal: string | null;
    tanggal_akhir: string | null;
    waktu_awal: string;
    waktu_akhir: string;
    status: "menunggu" | "disetujui" | "ditolak" | "diapp";
    pembuat_username: string;
    confirm_username: string | null;
    zoom_akun: 'statsulbar' | 'sp2020' | null;
    error_clash: string;
}

export interface PinjamZoomIEvent extends IEvent {
    pinjam_zoom_ori: pinjam_zoom | null;
    zoom_akun: 'statsulbar' | 'sp2020' | null;
    zoom_meeting_id: string | null;
    join_url: string | null;
    start_url: string | null;
}

export interface pinjam_zoom_dialog_event {
    status: null | "lihat" | "tambah" | "ubah" | "hapus" | "approval";
    data: pinjam_zoom | null;
}

export function producePinjamZoomIEvent(
    pinjam_zoom: pinjam_zoom[],
): PinjamZoomIEvent[] {
    const retur_data: PinjamZoomIEvent[] = [];
    pinjam_zoom.forEach((element) => {
        if (element.status != "ditolak") {
            let tanggal: string = "";
            let tanggal_awal: string = "";
            let tanggal_akhir: string = "";

            if (element.tipe == "sekali" && element.tanggal) {
                tanggal = element.tanggal;
                tanggal_awal = element.tanggal;
                tanggal_akhir = element.tanggal;
            } else if (element.tipe == "berulang") {
                if (
                    element.berulang_tanggal_awal &&
                    element.berulang_tanggal_akhir
                ) {
                    tanggal = element.berulang_tanggal_awal;
                    tanggal_awal = element.berulang_tanggal_awal;
                    tanggal_akhir = element.berulang_tanggal_akhir;
                }
            }

            retur_data.push({
                id: element.id,
                startDate: `${tanggal_awal}T${element.waktu_awal}`,
                endDate: `${tanggal_akhir}T${element.waktu_akhir}`,
                date: tanggal,
                _startDate: tanggal_awal,
                _endDate: tanggal_akhir,
                _startTime: element.waktu_awal,
                _endTime: element.waktu_akhir,
                title: element.kegiatan,
                color: element.status == "disetujui" ? element.zoom_akun == 'statsulbar' ? "green" : "yellow" : "purple",
                description: "Peminjaman Zoom",
                user: [],
                zoom_akun: element.zoom_akun as 'statsulbar' | 'sp2020' | null,
                zoom_meeting_id: element.zoom_meeting_id,
                pinjam_zoom_ori: element,
                join_url: element.zoom_join_url,
                start_url: element.zoom_start_url,
                oriKey: 'pinjam_zoom_ori'
            });
        }
    });
    return retur_data;
}

export function producePinjamZoomIEventFromZoomApp(
    awal: PinjamZoomIEvent[],
    pinjam_zoom: zoom_app[],
    zoom_akun: 'statsulbar' | 'sp2020' | null,
): void {
    const zoom_meeting_id_s: string[] = [];
    awal.forEach((element) => {
        if (element.pinjam_zoom_ori?.zoom_meeting_id) {
            zoom_meeting_id_s.push(element.pinjam_zoom_ori?.zoom_meeting_id);
        }
    });

    const pinjam_zoom_filter = pinjam_zoom.filter((item) => {
        if (!zoom_meeting_id_s.includes(''+item.id)) {
            return true;
        } else {
            return false;
        }
    });

    const retur_data: PinjamZoomIEvent[] = [];

    pinjam_zoom_filter.forEach((element) => {
        let ada = false;
        for (let i = 0; i < retur_data.length; i++) {
            if (retur_data[i].pinjam_zoom_ori?.zoom_meeting_id == ''+element.id) {
                ada = true;
                const date_awal = new Date(element.start_time);
                const date_akhir = new Date(
                    date_awal.getTime() + element.duration * 60000,
                );

                retur_data[i].endDate = format(date_akhir, "yyyy-MM-dd HH:mm");
                retur_data[i]._endDate = formatDate(date_akhir);
            }
        }

        if (!ada) {
            const date_awal = new Date(element.start_time);
            const date_akhir = new Date(
                date_awal.getTime() + element.duration * 60000,
            );

            let color: TEventColor = "purple"
            if(zoom_akun == 'statsulbar'){
                color='green'
            }else if(zoom_akun == 'sp2020'){
                color='yellow'
            }

            if(formatDate(date_awal) == formatDate(date_akhir)){
                 retur_data.push({
                    id: uuidv4(),
                    startDate: format(date_awal, "yyyy-MM-dd HH:mm"),
                    endDate: format(date_akhir, "yyyy-MM-dd HH:mm"),
                    date: formatDate(date_awal),
                    _startDate: formatDate(date_awal),
                    _endDate: formatDate(date_akhir),
                    _startTime: formatTime(date_awal, true),
                    _endTime: formatTime(date_akhir, true),
                    title: element.topic,
                    color:  color,
                    description: "Peminjaman Zoom",
                    user: [],
                    zoom_akun: zoom_akun,
                    zoom_meeting_id:  ''+element.id,
                    pinjam_zoom_ori: null,
                    join_url: element.join_url,
                    start_url: null,
                     oriKey: 'pinjam_zoom_ori'
                });
            }else{
                const satu_startDate: Date = parseISO(format(date_awal, "yyyy-MM-dd HH:mm"))
                const satu_endDate: Date = parseISO(`${formatDate(date_awal)}T23:59`)
                const dua_startDate: Date = parseISO(`${formatDate(date_akhir)}T00:00`)
                const dua_endDate: Date = parseISO(format(date_akhir, "yyyy-MM-dd HH:mm"))

                retur_data.push({
                    id: uuidv4(),
                    startDate: format(satu_startDate, "yyyy-MM-dd HH:mm"),
                    endDate: format(satu_endDate, "yyyy-MM-dd HH:mm"),
                    date: formatDate(satu_startDate),
                    _startDate: formatDate(satu_startDate),
                    _endDate: formatDate(satu_endDate),
                    _startTime: formatTime(satu_startDate, true),
                    _endTime: formatTime(satu_endDate, true),
                    title: element.topic,
                    color:  color,
                    description: "Peminjaman Zoom",
                    user: [],
                    zoom_akun: zoom_akun,
                    zoom_meeting_id: ''+ element.id,
                    pinjam_zoom_ori: null,
                    join_url: element.join_url,
                    start_url: null,
                     oriKey: 'pinjam_zoom_ori'
                });

                retur_data.push({
                    id: uuidv4(),
                    startDate: format(dua_startDate, "yyyy-MM-dd HH:mm"),
                    endDate: format(dua_endDate, "yyyy-MM-dd HH:mm"),
                    date: formatDate(dua_startDate),
                    _startDate: formatDate(dua_startDate),
                    _endDate: formatDate(dua_endDate),
                    _startTime: formatTime(dua_startDate, true),
                    _endTime: formatTime(dua_endDate, true),
                    title: element.topic,
                    color:  color,
                    description: "Peminjaman Zoom",
                    user: [],
                    zoom_akun: zoom_akun,
                    zoom_meeting_id:  ''+element.id,
                    pinjam_zoom_ori: null,
                    join_url: element.join_url,
                    start_url: null,
                    oriKey: 'pinjam_zoom_ori'
                });

            }


        }
    });

    awal.push(...retur_data);
}

export function pairIEventDenganUser(
    pinjam_zoom_event: PinjamZoomIEvent[],
    users: IUser[],
) {
    for (let i = 0; i < pinjam_zoom_event.length; i++) {
        const user = users.find(
            (e) =>
                e.id ==
                pinjam_zoom_event[i].pinjam_zoom_ori?.pembuat_username,
        );
        if (user) {
            pinjam_zoom_event[i].user = [user]
        }
    }
}

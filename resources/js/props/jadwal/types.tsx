import { format, parseISO } from "date-fns";
import { formatDate, formatTime } from "@/components/calender_modules/helpers";
import { CalenderUser, IEvent } from "@/components/calender_modules/interfaces";
import { User } from "@/types";
import { v4 as uuidv4 } from "uuid";
import { TEventColor } from "@/components/calender_modules/types";


export type JadwalProps = {
    jadwal: jadwal[];
    user: User[];
    havePinjamMovilRole: boolean;
};

export type JadwalContextType = JadwalProps & {
    eventDialog: jadwal_dialog_event;
    setEventDialog: (eventDialog: jadwal_dialog_event) => void;
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

export interface jadwal {
    id: string;
    kegiatan: string;
    description: string;

    tipe: string;
    tanggal: string | null;
    berulang_tanggal_awal: string | null;
    berulang_tanggal_akhir: string | null;
    waktu_awal: string;
    waktu_akhir: string;

    status: "menunggu" | "disetujui" | "ditolak";
    pembuat_username: string;

    url_daftar_hadir: string | null;
    url_notulensi: string | null;
    url_dokumentasi: string | null;
    url_undangan: string | null;

    ruang_status: 'tidak' | 'menunggu' | 'disetujui' | 'ditolak';
    ruang: string | null;
    ruang_confirm_username: string | null;

    zoom_status: 'tidak' | 'langsung' | 'menunggu' | 'disetujui' | 'ditolak';
    zoom_password: string | null;
    zoom_confirm_username: string | null;
    zoom_akun: 'statsulbar' | 'sp2020' | null;
    zoom_meeting_id: number | null;
    zoom_start_url: string | null;
    zoom_join_url: string | null;
}

export interface jadwal_req {
    id: string;
    kegiatan: string;

    tipe: string;
    tanggal: string | null;
    tanggal_awal: string | null;
    tanggal_akhir: string | null;
    waktu_awal: string;
    waktu_akhir: string;
    status: "menunggu" | "disetujui" | "ditolak";

    ruang_menggunakan: 0 | 1;
    ruang: string | null;

    zoom_menggunakan:  0 | 1;
    zoom_password: string | null;
    zoom_akun: 'statsulbar' | 'sp2020' | null;
    zoom_join_url_langsung: string | null;
}

export interface JadwalIEvent extends IEvent {
    jadwal_ori: jadwal | null;
    zoom_akun: 'statsulbar' | 'sp2020' | null;
    join_url: string | null;
    start_url: string | null;
}

export interface jadwal_dialog_event {
    status: null | "lihat" | "tambah" | "ubah" | "hapus" | "approval";
    data: jadwal | null;
}

export function produceJadwalIEvent(
    jadwal: jadwal[],
): JadwalIEvent[] {
    const retur_data: JadwalIEvent[] = [];
    jadwal.forEach((element) => {
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
                color: element.status == "disetujui" ? "green" : "blue",
                description: "Peminjaman Zoom",
                user: null,
                zoom_akun: element.zoom_akun as 'statsulbar' | 'sp2020' | null,
                jadwal_ori: element,
                join_url: element.zoom_join_url,
                start_url: element.zoom_start_url,
                oriKey: 'jadwal_ori',
            });
        }
    });
    return retur_data;
}


export function pairIEventDenganUser(
    pinjam_zoom_event: JadwalIEvent[],
    users: CalenderUser[],
) {
    for (let i = 0; i < pinjam_zoom_event.length; i++) {
        const user = users.find(
            (e) =>
                e.username ==
                pinjam_zoom_event[i].jadwal_ori?.pembuat_username,
        );
        if (user) {
            pinjam_zoom_event[i].user = user;
        }
    }
}

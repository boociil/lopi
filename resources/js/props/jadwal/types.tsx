import { CalenderUser, IEvent, SettingCalender } from "@/components/calender_modules/interfaces";
import { User } from "@/types";
import { pinjam_zoom } from "../pinjam-zoom/types";


export type JadwalProps = {
    jadwal: jadwal[];
    user: User[];
    havePinjamMovilRole: boolean;
};

export type JadwalContextType = JadwalProps & {
    eventDialog: jadwal_dialog_event;
    setEventDialog: (eventDialog: jadwal_dialog_event) => void;
};



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
    zoom_pinjam_id :  null | string;
    zoom_langsung_join_url: null | string;

    zoom_pinjam: pinjam_zoom | null;
}

export interface jadwal_req {
    id: string;
    kegiatan: string;
    description: string;

    tipe: string;
    tanggal: string | null;
    tanggal_awal: string | null;
    tanggal_akhir: string | null;
    waktu_awal: string;
    waktu_akhir: string;
    status: "menunggu" | "disetujui" | "ditolak";

    ruang_status: 'tidak' | 'menunggu' | 'disetujui' | 'ditolak';
    ruang: string | null;

    zoom_status: 'tidak' | 'langsung' | 'menunggu' | 'disetujui' | 'ditolak';
    zoom_langsung_join_url: null | string;
    zoom_password:  null | string;
    zoom_akun: 'statsulbar' | 'sp2020' | null;
    peserta: string [],
}

export interface JadwalIEvent extends IEvent {
    jadwal_ori: jadwal;
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
                jadwal_ori: element,
                oriKey: 'jadwal_ori',
            });
        }
    });
    return retur_data;
}


export function pairIEventDenganUser(
    jadwal_event: JadwalIEvent[],
    users: CalenderUser[],
) {
    for (let i = 0; i < jadwal_event.length; i++) {
        const user = users.find(
            (e) =>
                e.username ==
                jadwal_event[i].jadwal_ori?.pembuat_username,
        );
        if (user) {
            jadwal_event[i].user = user;
        }
    }
}

export interface SettingCalenderJadwal extends SettingCalender {
    ruang: string[],
}

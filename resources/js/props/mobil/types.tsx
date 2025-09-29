import { IEvent, IUser } from "@/components/calender_modules/interfaces"
import { User } from "@/types"

export type PinjamMobilProps = {
  pinjam_mobil: pinjam_mobil[]
  user: User[],
  havePinjamMovilRole: boolean
}

export type PinjamMobilContextType = PinjamMobilProps & {
  eventDialog: pinjam_mobil_dialog_event
  setEventDialog: (eventDialog: pinjam_mobil_dialog_event) => void
}


export interface pinjam_mobil {
    id: string;
    kegiatan: string;
    tipe: string;
    berulang_id: string | null;
    tanggal: string;
    waktu_awal: string;
    waktu_akhir: string;
    status: number;
    pembuat_username: string;
    confirm_username: string | null;
}


export interface PinjamMobilIEvent extends IEvent{
    pinjam_mobil_ori: pinjam_mobil;
}



export interface pinjam_mobil_dialog_event {
    status: null | 'lihat'| 'tambah' | 'ubah' | 'hapus' | 'approval';
    data: pinjam_mobil | null;
}

export function producePinjamMobilIEvent(pinjam_mobil: pinjam_mobil[]): PinjamMobilIEvent[]{
    const retur_data: PinjamMobilIEvent[] = []
    pinjam_mobil.forEach(element => {
        retur_data.push({
            id: element.id,
            repeat_id: element.berulang_id,
            startDate: `${element.tanggal}T${element.waktu_awal}`,
            endDate: `${element.tanggal}T${element.waktu_akhir}`,
            date: element.tanggal,
            _startDate: element.tanggal,
            _endDate: element.tanggal,
            _startTime: element.waktu_awal,
            _endTime: element.waktu_akhir,
            title: element.kegiatan,
            color: "orange",
            description: "Peminjaman Mobil",
            user: null,
            pinjam_mobil_ori: element,
        })
    });

    return retur_data
}


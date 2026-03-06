import { CalenderUser, IEvent } from "@/components/calender_modules/interfaces"
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
    tanggal: string | null;
    berulang_tanggal_awal: string | null;
    berulang_tanggal_akhir: string | null;
    waktu_awal: string;
    waktu_akhir: string;
    status: 'menunggu' | 'disetujui' | 'ditolak';
    pembuat_username: string;
    confirm_username: string | null;
}

export interface pinjam_mobil_req {
    id: string;
    kegiatan: string;
    tipe: string;
    tanggal: string | null;
    tanggal_awal: string | null;
    tanggal_akhir: string | null;
    waktu_awal: string;
    waktu_akhir: string;
    status: 'menunggu' | 'disetujui' | 'ditolak';
    pembuat_username: string;
    confirm_username: string | null;
    error_clash: string;
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
        if(element.status != 'ditolak'){
            let tanggal: string =  ''
            let tanggal_awal: string = ''
            let tanggal_akhir: string = ''

            if(element.tipe == 'sekali' && element.tanggal){
                tanggal = element.tanggal
                tanggal_awal = element.tanggal
                tanggal_akhir = element.tanggal
            }else if(element.tipe == 'berulang'){
                if(element.berulang_tanggal_awal && element.berulang_tanggal_akhir){
                    tanggal = element.berulang_tanggal_awal
                    tanggal_awal = element.berulang_tanggal_awal
                    tanggal_akhir = element.berulang_tanggal_akhir
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
                color: element.status == 'disetujui' ?  "green" : "blue",
                description: "Peminjaman Mobil",
                user: null,
                pinjam_mobil_ori: element,
                oriKey: 'pinjam_mobil_ori'
            })
        }

    });
    return retur_data
}

export function pairIEventDenganUser(pinjam_mobil_event: PinjamMobilIEvent[], users: CalenderUser[]){
    for(let i=0; i<pinjam_mobil_event.length; i++){
        const user = users.find(e => e.username == pinjam_mobil_event[i].pinjam_mobil_ori.pembuat_username)
        if(user){
            pinjam_mobil_event[i].user = user
        }
    }
}


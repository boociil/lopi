import { CalenderUser, IEvent } from "@/components/calender_modules/interfaces"
import { TEventColor } from "@/components/calender_modules/types"
import { User } from "@/types"

export type PinjamRuangProps = {
  pinjam_ruang: pinjam_ruang[]
  user: User[],
  havePinjamMovilRole: boolean
}

export type PinjamRuangContextType = PinjamRuangProps & {
  eventDialog: pinjam_ruang_dialog_event
  setEventDialog: (eventDialog: pinjam_ruang_dialog_event) => void
}


export interface pinjam_ruang {
    id: string;
    kegiatan: string;
    ruang: string;
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

export interface pinjam_ruang_req {
    id: string;
    kegiatan: string;
    ruang: string;
    tipe: string;
    tanggal: string | null;
    tanggal_awal: string | null;
    tanggal_akhir: string | null;
    waktu_awal: string;
    waktu_akhir: string;
    status: 'menunggu' | 'disetujui' | 'ditolak';
    pembuat_username: string;
    confirm_username: string | null;
}

export interface PinjamRuangIEvent extends IEvent{
    ruang: string;
    pinjam_ruang_ori: pinjam_ruang;
}


export interface pinjam_ruang_dialog_event {
    status: null | 'lihat'| 'tambah' | 'ubah' | 'hapus' | 'approval';
    data: pinjam_ruang | null;
}

export function producePinjamRuangIEvent(pinjam_ruang: pinjam_ruang[]): PinjamRuangIEvent[]{
    const retur_data: PinjamRuangIEvent[] = []
    pinjam_ruang.forEach(element => {
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

            let color: TEventColor = 'blue'
            if(element.status == 'disetujui'){
                if(element.ruang == 'aula'){
                    color = 'green'
                }else if(element.ruang == 'pokja'){
                    color = 'yellow'
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
                color: color,
                description: "Peminjaman Ruang",
                user: null,
                ruang: element.ruang,
                pinjam_ruang_ori: element,
            })
        }

    });
    return retur_data
}

export function pairIEventDenganUser(pinjam_ruang_event: PinjamRuangIEvent[], users: CalenderUser[]){
    for(let i=0; i<pinjam_ruang_event.length; i++){
        const user = users.find(e => e.username == pinjam_ruang_event[i].pinjam_ruang_ori.pembuat_username)
        if(user){
            pinjam_ruang_event[i].user = user
        }
    }
}


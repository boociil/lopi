import { CalenderUser, IEvent } from "@/components/calender_modules/interfaces";
import { TEventColor } from "@/components/calender_modules/types";

export interface cuti {
    p_id: string;
    p_niplama: string;
    p_nmcuti: string;
    p_jmlcuti: string;
    p_tglmulai: string;
    p_tglselesai: string;
    p_statuslabel: string;
}

export interface cuti_req {

}

export interface CutiIEvent extends IEvent {
    cuti_ori: cuti;
}

export interface cuti_dialog_event {
    status: null | "lihat" | "tambah" | "ubah" | "hapus" | "approval";
    data: cuti | null;
}

export function produceCutiIEvent(
    cuti: cuti[],
    users: CalenderUser[],
): CutiIEvent[] {
    const colors = ["blue", "green", "red", "yellow", "purple", "orange"];

    const retur_data: CutiIEvent[] = [];
    cuti.forEach((element) => {
        if (element.p_statuslabel == "Selesai - Disetujui") {
            let tanggal: string = "";
            let tanggal_awal: string = "";
            let tanggal_akhir: string = "";

            const user = users.find(
                (e) =>
                    e.nip_lama ==
                    element.p_niplama,
            );


            tanggal = reformatDateString(element.p_tglmulai);
            tanggal_awal = reformatDateString(element.p_tglmulai);
            tanggal_akhir = reformatDateString(element.p_tglselesai);

            retur_data.push({
                id: element.p_id,
                startDate: `${tanggal_awal}T00:00:00`,
                endDate: `${tanggal_akhir}T23:59:59`,
                date: tanggal,
                _startDate: tanggal_awal,
                _endDate: tanggal_akhir,
                _startTime: '00:00:00',
                _endTime: '23:59:59',
                title: user ? user.nama : "",
                color:  colors[Math.floor(Math.random() * colors.length)] as TEventColor,
                description: element.p_nmcuti+' ('+element.p_jmlcuti+')',
                user: null,
                cuti_ori: element,
                oriKey: 'cuti_ori',
            });
        }
    });
    return retur_data;
}


export function pairIEventDenganUser(
    cuti_event: CutiIEvent[],
    users: CalenderUser[],
) {
    for (let i = 0; i < cuti_event.length; i++) {
        const user = users.find(
            (e) =>
                e.nip_lama ==
                cuti_event[i].cuti_ori?.p_niplama,
        );
        if (user) {
            cuti_event[i].user = user;
        }
    }
}


function reformatDateString(s: string) {
  // Split the string by the hyphen delimiter
  var b = s.split('-');
  // Reverse the array of parts (e.g., ["25", "12", "2014"] becomes ["2014", "12", "25"])
  // and join them back together with a hyphen
  return b.reverse().join('-');
}

import {  User } from "@/types";
import { AxiosInstance } from "axios";

export type PeranProps = {
    peranLopi: peran_lopi[];
    user: User[];
    satuanKerjaTahun: satuan_kerja_tahun[];
    userDict: Map<string, string>,
    satuanKerjaTahunDict: Map<string, string>,
};

export type PeranContextType = PeranProps & {
    eventDialog: peran_dialog_event;
    setEventDialog: (eventDialog: peran_dialog_event) => void;
    addPeran: (peran_lopi_: peran_lopi) => void;
    axiosClient: AxiosInstance;
};

export interface peran_lopi {
    id: string;
    tipe_peran: string;
    parent_id: string;
    pemilik_username: string;
    tahun: string;
    peran: string;
}

export interface satuan_kerja_tahun {
    id: string;
    nama: string;
    tahun: number;
    satuan_kerja_id: string;
    kode_satuan_kerja: string;
    type: string;
    parent_id: string | null;
    peran_daftar: peran_lopi[];
}

export interface peran_dialog_event {
    status: null | "tambah" | "hapus";
    data: peran_lopi | null;
}

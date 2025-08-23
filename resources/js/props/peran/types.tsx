import {  User } from "@/types";

export type PeranProps = {
    peranZoom: peran_lopi[];
    setPeranZoom: (peranZoom: peran_lopi[]) => void;
    user: User[];
    setUser: (user: User[]) => void;
    satuanKerjaTahun: satuan_kerja_tahun[];
    setSatuanKerjaTahun: (satuanKerjaTahun: satuan_kerja_tahun[]) => void;
    userDict: Map<string, string>,
    setUserDict: (userDict: Map<string, string>) => void;
    satuanKerjaTahunDict: Map<string, string>,
    setSatuanKerjaTahunDict: (satuanKerjaTahunDict: Map<string, string>) => void;
};

export type PeranContextType = PeranProps & {
    eventDialog: peran_dialog_event;
    setEventDialog: (eventDialog: peran_dialog_event) => void;
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
    status: null | "tambah";
    data: peran_lopi | null;
}

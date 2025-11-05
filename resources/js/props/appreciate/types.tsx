import {  User } from "@/types";
import { AxiosInstance } from "axios";

export type AppreciateMaterProps = {
    appreciateMaster: appreciate_master[];
    user: User[];
    userDict: Map<string, string>,
};

export type AppreciateMaterContextType = AppreciateMaterProps & {
    eventDialog: appreciate_master_dialog_event;
    setEventDialog: (eventDialog: appreciate_master_dialog_event) => void;
    addAppreciateMaster: (appreciate_master_: appreciate_master) => void;
    updateAppreciateMaster: (appreciate_master_: appreciate_master) => void;
    axiosClient: AxiosInstance;
};

export interface appreciate_master_dialog_event {
    status: null | "tambah"  | "ubah" | "hapus";
    data: appreciate_master | null;
}


export type AppreciateDataProps = {
    peranLopi: appreciate_master[];
    user: User[];
    userDict: Map<string, string>,
};

export type AppreciateDataContextType = AppreciateDataProps & {
    eventDialog: appreciate_data_dialog_event;
    setEventDialog: (eventDialog: appreciate_data_dialog_event) => void;
    addAppreciateData: (appreciate_data_: appreciate_data) => void;
    axiosClient: AxiosInstance;
};

export interface appreciate_data_dialog_event {
    status: null | "tambah" | "hapus";
    data: appreciate_data | null;
}


export interface appreciate_master {
    id: number;
    nama: string;
    isi_awal: string;
    isi_akhir: string;
    target: appreciate_target[];
}

export interface appreciate_target {
    id: string;
    master_id: string;
    username: string;
}

export interface appreciate_data {
    id: string;
    master_id: string;
    dari: string;
    tujuan: string;
    status: string;
    pesan: string;
}



export type ZoomProps = {
  zoom: zoom[]
  setZoom: (zoom: zoom[]) => void
}

export type ZoomContextType = ZoomProps & {
  eventDialog: zoom_dialog_event
  setEventDialog: (eventDialog: zoom_dialog_event) => void
  haveZoomRole: boolean
  setHaveZoomRole: (haveZoomRole: boolean) => void
}


export interface zoom {
    id: string | null;
    topic: string;
    password: string;
    start_time: string;
    duration: number;
    end_time: string;
    pembuat_username: string | null;
    pembuat_nama: string | null;
    zoom_status: number;
    zoom_akun: string | null;
    zoom_meeting_id: bigint | null;
    zoom_start_url: string | null;
    zoom_join_url: string | null;
    zoom_app_prop: zoom_app | null;
}

export interface zoom_app_with_status {
    status: boolean;
    data: {
        meetings: zoom_app[]
    }
}


export interface zoom_app {
    id: bigint;
    topic: string;
    start_time: string;
    start_time_penyesuaian: number;
    duration: number;
    zoom_join_url: string | null;
}

export interface zoom_dialog_event {
    status: null | 'tambah' | 'ubah' | 'hapus' | 'approval';
    data: zoom | null;
}

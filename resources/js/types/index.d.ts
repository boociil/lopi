import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGeneral {
    type: string; //group | item
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
    subItems?: NavItem[];
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    tahun_active: number;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    username: string;
    email: string;
    nip_baru: string;
    nip_lama: string;
    nama: string;
    jabatan: string;
    golonagan: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Peran {
    id: string;
    tipe_peran: string;
    parent_id: string?;
    pemilik_username: string?;
    pemilik_tim_id: string?;
    pemilik_satuan_kerja_tahun_id: string?;
    tahun: string;
    peran: string;
}

export interface zoom {
    topic: string;
    start_time: string;
    start_time_penyesuaian: number;
    pembuat_username: string?;
    zoom_status: number?;
    zoom_akun: string?;
    zoom_meeting_id: bigint?;
    zoom_start_url: string?;
    zoom_join_url: string?;
    zoom_app_prop: any?;
    zoom_int_prop: any?;
}

import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
    peran: Peran[];
    satuan_kerja_tahun: Satuan_Kerja_Tahun[];
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
    flash: {success: string?; error: string?; info: string?; }
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export type RoleBuku = 'umum' | 'admin_buku' | 'super_admin';

export interface User {
    id: number;
    username: string;
    email: string;
    nip_baru: string;
    nip_lama: string;
    nama: string;
    jabatan: string;
    golonagan: string;
    role_buku?: RoleBuku;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Buku {
    id: number;
    judul: string;
    pengarang: string | null;
    jumlah_halaman: number | null;
    tahun_terbit: number | null;
    bahasa: string | null;
    kategori: string | null;
    sinopsis: string | null;
    durasi_pinjam: number;
    stok: number;
    cover_path: string | null;
    cover_url: string | null;
    created_at?: string;
    updated_at?: string;
}

export type BukuPinjamStatus = 'menunggu' | 'dipinjam' | 'ditolak' | 'dikembalikan';

export interface BukuPinjam {
    id: number;
    buku_id: number;
    peminjam_username: string;
    tanggal_pengajuan: string;
    tanggal_persetujuan: string | null;
    tanggal_jatuh_tempo: string | null;
    tanggal_kembali: string | null;
    status: BukuPinjamStatus;
    diproses_oleh: string | null;
    is_terlambat: boolean;
    hari_terlambat: number;
    sisa_hari: number | null;
    is_kembali_terlambat: boolean;
    hari_kembali_terlambat: number;
    buku?: Buku;
    peminjam?: User;
    created_at?: string;
    updated_at?: string;
}

export interface BukuAdminLog {
    id: number;
    actor_username: string;
    actor_nama?: string;
    target_username: string;
    target_nama?: string;
    aksi: string;
    keterangan: string | null;
    ip_address: string | null;
    created_at?: string;
    updated_at?: string;
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

export interface Satuan_Kerja_Tahun {
    id: string;
    nama: string;
    tahun: number;
    satuan_kerja_id: string;
    kode_satuan_kerja: string;
    type: string;
    parent_id: string?;
    peran?: Peran[]
}

export interface Tim {
    id: string;
    nama: string;
    tahun: number;
    satuan_kerja_tahun_id: string;
    peran?: Peran[]
}

export interface Jurnal_Harian_Proyek {
    id: string;
    nama: string;
    tahun: number;
    satuan_kerja_tahun_id: string;
    tim_id: string;
}

interface Setting_User {
    id: string;
    username: string;
    category: string;
    key: string;
    data: any;
}

function hashUuid(uuid) {
  let hash = 0;
  for (let i = 0; i < uuid.length; i++) {
    hash = uuid.charCodeAt(i) + ((hash << 5) - hash);
  }
  return Math.abs(hash);
}

function uuidToColor(uuid) {
  const index = hashUuid(uuid) % palette.length;
  return palette[index];
}

import AppLayout from "@/layouts/app-layout";
import {
    type User,
    type BreadcrumbItem,
} from "@/types";
import React, { useState } from 'react';
import {
    Trash2,
    Plus,
    X,
    Pencil,
    Check,
    XIcon,
    ChevronLeft,
    ChevronRight,
} from 'lucide-react';

import { Button } from "@/components/ui/button";
import Heading from "@/components/heading";
import { useApi } from "@/lib/API/useApi";
import { toast } from "sonner";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Head } from "@inertiajs/react";
import { Badge } from "@/components/ui/badge";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Permohonan Izin Akses",
        href: '/lopi/arsip/klasifikasi',
    }
];


export type arsip_klasifikasi_type = {
    id: number,
    kode: string,
    nama: string,
};

export type arsip_arsip_type = {
    id: number,
    klasifikasi_arsip_id: number,
    unit_cipta_arsip: string,
    tingkat_perkembangan: 'Asli' | 'Salinan' | 'Copy',
    media_arsip: 'Digital' | 'Kertas' | 'Foto',
    kondisi_arsip: 'baik' | 'kurang_baik',
    uraian_arsip: string,
    kurun_waktu_awal: number,
    kurun_waktu_akhir: number,
    arsip_filename: string,
};

export type arsip_akses_type = {
    id: number,
    arsip_id: number,
    username: string,
    status: 'menunggu'| 'disetujui'| 'ditolak',
    arsip: arsip_arsip_type | null
};


export type satuankerjatahun_type = {
    id: string,
    kode_satuan_kerja: number,
    nama: string,
    tahun: number,
};


export default function Index({
    akses,
    klasifikasi,
    satuan_kerja_tahun,
    user,
    total_akses,
}: React.PropsWithChildren<{
    akses: arsip_akses_type[],
    klasifikasi: arsip_klasifikasi_type[],
    satuan_kerja_tahun: satuankerjatahun_type[],
    user: User[],
    total_akses: number,
}>) {

    const arsipKlasifikasiDict: Map<number, arsip_klasifikasi_type> = new Map<number, arsip_klasifikasi_type>();
    klasifikasi.forEach(element => {
        arsipKlasifikasiDict.set(element.id,  element)
    });

    const satuanKerjaTahunDict:  Map<string, satuankerjatahun_type> = new Map<string, satuankerjatahun_type>();
    satuan_kerja_tahun.forEach(element => {
        satuanKerjaTahunDict.set(element.id, element)
    });
    const userDict: Map<string, User> = new Map<string, User>();
    user.forEach(element => {
        userDict.set(element.username,  element)
    });

    const axiosClient = useApi();

    const [arsipAkses, setArsipAkses] = useState<arsip_akses_type[]>(akses);
    const [currentPageAkses, setCurrentPageAkses] = useState(1);
    const [totalAkses, setTotalAkses] = useState(total_akses);


    function load_data(page: number, success: () => void){
        axiosClient.post(route('arsip-akses_daftar'), {'page': page, 'limit': 20})
            .then(function (response) {
                setArsipAkses(response.data.akses as arsip_akses_type[])
                setTotalAkses(response.data.total_akses as number)
                setCurrentPageAkses(page)
                success()
                toast.success("Berhasil meload data.");
            })
            .catch(function (error) {
                toast.success("Gagal meload data.");
            });
    }

    function approve(target: arsip_akses_type, status: 'disetujui'|'ditolak'){
        axiosClient.post(route('arsip-akses_approval', {'akses': target.id}), {status : status})
            .then(function (response) {
                const _newData: arsip_akses_type = response.data.data as arsip_akses_type;
                setArsipAkses((prev) => prev.map((e) => (e.id === _newData.id ? _newData : e)));
                toast.success("Berhasil approve data.");
            })
            .catch(function (error) {
                toast.success("Gagal approve data.");
            });
    };

      return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Permohonan Izin Akses" />

                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                    <div className="w-full">
                        <Heading
                            title="Permohonan Izin Akses"
                            description="Tinjau permintaan akses untuk dokumen dengan tingkat keamanan terbatas."
                        />


                            {/* Table Section */}
                           <Card className="overflow-hidden p-0 mt-4">
                                <div className="w-full overflow-auto">
                                    <table className="w-full caption-bottom text-sm">
                                            <thead className="[&_tr]:border-b bg-accent">
                                                <tr className="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                                <th className="h-12 px-4 text-center align-middle font-medium ">No</th>
                                                <th className="h-12 px-4 text-left align-middle font-medium ">Klasifikasi</th>
                                                <th className="h-12 px-4 text-left align-middle font-medium ">Uraian</th>
                                                <th className="h-12 px-4 text-left align-middle font-medium ">Pencipta</th>
                                                <th className="h-12 px-4 text-left align-middle font-medium ">Pemohon</th>
                                                <th className="h-12 px-4 text-left align-middle font-medium ">Status</th>
                                                <th className="h-12 px-4 align-middle font-medium ">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody className="[&_tr:last-child]:border-0">
                                            {arsipAkses.map((item, index) => (
                                            <tr key={item.id} className="border-b transition-colors hover:bg-slate-50/50">
                                                <td className="p-4 align-middle font-mono font-bold">{index + 1}</td>
                                                <td className="p-4 align-middle font-mono font-bold text-blue-600">
                                                    {(item.arsip && item.arsip!.klasifikasi_arsip_id != -1 && arsipKlasifikasiDict.has(item.arsip!.klasifikasi_arsip_id)) ?
                                                        <>({arsipKlasifikasiDict.get(item.arsip!.klasifikasi_arsip_id)?.kode}) {arsipKlasifikasiDict.get(item.arsip!.klasifikasi_arsip_id)?.nama}</> : '-'}
                                                </td>
                                                <td className="p-4 align-middle font-medium max-w-[300px] truncate">{item.arsip && item.arsip!.uraian_arsip}</td>
                                                <td className="p-4 text-left align-middle">
                                                    {(item.arsip && item.arsip!.unit_cipta_arsip && satuanKerjaTahunDict.has(item.arsip!.unit_cipta_arsip)) ?
                                                    <>({satuanKerjaTahunDict.get(item.arsip!.unit_cipta_arsip)?.kode_satuan_kerja}) {satuanKerjaTahunDict.get(item.arsip!.unit_cipta_arsip)?.nama}</> : '-'}
                                                </td>
                                                 <td className="p-4 align-middle">
                                                    { userDict.has(item.username) ? <>{userDict.get(item.username)?.nama}</> : <>{item.username}</>}
                                                </td>
                                                <td className="p-4 align-middle">
                                                    {item.status === 'menunggu' && <Badge variant="warning_">Menunggu</Badge>}
                                                    {item.status === 'disetujui' && <Badge variant="success_">Disetujui</Badge>}
                                                    {item.status === 'ditolak' && <Badge variant="destructive_">Ditolak</Badge>}
                                                </td>
                                                <td className="p-4 align-middle text-center">

                                                    <Button variant="ghost" className="h-8 w-8 p-0"
                                                        onClick={() => {
                                                            approve(item, "disetujui");
                                                        }}>
                                                        <Check size={16} />
                                                    </Button>
                                                    <Button variant="ghost" className="h-8 w-8 p-0"
                                                        onClick={() => {
                                                            approve(item, "ditolak");
                                                        }}>
                                                        <XIcon size={16} />
                                                    </Button>

                                                </td>
                                            </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                 <PaginationFooter
                                    currentPage={currentPageAkses}
                                    itemsPerPage={20}
                                    totalItems={totalAkses}
                                    totalPages={Math.max(Math.ceil(totalAkses / 20), 1)}
                                    setCurrentPage={(page: number): void => { load_data(page, () => setCurrentPageAkses(page)) }}
                                />
                            </Card>


                    </div>
                </div>

        </AppLayout>
      );
}

const PaginationFooter = ({ currentPage, itemsPerPage, totalItems, totalPages, setCurrentPage } : { currentPage: number, itemsPerPage: number, totalItems: number, totalPages: number, setCurrentPage(page: number): void}) => (
  <div className="flex items-center justify-between px-4 py-4 border-t border-slate-100">
    <p className="text-xs ">
      Menampilkan <span className="font-medium ">{(currentPage - 1) * itemsPerPage + 1}</span> hingga <span className="font-medium ">{Math.min(currentPage * itemsPerPage, totalItems)}</span> dari <span className="font-medium ">{totalItems}</span> data
    </p>
    <div className="flex items-center space-x-2">
      <Button
        variant="outline"
        className="h-8 w-8 p-0"
        onClick={() => setCurrentPage(Math.max(1, currentPage - 1))}
        disabled={currentPage === 1}
      >
        <ChevronLeft size={16} />
      </Button>

      <div className="flex items-center gap-1">
        {[...Array(totalPages)].map((_, i) => (
          <Button
            key={i}
            variant={currentPage === i + 1 ? 'outline' : 'ghost'}
            className={`h-8 w-8 p-0 text-xs ${currentPage === i + 1 ? 'border-slate-900 bg-accent' : ''}`}
            onClick={() => setCurrentPage(i + 1)}
          >
            {i + 1}
          </Button>
        ))}
      </div>

      <Button
        variant="outline"
        className="h-8 w-8 p-0"
        onClick={() => setCurrentPage(Math.min(totalPages, currentPage+1)) }
        disabled={currentPage === totalPages}
      >
        <ChevronRight size={16} />
      </Button>
    </div>
  </div>
);


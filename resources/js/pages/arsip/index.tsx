import AppLayout from "@/layouts/app-layout";
import {
    type BreadcrumbItem,
} from "@/types";
import React, { useState } from 'react';
import {
    Trash2,
    Plus,
    X,
    Pencil,
    ChevronLeft,
    ChevronRight,
    Globe,
    FileText,
    MoreVertical,
    ChevronsUpDown,
    Check,
    DownloadIcon,
    KeyRound,
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
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from "@/components/ui/command"
import { cn } from "@/lib/utils";
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Daftar",
        href: '/lopi/arsip',
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
    have_arsip_peran,
    arsip,
    klasifikasi,
    satuan_kerja_tahun,
    total_arsip,
}: React.PropsWithChildren<{
    have_arsip_peran: boolean,
    arsip: arsip_arsip_type[],
    klasifikasi: arsip_klasifikasi_type[],
    total_arsip: number,
    satuan_kerja_tahun: satuankerjatahun_type[],
}>) {
    const arsipKlasifikasiDict: Map<number, arsip_klasifikasi_type> = new Map<number, arsip_klasifikasi_type>();
    klasifikasi.forEach(element => {
        arsipKlasifikasiDict.set(element.id,  element)
    });

    const satuanKerjaTahunDict:  Map<string, satuankerjatahun_type> = new Map<string, satuankerjatahun_type>();
    satuan_kerja_tahun.forEach(element => {
        satuanKerjaTahunDict.set(element.id, element)
    });

    const [isModalOpen, setIsModalOpen] = useState(false);
    const [arsipArsip, setArsipArsip] = useState<arsip_arsip_type[]>(arsip);
    const [arsipAkses, setArsipAkses] = useState<arsip_akses_type[]>([]);

    const [subTabArsip, setSubTabArsip] = useState<'semua'|'akses'>('semua');
    const [currentPageSemua, setCurrentPageSemua] = useState(1);
    const [currentPageAkses, setCurrentPageAkses] = useState(1);

    const [totalArsip, setTotalArsip] = useState(total_arsip);
    const [totalAkses, setTotalAkses] = useState(0);

    const [openPopKlasifikasi, seOpenPopKlasifikasi] = useState<boolean>(false)

    const [formData, setFormData] = useState<arsip_arsip_type>({
        id: -1,
        klasifikasi_arsip_id: -1,
        unit_cipta_arsip: '',
        tingkat_perkembangan: 'Asli',
        media_arsip: 'Digital',
        kondisi_arsip: 'baik',
        uraian_arsip: '',
        kurun_waktu_awal: 2026,
        kurun_waktu_akhir: 2030,
        arsip_filename: ''
    });

    const axiosClient = useApi();

    const handleArsipSubmit = (event:  React.FormEvent<HTMLFormElement>) => {
       event.preventDefault();


        if(formData.id == -1){
            const formData_ = new FormData(event.currentTarget)
            formData_.append('klasifikasi_arsip_id', ''+formData.klasifikasi_arsip_id);
            formData_.append('unit_cipta_arsip', ''+formData.unit_cipta_arsip);
            formData_.append('tingkat_perkembangan', ''+formData.tingkat_perkembangan);
            formData_.append('media_arsip', ''+formData.media_arsip);
            formData_.append('kondisi_arsip', ''+formData.kondisi_arsip);
            formData_.append('uraian_arsip', ''+formData.uraian_arsip);
            formData_.append('kurun_waktu_awal', ''+formData.kurun_waktu_awal);
            formData_.append('kurun_waktu_akhir', ''+formData.kurun_waktu_akhir);

            axiosClient.post(route('arsip-tambah'), formData_)
                .then(function (response) {
                    setIsModalOpen(false);
                    const _newData: arsip_arsip_type = response.data.data as arsip_arsip_type;
                    setArsipArsip([...arsipArsip, _newData]);
                    setFormData({ ...formData,
                        id: -1,
                        klasifikasi_arsip_id: -1,
                        unit_cipta_arsip: '',
                        tingkat_perkembangan: 'Asli',
                        media_arsip: 'Digital',
                        kondisi_arsip: 'baik',
                        uraian_arsip: '',
                        kurun_waktu_awal: 2026,
                        kurun_waktu_akhir: 2030,});
                    toast.success("Berhasil menambah data.");
                })
                .catch(function (error) {
                    toast.success("Gagal menambah data.");
                });
        }else{
            const newData: arsip_arsip_type = {
            ...formData,
            };
            axiosClient.post(route('arsip-ubah', {'arsip' : formData.id}), newData)
                .then(function (response) {
                    setIsModalOpen(false);
                    const _newData: arsip_arsip_type = response.data.data as arsip_arsip_type;

                    setArsipArsip((prev) => prev.map((e) => (e.id === _newData.id ? _newData : e)));
                    setFormData({ ...formData,
                        id: -1,
                        klasifikasi_arsip_id: -1,
                        unit_cipta_arsip: '',
                        tingkat_perkembangan: 'Asli',
                        media_arsip: 'Digital',
                        kondisi_arsip: 'baik',
                        uraian_arsip: '',
                        kurun_waktu_awal: 2026,
                        kurun_waktu_akhir: 2030,});
                    toast.success("Berhasil menambah data.");
                })
                .catch(function (error) {
                    toast.success("Gagal menambah data.");
                });
        }

    };

    function hapusArsipData(target: arsip_arsip_type){
        axiosClient.post(route('arsip-hapus', {'arsip': target.id}))
            .then(function (response) {
                setArsipArsip(arsipArsip.filter(h => h.id !== target.id))
                toast.success("Berhasil hapus data.");
            })
            .catch(function (error) {
                toast.success("Gagal hapus data.");
            });
    };

    function ajukanAksesArsipData(target: arsip_arsip_type){
        axiosClient.post(route('arsip-akses_ajukan', {'arsip': target.id}))
            .then(function (response) {

                const _newData: arsip_akses_type = response.data.data as arsip_akses_type;
                    setArsipAkses([ _newData, ... arsipAkses]);

                toast.success("Berhasil meminta akses file.");
            })
            .catch(function (error) {
                toast.success("Gagal meminta akses file.");
            });
    };

    function load_data(type: 'semua'|'akses', page: number, success: () => void){
        axiosClient.post(route('arsip-index'), {'page': page, 'limit': 20, 'tab': type})
            .then(function (response) {
                if(type == 'akses'){
                    setArsipAkses(response.data.akses as arsip_akses_type[])
                    setTotalAkses(response.data.total_akses as number)
                    setCurrentPageAkses(page)
                }else {
                    setArsipArsip(response.data.arsip as arsip_arsip_type[])
                    setTotalArsip(response.data.total_arsip as number)
                    setCurrentPageSemua(page)
                }
                success()
                toast.success("Berhasil meload data.");
            })
            .catch(function (error) {
                toast.success("Gagal meload data.");
            });
    }

    const handleDownload = async (target: arsip_arsip_type) => {
        try {
            const response = await axiosClient.get(
                `arsip/akses/${target.id}/buka`,
                {
                    responseType: "blob", // WAJIB untuk file
                }
            );

            const blob = new Blob([response.data]);
            const url = window.URL.createObjectURL(blob);

            const link = document.createElement("a");
            link.href = url;

            // ambil filename dari header kalau ada
            const contentDisposition = response.headers["content-disposition"];
            let fileName = "download";

            if (contentDisposition) {
                const match = contentDisposition.match(/filename="(.+)"/);
                if (match?.[1]) fileName = match[1];
            }

            link.setAttribute("download", target.arsip_filename);
            document.body.appendChild(link);
            link.click();

            link.remove();
            window.URL.revokeObjectURL(url);
        } catch (error) {
            console.error("Download gagal:", error);
        }
    };

      return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Daftar Arsip Digital" />

                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                    <div className="w-full">
                        <Heading
                            title="Daftar Arsip Digital"
                            description="Kelola dan pantau arsip organisasi Anda secara efisien."
                        />
                            { have_arsip_peran &&
                                <div className="flex justify-end mt-2 mb-4 gap-x-1.5">
                                    <Button
                                        className="flex items-center gap-1 bg-primary text-primary-foreground"
                                        onClick={() =>
                                            setIsModalOpen(true)
                                        }
                                    >
                                        <Plus />
                                        Tambah Arsip
                                    </Button>
                                </div>
                            }


                            <div className="inline-flex h-10 items-center justify-center rounded-md bg-slate-100 p-1 ">
                                <button
                                    onClick={() => { load_data('semua', 1, () => {setSubTabArsip('semua');setCurrentPageSemua(1) }) }}
                                    className={`inline-flex items-center justify-center whitespace-nowrap rounded-sm px-4 py-1.5 text-sm font-medium ring-offset-white transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 ${subTabArsip === 'semua' ? 'bg-white text-slate-950 shadow-sm' : 'hover:text-slate-900'}`}
                                    >
                                    Semua Akses
                                </button>
                                <button
                                    onClick={() => { load_data('akses', 1, () => {setSubTabArsip('akses'); setCurrentPageAkses(1) })}}
                                    className={`inline-flex items-center justify-center whitespace-nowrap rounded-sm px-4 py-1.5 text-sm font-medium ring-offset-white transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 ${subTabArsip === 'akses' ? 'bg-white text-slate-950 shadow-sm' : 'hover:text-slate-900'}`}
                                    >
                                    Memiliki Akses
                                </button>
                            </div>

                            {subTabArsip == 'akses' ?
                            <>
                                 <Card className="overflow-hidden p-0 mt-4">
                                    <div className="w-full overflow-auto">
                                        <table className="w-full caption-bottom text-sm">
                                             <thead className="[&_tr]:border-b bg-accent">
                                                 <tr className="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                                    <th className="h-12 px-4 text-center align-middle font-medium ">No</th>
                                                    <th className="h-12 px-4 text-left align-middle font-medium ">Klasifikasi</th>
                                                    <th className="h-12 px-4 text-left align-middle font-medium ">Uraian</th>
                                                    <th className="h-12 px-4 text-left align-middle font-medium ">Pencipta</th>
                                                    <th className="h-12 px-4 text-left align-middle font-medium ">Status</th>
                                                    <th className="h-12 px-4 align-middle font-medium ">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody className="[&_tr:last-child]:border-0">
                                                {arsipAkses.map((item, index) => (
                                                <tr key={item.id} className="border-b transition-colors hover:bg-slate-50/50">
                                                    <td className="p-4 align-middle font-mono font-bold">{(currentPageSemua-1)*20 + index + 1}</td>
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
                                                        {item.status === 'menunggu' && <Badge variant="warning_">Menunggu</Badge>}
                                                        {item.status === 'disetujui' && <Badge variant="success_">Disetujui</Badge>}
                                                        {item.status === 'ditolak' && <Badge variant="destructive_">Ditolak</Badge>}
                                                    </td>
                                                    <td className="p-4 align-middle text-center">
                                                        {(item.arsip && item.status == 'disetujui') &&
                                                            <Button variant="ghost" className="h-8 w-8 p-0"
                                                                onClick={() => {
                                                                    handleDownload(item.arsip!);
                                                                }}>
                                                                <DownloadIcon size={16} />
                                                            </Button>
                                                        }
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
                                        setCurrentPage={(page: number): void => { load_data('akses', page, () => setCurrentPageAkses(page)) }}
                                    />
                                </Card>
                            </>
                            :
                            <>
                                <Card className="overflow-hidden p-0  mt-4">
                                    <div className="w-full overflow-auto">
                                        <table className="w-full caption-bottom text-sm">
                                             <thead className="[&_tr]:border-b bg-accent">
                                                 <tr className="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                                    <th className="h-12 px-4 text-center align-middle font-medium ">No</th>
                                                    <th className="h-12 px-4 text-left align-middle font-medium ">Klasifikasi</th>
                                                    <th className="h-12 px-4 text-left align-middle font-medium ">Uraian</th>
                                                    <th className="h-12 px-4 text-left align-middle font-medium ">Pencipta</th>
                                                    <th className="h-12 px-4 text-left align-middle font-medium ">Tingkat Perkembangan</th>
                                                    <th className="h-12 px-4 text-left align-middle font-medium ">Media</th>
                                                    <th className="h-12 px-4 text-center align-middle font-medium">Tahun</th>
                                                    <th className="h-12 px-4 align-middle font-medium ">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody className="[&_tr:last-child]:border-0">
                                                {arsipArsip.map((item, index) => (
                                                <tr key={item.id} className="border-b transition-colors hover:bg-slate-50/50">
                                                    <td className="p-4 align-middle font-mono font-bold">{(currentPageSemua-1)*20 + index + 1}</td>
                                                    <td className="p-4 align-middle font-mono font-bold text-blue-600">
                                                        {(item.klasifikasi_arsip_id != -1 && arsipKlasifikasiDict.has(item.klasifikasi_arsip_id)) ?
                                                            <>({arsipKlasifikasiDict.get(item.klasifikasi_arsip_id)?.kode}) {arsipKlasifikasiDict.get(item.klasifikasi_arsip_id)?.nama}</> : '-'}
                                                    </td>
                                                    <td className="p-4 align-middle font-medium max-w-[300px] truncate">{item.uraian_arsip}</td>
                                                    <td className="p-4 text-left align-middle">
                                                        {(item.unit_cipta_arsip && satuanKerjaTahunDict.has(item.unit_cipta_arsip)) ?
                                                        <>({satuanKerjaTahunDict.get(item.unit_cipta_arsip)?.kode_satuan_kerja}) {satuanKerjaTahunDict.get(item.unit_cipta_arsip)?.nama}</> : '-'}
                                                    </td>
                                                    <td className="p-4 align-middle italic  text-xs">{item.tingkat_perkembangan}</td>
                                                    <td className="p-4 align-middle">
                                                    <span className="flex items-center gap-1.5">
                                                        {item.media_arsip === 'Digital' ? <Globe size={14}/> : <FileText size={14}/>}
                                                        {item.media_arsip}
                                                    </span>
                                                    </td>
                                                    <td className="p-4 align-middle text-center font-medium">{item.kurun_waktu_awal} {item.kurun_waktu_akhir && <> - {item.kurun_waktu_akhir}</>}</td>
                                                    <td className="p-4 align-middle text-center">
                                                        {!have_arsip_peran &&
                                                            <Button variant="ghost" className="h-8 w-8 p-0"
                                                                onClick={() => {
                                                                    ajukanAksesArsipData(item)
                                                                }}>
                                                                <KeyRound size={16} />
                                                            </Button>
                                                        }

                                                        {have_arsip_peran &&
                                                             <Button variant="ghost" className="h-8 w-8 p-0"
                                                                onClick={() => {
                                                                    handleDownload(item);
                                                                }}>
                                                                <DownloadIcon size={16} />
                                                            </Button>
                                                        }

                                                        <Button variant="ghost" className="h-8 w-8 p-0"  onClick={() => {
                                                                setFormData({...item});
                                                                setIsModalOpen(true);
                                                            }}
                                                        >
                                                            <Pencil size={16} />
                                                        </Button>
                                                        <Button variant="ghost" className="h-8 w-8 p-0"
                                                            onClick={() => {
                                                                hapusArsipData(item)
                                                            }}>
                                                            <Trash2 size={16} />
                                                        </Button>
                                                    </td>
                                                </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                    <PaginationFooter
                                        currentPage={currentPageSemua}
                                        itemsPerPage={20}
                                        totalItems={totalArsip}
                                        totalPages={Math.max(Math.ceil(totalArsip / 20), 1)}
                                        setCurrentPage={(page: number): void => { load_data('semua', page, () => setCurrentPageSemua(page)) }}
                                    />
                                </Card>
                            </>}

                            {/* Table Section */}


                        {/* Modal - Shadcn Dialog Style */}
                        {isModalOpen && (
                            <div className="fixed inset-0 z-50 flex items-center justify-center">
                            <div className="absolute inset-0 bg-slate-950/20 backdrop-blur-sm" onClick={() => setIsModalOpen(false)} />
                            <Card className="relative w-full max-w-lg shadow-xl animate-in fade-in zoom-in duration-200">
                                <CardHeader className="flex flex-row items-center justify-between space-y-0">
                                <div>
                                    <CardTitle className="text-xl">Entri Data Baru</CardTitle>
                                    <CardDescription>Masukkan Klasifikasi Baru.</CardDescription>
                                </div>
                                <Button variant="ghost" className="h-8 w-8 p-0" onClick={() => setIsModalOpen(false)}>
                                    <X className="h-4 w-4" />
                                </Button>
                                </CardHeader>
                                <form onSubmit={handleArsipSubmit}>
                                    <CardContent className="grid gap-4 py-4">
                                        <div className="grid gap-2">
                                            <Label>Kode Klasifikasi</Label>
                                            <Popover open={openPopKlasifikasi} onOpenChange={seOpenPopKlasifikasi} modal={true}>
                                                <PopoverTrigger asChild>
                                                    <Button
                                                        variant="outline"
                                                        role="combobox"
                                                        className="justify-between text-secondary"
                                                    >
                                                        { (formData.klasifikasi_arsip_id != -1) ?
                                                             (arsipKlasifikasiDict.has(Number(formData.klasifikasi_arsip_id) ) ? <>({arsipKlasifikasiDict.get(formData.klasifikasi_arsip_id)?.kode}) {arsipKlasifikasiDict.get(formData.klasifikasi_arsip_id)?.nama}</> : formData.klasifikasi_arsip_id)
                                                            : "Pilih Klasifikasi..."}
                                                        <ChevronsUpDown className="opacity-50" />
                                                    </Button>
                                                </PopoverTrigger>
                                                <PopoverContent className="p-0 w-full">
                                                    <Command>
                                                        <CommandInput placeholder="Cari Klasifikasi..." className="h-9" />
                                                        <CommandList>
                                                            <CommandEmpty>Tidak ditemukan.</CommandEmpty>
                                                            <CommandGroup>
                                                                {klasifikasi.map((item) => (
                                                                    <CommandItem
                                                                        key={item.id}
                                                                        value={''+item.id}
                                                                        onSelect={(currentValue) => {
                                                                            setFormData(prev => ({ ...prev, ['klasifikasi_arsip_id']: Number(currentValue)  }))
                                                                            seOpenPopKlasifikasi(false)
                                                                        }}
                                                                    >
                                                                    ({item.kode}) {item.nama}
                                                                    <Check
                                                                        className={cn(
                                                                        "ml-auto",
                                                                            formData.klasifikasi_arsip_id === item.id ? "opacity-100" : "opacity-0"
                                                                        )}
                                                                    />
                                                                    </CommandItem>
                                                                ))}
                                                            </CommandGroup>
                                                        </CommandList>
                                                    </Command>
                                                </PopoverContent>
                                            </Popover>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label>Satuan Kerja</Label>
                                            <Select required
                                                defaultValue={formData.unit_cipta_arsip}
                                                onValueChange={(value) => {
                                                    setFormData(prev => ({ ...prev, ['unit_cipta_arsip']: value  }))
                                                }}>
                                                <SelectTrigger
                                                    name="satuan_kerja"
                                                    className="text-secondary">
                                                    <SelectValue placeholder="Pilih Satuan Kerja" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectGroup>
                                                        <SelectGroup>
                                                            {satuan_kerja_tahun.map((item, index) =>
                                                                ( <SelectItem key={item.id} value={item.id}>{item.nama}</SelectItem>)
                                                            )}
                                                        </SelectGroup>
                                                    </SelectGroup>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label>Tingkat Pengembangan</Label>
                                            <Select required
                                                defaultValue={formData.tingkat_perkembangan}
                                                onValueChange={(value) => {
                                                    setFormData(prev => ({ ...prev, ['tingkat_perkembangan']: value as  'Asli' | 'Salinan' | 'Copy' }))
                                                }}>
                                                <SelectTrigger
                                                    name="satuan_kerja"
                                                    className="text-secondary">
                                                    <SelectValue placeholder="Pilih Tingkat Pengembangan" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectGroup>
                                                        <SelectGroup>
                                                            <SelectItem value="Asli">Asli</SelectItem>
                                                            <SelectItem value="Salinan">Salinan</SelectItem>
                                                            <SelectItem value="Copy">Copy</SelectItem>
                                                        </SelectGroup>
                                                    </SelectGroup>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="grid grid-cols-2 gap-4">
                                             <div className="grid gap-2">
                                                <Label>Media Arsip</Label>
                                                <Select required
                                                    defaultValue={formData.media_arsip}
                                                    onValueChange={(value) => {
                                                        setFormData(prev => ({ ...prev, ['media_arsip']: value as  'Digital' | 'Kertas' | 'Foto' }))
                                                    }}>
                                                    <SelectTrigger
                                                        name="satuan_kerja"
                                                        className="text-secondary">
                                                        <SelectValue placeholder="Pilih Media Arsip" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectGroup>
                                                            <SelectGroup>
                                                                <SelectItem value="Digital">Digital</SelectItem>
                                                                <SelectItem value="Kertas">Kertas</SelectItem>
                                                                <SelectItem value="Foto">Foto</SelectItem>
                                                            </SelectGroup>
                                                        </SelectGroup>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            <div className="grid gap-2">
                                                <Label>Kondisi Arsip</Label>
                                                <Select required
                                                    defaultValue={formData.kondisi_arsip}
                                                    onValueChange={(value) => {
                                                        setFormData(prev => ({ ...prev, ['kondisi_arsip']: value as  'baik' | 'kurang_baik' }))
                                                    }}>
                                                    <SelectTrigger
                                                        name="satuan_kerja"
                                                        className="text-secondary">
                                                        <SelectValue placeholder="Pilih Satuan Kerja" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectGroup>
                                                            <SelectGroup>
                                                                <SelectItem value="baik">Baik</SelectItem>
                                                                <SelectItem value="kurang_baik">Kurang Baik</SelectItem>
                                                            </SelectGroup>
                                                        </SelectGroup>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label>Uraian</Label>
                                            <Input type="text" placeholder="Contoh: SK ..." value={formData.uraian_arsip}
                                                onChange={e =>  setFormData(prev => ({ ...prev, ['uraian_arsip']: e.target.value })) } required>
                                            </Input>
                                        </div>
                                        <div className="grid grid-cols-2 gap-4">
                                             <div className="grid gap-2">
                                                <Label>Kurung Waktu Awal</Label>
                                                <Input type="number" placeholder="Contoh: 2022" value={formData.kurun_waktu_awal}
                                                    onChange={e =>  setFormData(prev => ({ ...prev, ['kurun_waktu_awal']: Number(e.target.value) })) } required>
                                                </Input>
                                            </div>
                                            <div className="grid gap-2">
                                                <Label>Kurung Waktu Akhir</Label>
                                                <Input type="number" placeholder="Contoh: 2026" value={formData.kurun_waktu_akhir}
                                                    onChange={e =>  setFormData(prev => ({ ...prev, ['kurun_waktu_akhir']: Number(e.target.value) })) } required>
                                                </Input>
                                            </div>
                                        </div>
                                        {formData.id == -1 &&
                                            <div className="grid gap-2">
                                                <Label>File</Label>
                                                <Input type="file" name="file" placeholder="Contoh: SK ..."
                                                    required>
                                                </Input>
                                            </div>
                                        }

                                    </CardContent>
                                    <div className="flex items-center justify-end gap-3 p-6 pt-0">
                                        <Button variant="outline" type="button" onClick={() => setIsModalOpen(false)}>Batal</Button>
                                        <Button type="submit">Simpan</Button>
                                    </div>
                                </form>
                            </Card>
                            </div>
                        )}
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

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
} from 'lucide-react';

import { Button } from "@/components/ui/button";
import Heading from "@/components/heading";
import { useApi } from "@/lib/API/useApi";
import { toast } from "sonner";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Head } from "@inertiajs/react";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Klasifikasi",
        href: '/lopi/arsip/klasifikasi',
    }
];

export type arsip_klasifikasi_type = {
    id: number,
    kode: string,
    nama: string,
};


export default function Index({
    arsip_klasifikasi_,

}: React.PropsWithChildren<{
    arsip_klasifikasi_: arsip_klasifikasi_type[]
}>) {

      const [isModalOpen, setIsModalOpen] = useState(false);
      const [arsipKlasifikasi, setArsipKlasifikasi] = useState<arsip_klasifikasi_type[]>(arsip_klasifikasi_);

      const [formData, setFormData] = useState<arsip_klasifikasi_type>({
        id: -1,
        kode: '',
        nama: '',
      });

      const axiosClient = useApi();

    const handleSubmit = (e:  React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const newData: arsip_klasifikasi_type = {
          ...formData,
          kode: formData.kode,
          nama: formData.nama,
        };

        if(formData.id == -1){
              axiosClient.post(route('arsip-klasifikasi-tambah'), newData)
                .then(function (response) {
                    setIsModalOpen(false);
                    const _newData: arsip_klasifikasi_type = response.data.data as arsip_klasifikasi_type;
                    setArsipKlasifikasi([...arsipKlasifikasi, _newData]);
                    setFormData({ ...formData,  id: -1, kode: '', nama: ''});
                    toast.success("Berhasil menambah data.");
                })
                .catch(function (error) {
                    toast.success("Gagal menambah data.");
                });
        }else{
            axiosClient.post(route('arsip-klasifikasi-ubah', {'klasifikasi' : formData.id}), newData)
                .then(function (response) {
                    setIsModalOpen(false);
                    const _newData: arsip_klasifikasi_type = response.data.data as arsip_klasifikasi_type;

                    setArsipKlasifikasi((prev) => prev.map((e) => (e.id === _newData.id ? _newData : e)));
                    setFormData({ ...formData,  id: -1, kode: '', nama: ''});
                    toast.success("Berhasil menambah data.");
                })
                .catch(function (error) {
                    toast.success("Gagal menambah data.");
                });
        }

    };

    function habpusData(target: arsip_klasifikasi_type){
        axiosClient.post(route('arsip-klasifikasi-hapus', {'klasifikasi': target.id}))
            .then(function (response) {
                setArsipKlasifikasi(arsipKlasifikasi.filter(h => h.id !== target.id))
                toast.success("Berhasil hapus data.");
            })
            .catch(function (error) {
                toast.success("Gagal hapus data.");
            });
    };

      return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Klasifikasi Arsip" />

                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                    <div className="w-full">
                        <Heading
                            title="Klasifikasi Arsip"
                            description="Atur skema penomoran dan kategori dokumen berdasarkan kode resmi."
                        />
                             <div className="flex justify-end mt-2 mb-4 gap-x-1.5">
                                <Button
                                    className="flex items-center gap-1 bg-primary text-primary-foreground"
                                    onClick={() =>
                                        setIsModalOpen(true)
                                    }
                                >
                                    <Plus />
                                    Tambah Kode
                                </Button>
                            </div>


                            {/* Table Section */}
                            <Card className="overflow-hidden  p-0 border-slate-50/50 border">
                                <div className="w-full overflow-auto">
                                    <table className="w-full caption-bottom text-sm">
                                        <thead className="[&_tr]:border-b bg-accent">
                                            <tr className="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                                <th className="h-12 px-4 text-center font-medium">No</th>
                                                <th className="h-12 px-4 text-center font-medium">Kode</th>
                                                <th className="h-12 px-4 text-left font-medium">Nama</th>
                                                <th className="h-12 px-4 text-center font-mediu">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody className="[&_tr:last-child]:border-0 text-accent-foregroun">
                                            {arsipKlasifikasi.map((item, index) => (
                                                <tr key={item.id} className="border-b transition-colors hover:bg-slate-50/50">
                                                    <td className="p-4 text-center">
                                                        {index + 1}
                                                    </td>
                                                    <td className="p-4 text-center">
                                                        {item.kode}
                                                    </td>
                                                    <td className="p-4 text-left">
                                                        {item.nama}
                                                    </td>
                                                    <td className="p-4 align-middle text-center">
                                                         <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => {
                                                                setFormData({...item});
                                                                setIsModalOpen(true);
                                                            }}
                                                            className="h-8 w-8 p-0"
                                                            >
                                                            <Pencil className="h-4 w-4  hover:text-green-500" />
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => habpusData(item)}
                                                            className="h-8 w-8 p-0"
                                                            >
                                                            <Trash2 className="h-4 w-4  hover:text-red-500" />
                                                        </Button>
                                                    </td>
                                                </tr>
                                            ))}
                                            </tbody>
                                        </table>
                                </div>
                            </Card>

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
                                <form onSubmit={handleSubmit}>
                                    <CardContent className="grid gap-4 py-4">
                                        <div className="grid gap-2">
                                            <Label>Kode Klasifikasi</Label>
                                            <Input type="text" placeholder="Misal: KP.02"
                                                value={formData.kode}
                                                onChange={e => setFormData(prev => ({ ...prev, ['kode']:  e.target.value})) }/>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label>Nama</Label>
                                            <Input type="text" placeholder="Misal: Kepegawaian"
                                                value={formData.nama }
                                                onChange={e => setFormData(prev => ({ ...prev, ['nama']:  e.target.value })) }/>
                                        </div>
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

import AppLayout from "@/layouts/app-layout";
import {
    type User,
    type BreadcrumbItem,
} from "@/types";
import React, { useState, useEffect } from 'react';
import {
  LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend, AreaChart, Area
} from 'recharts';
import {
  Activity, Heart, Thermometer, Trash2,
  Database, TrendingUp, X, Check, Calendar as CalendarIcon,
  ChevronDown, MoreHorizontal,
  User as UserIcon,
  Plus,
} from 'lucide-react';

import { Button } from "@/components/ui/button";

import Heading from "@/components/heading";
import { useApi } from "@/lib/API/useApi";
import { toast } from "sonner";
import { appreciate_data } from "@/props/appreciate/types";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Head } from "@inertiajs/react";
import { number } from "framer-motion";
import { DatePicker22 } from "@/components/ui/date-picker";
import { formatDate } from "@/components/calender_modules/helpers";


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Kesehatan",
        href: '/lopi/kesehatan',
    }
];

export type kesehatan = {
    id: number,
    tanggal: string,
    tinggi_badan: number | null,
    berat_badan: number | null,
    linkar_perut: number | null,
    tekanan_darah: string | null,
    asam_urat: number | null,
    gula_darah: number | null,
    gula_darah_tipe: string | null,
};


export default function Index({
    kesehatan_,

}: React.PropsWithChildren<{
    kesehatan_: kesehatan[]
}>) {

      const [isModalOpen, setIsModalOpen] = useState(false);
      const [healthData, setHealthData] = useState<kesehatan[]>(kesehatan_);

      const latestData: kesehatan = healthData[healthData.length-1] || {};
      healthData.forEach(element => {
        if(!latestData.tinggi_badan && element.tinggi_badan){
            latestData.tinggi_badan = element.tinggi_badan
        }
        if(!latestData.berat_badan && element.berat_badan){
            latestData.berat_badan = element.berat_badan
        }
        if(!latestData.linkar_perut && element.linkar_perut){
            latestData.linkar_perut = element.linkar_perut
        }
         if(!latestData.tekanan_darah && element.tekanan_darah){
            latestData.tekanan_darah = element.tekanan_darah
        }
        if(!latestData.asam_urat && element.asam_urat){
            latestData.asam_urat = element.asam_urat
        }
        if(!latestData.gula_darah && element.gula_darah){
            latestData.gula_darah = element.gula_darah
        }
        if(!latestData.gula_darah_tipe && element.gula_darah_tipe){
            latestData.gula_darah_tipe = element.gula_darah_tipe
        }

      });

      const [formData, setFormData] = useState<{
            id: number,
            tanggal: string,
            tinggi_badan: number | null,
            berat_badan: number | null,
            linkar_perut: number | null,
            tekanan_darah_sistolik: number | null,
            tekanan_darah_diastolik: number | null,
            asam_urat: number | null,
            gula_darah: number | null,
            gula_darah_tipe: string | null,
        }
      >({
        id: 0,
        tanggal: new Date().toISOString().slice(0, 16),
        tinggi_badan: latestData.tinggi_badan,
        berat_badan: null,
        linkar_perut: null,
        tekanan_darah_sistolik: null,
        tekanan_darah_diastolik: null,
        asam_urat: null,
        gula_darah: null,
        gula_darah_tipe: 'gds'
      });

      const axiosClient = useApi();

      const handleSubmit = (e:  React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const newData: kesehatan = {
          ...formData,
          id: -1,
          tanggal: formData.tanggal,
          tinggi_badan: formData.tinggi_badan,
          berat_badan: formData.berat_badan,
          linkar_perut: formData.linkar_perut,
          tekanan_darah: formData.tekanan_darah_sistolik && formData.tekanan_darah_diastolik ? formData.tekanan_darah_sistolik+'/'+formData.tekanan_darah_diastolik : null,
          asam_urat: formData.asam_urat,
          gula_darah: formData.gula_darah,
          gula_darah_tipe: formData.gula_darah_tipe
        };

        axiosClient.post(route('kesehatan-tambah'), newData)
            .then(function (response) {
                setIsModalOpen(false);
                setHealthData([newData, ...healthData]);
                setFormData({ ...formData, tanggal: new Date().toISOString().slice(0, 16), tinggi_badan: latestData.tinggi_badan, berat_badan: null, linkar_perut: null, tekanan_darah_sistolik: null, tekanan_darah_diastolik: null, asam_urat: null, gula_darah: null, gula_darah_tipe: 'gds' });
                toast.success("Berhasil menambah data.");
            })
            .catch(function (error) {
                toast.success("Gagal menambah data.");
            });
    };

    function habpusData(target: kesehatan){
        axiosClient.post(route('kesehatan-hapus', {'kesehatan': target.id}))
            .then(function (response) {
                setHealthData(healthData.filter(h => h.id !== target.id))
                toast.success("Berhasil hapus data.");
            })
            .catch(function (error) {
                toast.success("Gagal hapus data.");
            });
    };

      const calculateBMI = (w: number | null, h: number | null) => {
        if (!w || !h) return 0;
        const heightInMeters = h / 100;

        let bmi = (w / (heightInMeters * heightInMeters));
        let kategori = "";

        if (bmi < 18.5) {
            kategori = "Kurus";
        } else if (bmi < 25) {
            kategori = "Normal";
        } else if (bmi < 30) {
            kategori = "Overweight";
        } else {
            kategori = "Obesitas";
        }

        return bmi.toFixed(1)+ " ("+kategori+")";
      };

      const bmiValue = calculateBMI(latestData.berat_badan, latestData.tinggi_badan);

      return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kesehatan" />

                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                    <div className="w-full">
                        <Heading
                            title="Dashboard Kesehatan"
                            description="Pantau dan kelola data kesehatan Anda secara efisien."
                        />
                             <div className="flex justify-end mt-2 mb-6 gap-x-1.5">
                                    <Button
                                        className="flex items-center gap-1 bg-primary text-primary-foreground"
                                        onClick={() =>
                                            setIsModalOpen(true)
                                        }
                                    >
                                        <Plus />
                                        Tambah Riwayat
                                    </Button>
                                </div>
                            {/* Stats Grid */}
                            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">

                                <Card className="bg-muted text-accent-foreground">
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-1">
                                        <CardTitle className="text-sm font-medium  uppercase tracking-wider">Tekanan Darah</CardTitle>
                                        <Heart className="h-4 w-4 " />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{latestData.tekanan_darah || '--/--'}</div>
                                        <p className="text-xs">mmHg</p>
                                    </CardContent>
                                </Card>



                                <Card className="bg-muted text-accent-foreground">
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-1">
                                        <CardTitle className="text-sm font-medium  uppercase tracking-wider">Gula Darah</CardTitle>
                                        <Activity className="h-4 w-4 " />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{latestData.gula_darah || '0'}</div>
                                        {latestData.gula_darah_tipe == 'gdp' &&   <p className="text-xs">Gula Darah Puasa</p>}
                                        {latestData.gula_darah_tipe == 'gds' &&   <p className="text-xs">Gula Darah Sewaktu</p>}
                                    </CardContent>
                                </Card>


                                <Card className="bg-muted text-accent-foreground">
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-1">
                                        <CardTitle className="text-sm font-medium  uppercase tracking-wider">Asam Urat</CardTitle>
                                        <Thermometer className="h-4 w-4 " />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{latestData.asam_urat || '0'}</div>
                                        <p className="text-xs">mg/dL</p>
                                    </CardContent>
                                </Card>


                                <Card className="bg-muted text-accent-foreground">
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-1">
                                        <CardTitle className="text-sm font-medium  uppercase tracking-wider">BMI Score</CardTitle>
                                        <TrendingUp className="h-4 w-4 " />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-2xl font-bold">{bmiValue}</div>
                                        <p className="text-xs">Indeks Massa Tubuh</p>
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Charts Grid */}
                            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-7 mt-4">
                                <Card className="lg:col-span-4 bg-muted text-accent-foregroundt" >
                                    <CardHeader>
                                        <CardTitle className="font-semibold text-accent-foreground">Analisis Metabolik</CardTitle>
                                        <CardDescription>Tren gula darah dan asam urat dalam 3 bulan terakhir.</CardDescription>
                                    </CardHeader>
                                    <CardContent className="h-[300px]">
                                    <ResponsiveContainer width="100%" height="100%">
                                        <LineChart data={[...healthData]}>
                                        <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                                        <XAxis
                                            dataKey="tanggal"
                                            tickFormatter={(str) => new Date(str).toLocaleDateString('id-ID', {month: 'short'})}
                                            axisLine={false}
                                            tickLine={false}
                                            tick={{fontSize: 12, fill: '#64748b'}}
                                        />
                                        <YAxis axisLine={false} tickLine={false} tick={{fontSize: 12, fill: '#64748b'}} domain={[0, 200]}/>
                                        <Tooltip
                                            contentStyle={{ borderRadius: '8px', border: '1px solid #e2e8f0', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}
                                        />
                                        <Legend verticalAlign="top" align="right" iconType="circle" wrapperStyle={{ paddingBottom: '20px', fontSize: '12px' }} />
                                            <Line type="monotone" dataKey="gula_darah" name="Gula Darah" stroke="#f2a154" strokeWidth={2} dot={{ r: 4, fill: '#f2a154' }} activeDot={{ r: 6 }} />
                                            <Line type="monotone" dataKey="asam_urat" name="Asam Urat" stroke="#c96442" strokeWidth={2} strokeDasharray="5 5" dot={{ r: 4, fill: '#c96442' }} />
                                        </LineChart>
                                    </ResponsiveContainer>
                                    </CardContent>
                                </Card>

                                <Card className="lg:col-span-3 bg-muted text-accent-foreground">
                                    <CardHeader>
                                        <CardTitle className="font-semibold  text-accent-foreground">Komposisi Tubuh</CardTitle>
                                        <CardDescription>Monitor berat badan dan lingkar perut.</CardDescription>
                                    </CardHeader>
                                    <CardContent className="h-[300px]">
                                    <ResponsiveContainer width="100%" height="100%">
                                        <AreaChart data={[...healthData]}>
                                        <defs>
                                            <linearGradient id="colorWeight" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="5%" stopColor="#f2a154" stopOpacity={0.1}/>
                                                <stop offset="95%" stopColor="#f2a154" stopOpacity={0}/>
                                            </linearGradient>
                                        </defs>
                                        <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                                        <XAxis dataKey="tanggal"
                                            tickFormatter={(str) => new Date(str).toLocaleDateString('id-ID', {month: 'short'})}
                                            axisLine={false}
                                            tickLine={false}
                                            tick={{fontSize: 12, fill: '#64748b'}}
                                         />
                                        <YAxis axisLine={false} tickLine={false} tick={{fontSize: 12, fill: '#64748b'}} />
                                        <Tooltip
                                            contentStyle={{ borderRadius: '8px', border: '1px solid #e2e8f0', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }}/>
                                        <Legend verticalAlign="top" align="right" iconType="circle" wrapperStyle={{ paddingBottom: '20px', fontSize: '12px' }} />
                                        <Area type="monotone" dataKey="berat_badan" name="Berat (kg)" stroke="#f2a154" fillOpacity={1} fill="url(#colorWeight)" strokeWidth={2} />
                                        <Area type="monotone" dataKey="linkar_perut" name="Lingkar (cm)" stroke="#c96442" fill="transparent" strokeWidth={2} strokeDasharray="4 4" />
                                        </AreaChart>
                                    </ResponsiveContainer>
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Table Section */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base font-semibold">Riwayat Pemeriksaan</CardTitle>
                                    <CardDescription>Daftar lengkap hasil pemeriksaan kesehatan Anda.</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="relative w-full overflow-auto">
                                    <table className="w-full caption-bottom text-sm">
                                        <thead className="[&_tr]:border-b bg-accent">
                                        <tr className="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                            <th className="h-12 px-4 text-left align-middle font-medium">Tanggal</th>
                                            <th className="h-12 px-4 text-left align-middle font-medium">TB/BB</th>
                                            <th className="h-12 px-4 text-left align-middle font-medium">Tekanan Darah</th>
                                            <th className="h-12 px-4 text-left align-middle font-medium">Gula Darah</th>
                                            <th className="h-12 px-4 text-left align-middle font-medium">Asam Urat</th>
                                            <th className="h-12 px-4 text-right align-middle font-mediu">Aksi</th>
                                        </tr>
                                        </thead>
                                        <tbody className="[&_tr:last-child]:border-0 text-accent-foregroun">
                                        {[...healthData].reverse().map((item) => (
                                            <tr key={item.id} className="border-b transition-colors hover:bg-slate-50/50">
                                                <td className="p-4 align-middle font-medium">
                                                    {new Date(item.tanggal).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })}
                                                </td>
                                                <td className="p-4 align-middle">
                                                    {item.tinggi_badan ? item.tinggi_badan : '-' } cm / {item.berat_badan ? item.berat_badan : '-'} kg
                                                </td>
                                                <td className="p-4 align-middle">
                                                    {item.tekanan_darah ? item.tekanan_darah : '-'}
                                                </td>
                                                 <td className="p-4 align-middle">
                                                    {item.gula_darah && <>({item.gula_darah_tipe?.toUpperCase()}) {item.gula_darah}</>}
                                                </td>
                                                <td className="p-4 align-middle">
                                                    {item.asam_urat ? item.asam_urat : '-'}
                                                </td>
                                                <td className="p-4 align-middle text-right">
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
                                </CardContent>
                            </Card>

                        {/* Modal - Shadcn Dialog Style */}
                        {isModalOpen && (
                            <div className="fixed inset-0 z-50 flex items-center justify-center">
                            <div className="absolute inset-0 bg-slate-950/20 backdrop-blur-sm" onClick={() => setIsModalOpen(false)} />
                            <Card className="relative w-full max-w-lg shadow-xl animate-in fade-in zoom-in duration-200">
                                <CardHeader className="flex flex-row items-center justify-between space-y-0">
                                <div>
                                    <CardTitle className="text-xl">Entri Data Baru</CardTitle>
                                    <CardDescription>Masukkan hasil laboratorium terbaru Anda.</CardDescription>
                                </div>
                                <Button variant="ghost" className="h-8 w-8 p-0" onClick={() => setIsModalOpen(false)}>
                                    <X className="h-4 w-4" />
                                </Button>
                                </CardHeader>
                                <form onSubmit={handleSubmit}>
                                <CardContent className="grid gap-4 py-4">
                                    <div className="grid gap-2">
                                        <Label>Tanggal</Label>
                                        <DatePicker22
                                            value={new Date(formData.tanggal)}
                                            setValue={(value) => {
                                                setFormData(prev => ({ ...prev, ['tanggal']: formatDate(value.toISOString()) }))
                                            }}
                                            modal={true}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label>Tekanan Darah (Sistolik / Diastolik)</Label>
                                        <div className="flex items-center gap-2">
                                            <Input
                                                type="number"
                                                placeholder="Sis"
                                                name="sistolik"
                                                value={formData.tekanan_darah_sistolik ? formData.tekanan_darah_sistolik : ""}
                                                onChange={e => { setFormData(prev => ({ ...prev, ['tekanan_darah_sistolik']: e.target.value ? Number(e.target.value) : null  })) }}
                                                className="text-center"
                                            />
                                            <span className="text-slate-400 font-bold">/</span>
                                            <Input
                                                type="number"
                                                placeholder="Dia"
                                                name="diastolik"
                                                value={formData.tekanan_darah_diastolik ? formData.tekanan_darah_diastolik : ""}
                                                onChange={e => { setFormData(prev => ({ ...prev, ['tekanan_darah_diastolik']: e.target.value ? Number(e.target.value) : null  })) }}
                                                className="text-center"
                                            />
                                            <span className="text-xs text-slate-400 ml-1">mmHg</span>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-2">
                                            <Label>Tinggi (cm)</Label>
                                            <Input type="number" name="tinggi_badan"
                                                value={formData.tinggi_badan ? formData.tinggi_badan : ""}
                                                onChange={e => setFormData(prev => ({ ...prev, ['tinggi_badan']: e.target.value ? Number(e.target.value) : null })) }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label>Berat (kg)</Label>
                                            <Input type="number" name="berat_badan"
                                                value={formData.berat_badan ? formData.berat_badan : ""}
                                                onChange={e => setFormData(prev => ({ ...prev, ['berat_badan']: e.target.value ? Number(e.target.value) : null })) }/>
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="grid gap-2">
                                            <Label>Gula Darah</Label>
                                            <div className="flex gap-1">
                                            <Input type="number" name="gula_darah"
                                                value={formData.gula_darah ? formData.gula_darah : ""}
                                                onChange={e =>  setFormData(prev => ({ ...prev, ['gula_darah']: e.target.value ? Number(e.target.value) : null })) }/>
                                            <select
                                                name="gula_darah_tipe"
                                                value={formData.gula_darah_tipe ? formData.gula_darah_tipe : ""}
                                                onChange={e => setFormData(prev => ({ ...prev, ['gula_darah_tipe']: e.target.value ? e.target.value : null })) }
                                                className="flex h-10 rounded-md border border-slate-200 bg-white px-2 py-2 text-xs font-bold text-black"
                                            >
                                                <option value="gds">GDS</option>
                                                <option value="dgp">DGP</option>
                                            </select>
                                            </div>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label>Asam Urat</Label>
                                            <Input type="number" step="0.1" name="asam_urat"
                                                value={formData.asam_urat ? formData.asam_urat : ""}
                                                onChange={e => setFormData(prev => ({ ...prev, ['asam_urat']: e.target.value ? Number(e.target.value) : null })) }/>
                                        </div>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label>Lingkar Perut (cm)</Label>
                                        <Input type="number" name="linkar_perut"
                                            value={formData.linkar_perut ? formData.linkar_perut : ""}
                                            onChange={e => setFormData(prev => ({ ...prev, ['linkar_perut']:  e.target.value ? Number(e.target.value) : null })) }/>
                                    </div>
                                </CardContent>
                                <div className="flex items-center justify-end gap-3 p-6 pt-0">
                                    <Button variant="outline" type="button" onClick={() => setIsModalOpen(false)}>Batal</Button>
                                    <Button type="submit">Simpan Perubahan</Button>
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

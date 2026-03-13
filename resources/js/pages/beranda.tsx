import Heading from "@/components/heading";
import AppLayout from "@/layouts/app-layout";
import { SharedData, type BreadcrumbItem } from "@/types";
import { Head, Link, usePage } from "@inertiajs/react";
import {
    ArrowRight,
    Award,
    Calendar,
    Car,
    ExternalLink,
    HeartPulse,
    Info,
    Video,
    Zap,
} from "lucide-react";
import { useState } from "react";
import { LucideIcon } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Beranda",
        href: "/beranda",
    },
];

interface Modules {
    id: string; //group | item
    label: string;
    url: string;
    color: string;
    icon: LucideIcon;
    desc: string;
}

export default function Beranda() {

    const auth = usePage<SharedData>().props.auth;
    const peran = auth.peran;

    let satuan_kerja_tahun_7600 = auth.satuan_kerja_tahun.find(item => item.kode_satuan_kerja == '7600')
    let have_7600 = false

    if(satuan_kerja_tahun_7600){
        if(peran.find(item => item.parent_id == satuan_kerja_tahun_7600.id)){
            have_7600 = true
        }
    }

    const modules: Modules[] = [];

    if(!have_7600){
        modules.push(...[{
            id: "apresiasi",
            label: "Apresiasi & Prestasi",
            icon: Award,
            color: "yellow",
            url: "/lopi/appreciate/baca",
            desc: "Ruang apresiasi untuk mengakui dedikasi pegawai.",
        }])
    }else{
        modules.push(...[
            {
                id: "jadwal",
                label: "Jadwal Kegiatan",
                icon: Calendar,
                color: "blue",
                url: "/lopi/jadwal",
                desc: "Modul sentral untuk memantau seluruh agenda pelatihan, pembinaan, dan rapat internal. Memastikan sinkronisasi antara zoom, ruang, dan pegawai.",
            },
            {
                id: "zoom",
                label: "Peminjaman Zoom",
                icon: Video,
                color: "purple",
                url: "/lopi/pinjam/zoom",
                desc: "Fasilitas reservasi akun Zoom BPS Sulawesi Barat untuk kelas daring. Menghindari tabrakan jadwal antar sesi pelatihan online.",
            },
            {
                id: "mobil",
                label: "Peminjaman Mobil",
                icon: Car,
                color: "orange",
                url: "/lopi/pinjam/mobil",
                desc: "Sistem peminjaman kendaraan operasional untuk keperluan internal.",
            },
            {
                id: "kesehatan",
                label: "Monitoring Kesehatan",
                icon: HeartPulse,
                color: "green",
                url: "/lopi/kesehatan",
                desc: "Fitur pelaporan kondisi kesehatan harian bagi seluruh pegawai memastikan lingkungan kerja dan belajar yang aman.",
            },
            {
                id: "apresiasi",
                label: "Apresiasi & Prestasi",
                icon: Award,
                color: "yellow",
                url: "/lopi/appreciate/baca",
                desc: "Ruang apresiasi untuk mengakui dedikasi pegawai.",
            },
        ])
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="w-full">
                    <Heading
                        title="Dashboard"
                        // description="Admin atur role user zoom dan ruang."
                    />

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {modules.map((m) => (
                            <div
                                key={m.id}
                                className="group bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-xl hover:border-blue-100 transition-all duration-300 flex flex-col justify-between"
                            >
                                <div>
                                    <div className="flex items-start justify-between mb-4">
                                        <div
                                            className={`p-4 rounded-2xl bg-${m.color}-50 text-${m.color}-600 group-hover:scale-110 transition-transform`}
                                        >
                                            <m.icon size={28} />
                                        </div>
                                        <div className="bg-slate-50 px-3 py-1 rounded-full flex items-center space-x-1">
                                            <Zap
                                                size={12}
                                                className="text-yellow-500"
                                            />
                                            <span className="text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">
                                                Aktif
                                            </span>
                                        </div>
                                    </div>
                                    <h3 className="text-xl font-bold text-slate-800 mb-2">
                                        {m.label}
                                    </h3>
                                    <p className="text-slate-500 text-sm leading-relaxed mb-6">
                                        {m.desc}
                                    </p>
                                </div>
                                <div className="pt-4 border-t border-slate-50 flex flex-col space-y-3 mt-auto">
                                    <Link
                                        href={m.url}
                                        className="w-full bg-slate-900 text-white py-3 rounded-xl font-bold text-sm flex items-center justify-center space-x-2 hover:bg-slate-800 transition-colors"
                                    >
                                         <span>Buka Menu</span>
                                        <ExternalLink size={16} />
                                    </Link>

                                    <div className="flex items-center justify-between px-1">

                                        <button className="flex items-center space-x-2 text-blue-600 text-xs font-bold hover:space-x-3 transition-all">
                                            <span>Pelajari Alur</span>
                                            <ArrowRight size={14} />
                                        </button>
                                        <button className="p-1 text-slate-300 hover:text-slate-600">
                                            <Info size={16} />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

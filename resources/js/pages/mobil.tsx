import AppLayout from "@/layouts/app-layout";
import {
    type Auth,
    type User,
    type BreadcrumbItem,
    type SharedData,
} from "@/types";
import { Head, router, usePage } from "@inertiajs/react";

import * as React from "react";
import Heading from "@/components/heading";

import { pinjam_mobil, pinjam_mobil_dialog_event } from "@/props/mobil/types";
import { PinjamMobilCalender } from "@/props/mobil/calender";


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Peminjaman Mobil",
        href: "/pinjam/mobil",
    },
];

export default function Index({
    tanggal_awal,
    tanggal_akhir,
    have_mobil_peran,
    user,
    data,
}: React.PropsWithChildren<{
    tanggal_awal: String;
    tanggal_akhir: String;
    have_mobil_peran: boolean;
    user: User[],
    data: pinjam_mobil[];
}>) {

    const auth = usePage<SharedData>().props.auth;
    const peran = auth.peran;

    let havePinjamMobilPeran = false;
    if(peran.find(item => {
        if(item.peran == 'mobil' && item.tipe_peran == 'global' && item.tahun == null){
            return true
        }
    })){
        havePinjamMobilPeran = true
    }else{
        havePinjamMobilPeran = false
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Peminjaman Mobil" />
                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                    <div className="w-full">
                        <Heading
                            title="Peminjaman Mobil"
                            description="Jadwal Peminjaman Mobil BPS Sulawesi Barat."
                        />
                        <PinjamMobilCalender
                            pinjam_mobil_data={data}
                            user={user}
                            havePinjamMobilPeran={havePinjamMobilPeran}>
                        </PinjamMobilCalender>
                    </div>
                </div>

        </AppLayout>
    );
}

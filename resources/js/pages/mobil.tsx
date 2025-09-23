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
import { PinjamMobilCalender } from "@/props/mobil/PinjamMobilCalender";


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Peran",
        href: "/peran",
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
    const [eventDialog, setEventDialog] = React.useState<pinjam_mobil_dialog_event>({
        status: null,
        data: null,
    });

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
                            user={user}>
                        </PinjamMobilCalender>
                    </div>
                </div>

        </AppLayout>
    );
}

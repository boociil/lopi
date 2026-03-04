import AppLayout from "@/layouts/app-layout";
import {
    type User,
    type BreadcrumbItem,
} from "@/types";
import { Head } from "@inertiajs/react";

import * as React from "react";
import Heading from "@/components/heading";

import { JadwalCalender } from "@/props/jadwal/calender";
import { jadwal } from "@/props/jadwal/types";


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Jadwal",
        href: "/lopi/jadwal",
    },
];

export default function Index({
    have_zoom_peran,
    user,
    jadwal_daftar,
    have_ruang_peran,
    ruang,
}: React.PropsWithChildren<{
    have_zoom_peran: boolean;
    user: User[];
    jadwal_daftar: jadwal[];
    have_ruang_peran: boolean;
    ruang: string[],
}>) {

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Jadwal Kegiatan" />
                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                    <div className="w-full">
                        <Heading
                            title="Jadwal Kegiatan"
                            description="Jadwal Kegiatan Sulawesi Barat."
                        />
                        <JadwalCalender
                            have_zoom_peran={have_zoom_peran}
                            user={user}
                            jadwal_data={jadwal_daftar}
                            have_ruang_peran={have_ruang_peran}
                            ruang={ruang}>
                        </JadwalCalender>
                    </div>
                </div>

        </AppLayout>
    );
}

import AppLayout from "@/layouts/app-layout";
import {
    type User,
    type BreadcrumbItem,
} from "@/types";
import { Head } from "@inertiajs/react";

import * as React from "react";
import Heading from "@/components/heading";

import { CutiCalender } from "@/props/cuti/calender";
import { cuti } from "@/props/cuti/types";


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Kalender Cuti",
        href: "/lopi/cek_cuti",
    },
];

export default function Index({
    user,
    cuti_daftar,
}: React.PropsWithChildren<{
    user: User[];
    cuti_daftar: cuti[];
}>) {

    console.log(cuti_daftar)

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kalender Cuti" />
                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                    <div className="w-full">
                        <Heading
                            title="Kalender Cuti"
                            description="Menampilkan cuti cuti pegawai untuk membantu perencanaan kerja dan memastikan ketersediaan tim setiap hari."
                        />
                        <CutiCalender
                            user={user}
                            cuti_data={cuti_daftar}>
                        </CutiCalender>
                    </div>
                </div>

        </AppLayout>
    );
}

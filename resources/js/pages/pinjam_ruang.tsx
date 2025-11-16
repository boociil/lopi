import AppLayout from "@/layouts/app-layout";
import {
    type Auth,
    type User,
    type BreadcrumbItem,
} from "@/types";
import { Head } from "@inertiajs/react";

import * as React from "react";
import Heading from "@/components/heading";

import { pinjam_ruang } from "@/props/pinjam-ruang/types";
import { PinjamRuangCalender } from "@/props/pinjam-ruang/calender";


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Pemakaian Ruang",
        href: "/pinjam/ruang",
    },
];

export default function Index({
    have_ruang_peran,
    user,
    data,
}: React.PropsWithChildren<{
    have_ruang_peran: boolean;
    user: User[],
    data: pinjam_ruang[];
}>) {

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pemakaian Ruang" />
                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                    <div className="w-full">
                        <Heading
                            title="Pemakaian Ruang"
                            description="Jadwal Pemakaian Ruang BPS Sulawesi Barat."
                        />
                        <PinjamRuangCalender
                            pinjam_ruang_data={data}
                            user={user}
                            havePinjamRuangPeran={have_ruang_peran}>
                        </PinjamRuangCalender>
                    </div>
                </div>

        </AppLayout>
    );
}

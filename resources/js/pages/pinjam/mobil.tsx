import AppLayout from "@/layouts/app-layout";
import {
    type Auth,
    type User,
    type BreadcrumbItem,
} from "@/types";
import { Head } from "@inertiajs/react";

import * as React from "react";
import Heading from "@/components/heading";

import { pinjam_mobil } from "@/props/pinjam-mobil/types";
import { PinjamMobilCalender } from "@/props/pinjam-mobil/calender";


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Pemakaian Mobil",
        href: "/pinjam/mobil",
    },
];

export default function Index({
    have_mobil_peran,
    user,
    data,
}: React.PropsWithChildren<{
    have_mobil_peran: boolean;
    user: User[],
    data: pinjam_mobil[];
}>) {

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pemakaian Mobil" />
                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                    <div className="w-full">
                        <Heading
                            title="Pemakaian Mobil"
                            description="Jadwal Pemakaian Mobil BPS Sulawesi Barat."
                        />
                        <PinjamMobilCalender
                            pinjam_mobil_data={data}
                            user={user}
                            havePinjamMobilPeran={have_mobil_peran}>
                        </PinjamMobilCalender>
                    </div>
                </div>

        </AppLayout>
    );
}

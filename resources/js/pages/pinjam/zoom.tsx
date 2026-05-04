import AppLayout from "@/layouts/app-layout";
import {
    type Auth,
    type User,
    type BreadcrumbItem,
} from "@/types";
import { Head } from "@inertiajs/react";

import * as React from "react";
import Heading from "@/components/heading";

import { PinjamZoomCalender } from "@/props/pinjam-zoom/calender";
import { pinjam_zoom, zoom_app, zoom_app_with_status } from "@/props/pinjam-zoom/types";


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Pemakaian Zoom",
        href: "/pinjam/zoom",
    },
];

export default function Index({
    have_zoom_peran,
    user,

}: React.PropsWithChildren<{
    have_zoom_peran: boolean;
    user: User[];
}>) {

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pemakaian Zoom" />
                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                    <div className="w-full">
                        <Heading
                            title="Pemakaian Zoom"
                            description="Jadwal Pemakaian Zoom BPS Sulawesi Barat."
                        />
                        <PinjamZoomCalender
                            have_zoom_peran={have_zoom_peran}
                            user={user}>
                        </PinjamZoomCalender>
                    </div>
                </div>

        </AppLayout>
    );
}

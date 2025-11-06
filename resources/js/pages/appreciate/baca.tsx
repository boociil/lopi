import AppLayout from "@/layouts/app-layout";
import {
    type User,
    type BreadcrumbItem,
} from "@/types";
import { Head} from "@inertiajs/react";

import * as React from "react";
import Heading from "@/components/heading";
import { appreciate_data} from "@/props/appreciate/types";
import StickyNoteBuka from "@/props/appreciate/buka/sticky_note_buka";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Toples Apresiasi",
        href: '/lopi/appreciate',
    },
     {
        title: "Untukmu",
        href: '/lopi/appreciate/baca',
    },
];

export interface tujuan_appreciate_data {
    tujuan: string;
    data: appreciate_data[];
}


export default function Index({
    data,
    user,
}: React.PropsWithChildren<{
    data: tujuan_appreciate_data[];
    user: User[];
}>) {

    const userDict = new Map<string, string>()
    user.forEach(element => {
        userDict.set(element.username, element.nama)
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Apresiasi" />
                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                    <div className="w-full">
                        <Heading
                            title="Kumpulan Apresiasi untuk Kamu"
                            description="Semua pesan kebaikan yang sudah diterima dan tersimpan di sini."
                        />

                        {data.map((item_data, index)  => {
                            return (
                                <>
                                    <p className="mt-10 mb-4 ml-2 font-bold text-sm text-secondary">Teruntuk {userDict.has(item_data.tujuan) ? userDict.get(item_data.tujuan) : item_data.tujuan}</p>
                                        <div className="overflow-hidden rounded-md flex flex-wrap gap-4">
                                            {item_data.data.map((item, index)  => {
                                                return (
                                                    <StickyNoteBuka data={item} userDict={userDict}>
                                                    </StickyNoteBuka>
                                                )
                                            })}
                                    </div>
                                </>
                            )
                        })};
                    </div>
                </div>
        </AppLayout>
    );
}

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
        href: '/lopi/appreciate/buka',
    },
];


export default function Index({
    data,
    user,
}: React.PropsWithChildren<{
    data: appreciate_data[];
    user: User[];
}>) {

    const [userDict, setUserDict] = React.useState<Map<string, string>>(new Map<string, string>);

    React.useEffect(() => {
        let userDict_ = new Map<string, string>()
        user.forEach(element => {
            userDict_.set(element.username, element.nama)
        });
        setUserDict(userDict_)
    }, [user]);


    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Apresiasi" />
                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                    <div className="w-full">
                        <Heading
                            title="Kumpulan Apresiasi untuk Kamu"
                            description="Semua pesan kebaikan yang sudah diterima dan tersimpan di sini."
                        />
                        <div className="overflow-hidden rounded-md flex gap-4">
                                {data.map((item, index)  => {
                                    return (
                                        <StickyNoteBuka data={item} userDict={userDict}>
                                        </StickyNoteBuka>
                                    )
                                })};
                        </div>
                    </div>
                </div>
        </AppLayout>
    );
}

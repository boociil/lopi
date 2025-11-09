import AppLayout from "@/layouts/app-layout";
import {
    type User,
    type BreadcrumbItem,
} from "@/types";
import { Head } from "@inertiajs/react";

import * as React from "react";
import Heading from "@/components/heading";
import { appreciate_data, appreciate_data_dialog_event, type appreciate_master, TargetData } from "@/props/appreciate/types";
import StickyNote from "@/props/appreciate/data/sticky_note";
import AppreciateDataProvider from "@/props/appreciate/data/provider";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Toples Apresiasi",
        href: '/lopi/appreciate',
    },
     {
        title: "Kirim",
        href: '/lopi/appreciate',
    },
];


export default function Index({
    master,
    data,
    user,
}: React.PropsWithChildren<{
    master: appreciate_master[];
    data: appreciate_data[];
    user: User[];
}>) {
    const target_data: TargetData[] = []

    master.forEach(element => {
        element.target.forEach(element2 => {
            let dapat = false
            data.forEach(element3 => {
                if(element.id == element3.master_id && element2.username == element3.tujuan){
                    target_data.push({target: element2, data: element3, number1: Math.floor(Math.random() * 5)
                                        , number2: Math.floor(Math.random() * 3)})
                    dapat = true
                }
            });
            if(!dapat){
                target_data.push({target: element2, data: null, number1: Math.floor(Math.random() * 5)
                                        , number2: Math.floor(Math.random() * 3)})
            }
        });
    });

    const [allTargetData, setAllTargetData] = React.useState<TargetData[]>(target_data);
    const [userDict, setUserDict] = React.useState<Map<string, string>>(new Map<string, string>);
    const [eventDialog, setEventDialog] = React.useState<appreciate_data_dialog_event>({
        status: null,
        data: null,
    });

    React.useEffect(() => {
        let userDict_ = new Map<string, string>()
        user.forEach(element => {
            userDict_.set(element.username, element.nama)
        });
        setUserDict(userDict_)
    }, [user]);

    function ubahData(target: TargetData){
        setAllTargetData((prev) => prev.map((e) => (e.target.username === target.target.username ? target : e)));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Apresiasi" />
                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                    <div className="w-full">
                        <Heading
                            title="Kirim Apresiasi"
                            description="Kirim apresiasi kepada rekan-rekan di kantor."
                        />
                        <div className="overflow-hidden rounded-md flex flex-wrap gap-4">
                            <AppreciateDataProvider
                                            user={user}
                                            userDict={userDict}
                                            eventDialog={eventDialog}
                                            setEventDialog={setEventDialog}
                                            ubahData={ubahData}
                                        >
                                {allTargetData.map((item, index)  => {
                                    return (
                                        <StickyNote data={item} userDict={userDict} openDialog={(data) => {
                                            setEventDialog({status: 'tambah', data: item})
                                        }}
                                        number1={item.number1}
                                        number2={item.number2}>
                                        </StickyNote>
                                    )
                                })}
                            </AppreciateDataProvider>
                        </div>
                    </div>
                </div>
        </AppLayout>
    );
}

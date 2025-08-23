import AppLayout from "@/layouts/app-layout";
import {
    type Auth,
    type User,
    type BreadcrumbItem,
    type SharedData,
} from "@/types";
import { Head, usePage } from "@inertiajs/react";

import * as React from "react";
import { Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
    Table,
    TableBody,
    TableCell,
    TableCellWrap,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { date_format, get_time_from_date_time } from "@/lib/date";
import { Badge } from "@/components/ui/badge";
import Heading from "@/components/heading";
import PeranProvider from "@/props/peran/provider";
import {
    type peran_zoom,
    type peran_dialog_event,
    type satuan_kerja_tahun,
} from "@/props/peran/types";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Peran",
        href: "/peran",
    },
];

export default function Index({
    peranZoom_,
    user_,
    satuan_kerja_tahun_,
}: React.PropsWithChildren<{
    peranZoom_: peran_zoom[];
    user_: User[];
    satuan_kerja_tahun_: satuan_kerja_tahun[];
}>) {
    const [eventDialog, setEventDialog] = React.useState<peran_dialog_event>({
        status: null,
        data: null,
    });



    const [peranZoom, setPeranZoom] = React.useState<peran_zoom[]>(peranZoom_);
    const [user, setUser] = React.useState<User[]>(user_);
    const [satuanKerjaTahun, setSatuanKerjaTahun] = React.useState<satuan_kerja_tahun[]>(satuan_kerja_tahun_);

    let userDict_ = new Map<string, string>()
    user.forEach(element => {
        userDict_.set(element.username, element.nama)
    });
    const [userDict, setUserDict] = React.useState<Map<string, string>>(userDict_);

    let satuanKerjaTahunDict_ =  new Map<string, string>()
    satuanKerjaTahun.forEach(element => {
        satuanKerjaTahunDict_.set(element.id, element.nama)
    });
    const [satuanKerjaTahunDict, setSatuanKerjaTahunDict] = React.useState<Map<string, string>>(satuanKerjaTahunDict_);


    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Peran" />
            <PeranProvider
                peranZoom={peranZoom}
                setPeranZoom={setPeranZoom}
                user={user}
                setUser={setUser}
                satuanKerjaTahun={satuanKerjaTahun}
                setSatuanKerjaTahun={setSatuanKerjaTahun}
                eventDialog={eventDialog}
                setEventDialog={setEventDialog}
                userDict={userDict}
                setUserDict={setUserDict}
                satuanKerjaTahunDict={satuanKerjaTahunDict}
                setSatuanKerjaTahunDict={setSatuanKerjaTahunDict}
            >
                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                    <div className="w-full">
                        <Heading
                            title="Pengaturan Peran"
                            description="Admin atur role user zoom dan ruang."
                        />
                        <div className="flex justify-end mt-2 mb-6 gap-x-1.5">
                            <Button
                                className="flex items-center gap-1 bg-primary text-primary-foreground"
                                onClick={() =>
                                    setEventDialog({
                                        status: "tambah",
                                        data: null,
                                    })
                                }
                            >
                                <Plus />
                                Tambah Peran
                            </Button>
                        </div>
                        <div className="overflow-hidden rounded-md border">
                            <Table>
                                <TableHeader className="bg-accent">
                                    <TableRow>
                                        <TableHead className="text-center">
                                            No
                                        </TableHead>
                                        <TableHead>Nama</TableHead>
                                        <TableHead className="text-center">
                                            Peran
                                        </TableHead>
                                        <TableHead className="text-center">
                                            Satuan Kerja
                                        </TableHead>
                                        <TableHead className="text-center">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody className="text-accent-foreground">
                                    {peranZoom.length > 0 ? (
                                        peranZoom.map((item, index) => {
                                            return (
                                                <TableRow key={"-" + item.id}>
                                                    <TableCell className="text-center">
                                                        {index+1}
                                                    </TableCell>
                                                    <TableCellWrap className="text-center">
                                                        {userDict.has(item.pemilik_username) ?
                                                            (<>{userDict.get(item.pemilik_username)}</>) :
                                                            (<>{item.pemilik_username}</>)
                                                        }
                                                    </TableCellWrap>
                                                    <TableCell className="text-center">
                                                        {(item.peran == "zoom") && (<>Zoom</>)}
                                                        {(item.peran == "ruang") && (<>Ruang</>)}
                                                    </TableCell>
                                                    <TableCellWrap className="text-center">
                                                        {satuanKerjaTahunDict.has(item.parent_id) ?
                                                            (<>{satuanKerjaTahunDict.get(item.parent_id)}</>) :
                                                            (<>- Tidak Ditemukan-</>)
                                                        }
                                                    </TableCellWrap>
                                                    <TableCell className="text-center">
                                                        <button></button>
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })
                                    ) : (
                                        <TableRow>
                                            <TableCell
                                                colSpan={5}
                                                className="h-12 text-center"
                                            >
                                                Tidak ada Peran.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                </div>
            </PeranProvider>
        </AppLayout>
    );
}

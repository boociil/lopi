import AppLayout from "@/layouts/app-layout";
import {
    type User,
    type BreadcrumbItem,
} from "@/types";
import { Head} from "@inertiajs/react";

import * as React from "react";
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

import Heading from "@/components/heading";
import { useApi } from "@/lib/API/useApi";
import { toast } from "sonner";
import { appreciate_data } from "@/props/appreciate/types";


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Toples Apresiasi",
        href: '/lopi/appreciate',
    },
     {
        title: "Appropval",
        href: '/lopi/appreciate/appropve',
    },
];


export default function Index({
    data,
    user,
}: React.PropsWithChildren<{
    data: appreciate_data[];
    user: User[];
}>) {

    const [allData, setAllData] = React.useState<appreciate_data[]>(data);
    const [userDict, setUserDict] = React.useState<Map<string, string>>(new Map<string, string>);

    React.useEffect(() => {
        let userDict_ = new Map<string, string>()
        user.forEach(element => {
            userDict_.set(element.username, element.nama)
        });
        setUserDict(userDict_)
    }, [user]);

    const axiosClient = useApi();

    function ubahData(target: appreciate_data, status : string){
        axiosClient.post(route('appreciation-aproval-ubah', {appreciation: target.id}), {status : status})
            .then(function (response) {
                const data: appreciate_data = response.data as appreciate_data
                setAllData(allData.filter(item => item.id != target.id));
                toast.success("Berhasil menambah data.");
            })
            .catch(function (error) {
                toast.success("Gagal menambah data.");
            });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Apresiasi" />
                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                    <div className="w-full">
                        <Heading
                            title="Approve Apresiasi"
                            description="Approve apresiasi rekan-rekan di kantor."
                        />
                        <div className="overflow-hidden rounded-md flex gap-4">
                            <div className="overflow-hidden rounded-md border w-full">
                                <Table className="w-full">
                                    <TableHeader className="bg-accent">
                                        <TableRow>
                                            <TableHead className="text-center">
                                                No
                                            </TableHead>
                                            <TableHead className="text-left">
                                                Pesan
                                            </TableHead>
                                            <TableHead className="text-left">
                                                Status
                                            </TableHead>
                                            <TableHead>
                                                Aksi
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody className="text-accent-foreground">
                                        {allData.length > 0 ? (
                                            allData.map((item, index) => {
                                                return (
                                                    <TableRow key={"-" + item.id}>
                                                        <TableCell className="text-center">
                                                            {index+1}
                                                        </TableCell>
                                                        <TableCellWrap>
                                                            <div dangerouslySetInnerHTML={{ __html: item.pesan }} />
                                                        </TableCellWrap>
                                                            <TableCellWrap>
                                                            {(item.status == "0") && (<>Belum</>)}
                                                            {(item.status == "1") && (<>Ditolak</>)}
                                                            {(item.status == "2") && (<>Disetujui</>)}

                                                        </TableCellWrap>
                                                        <TableCell className="text-center gap-1">
                                                            <Button variant="outline"
                                                                onClick={e =>{
                                                                    ubahData(item, "1")
                                                                }}
                                                                >Tolak</Button>
                                                            <Button variant="outline"
                                                                onClick={e =>{
                                                                    ubahData(item, "2")
                                                                }}
                                                                >Setujui</Button>
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
                                                    Tidak ada Sesi.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </div>

                        </div>

                    </div>
                </div>
        </AppLayout>
    )
}

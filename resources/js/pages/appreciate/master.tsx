import AppLayout from "@/layouts/app-layout";
import {
    type User,
    type BreadcrumbItem,
} from "@/types";
import { Head } from "@inertiajs/react";

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
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/components/ui/alert-dialog"
import Heading from "@/components/heading";
import { useApi } from "@/lib/API/useApi";
import { progress as progressIndikator } from '@inertiajs/react'
import { toast } from "sonner";
import { appreciate_master, appreciate_master_dialog_event } from "@/props/appreciate/types";
import AppreciateMaterProvider from "@/props/appreciate/master/provider";
import { format } from "date-fns";
import { id } from "date-fns/locale";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Toples Apresiasi",
        href: '/lopi/appreciate',
    },
     {
        title: "Master",
        href: '/lopi/appreciate/master',
    },
];

export default function Index({
    data,
    user,
}: React.PropsWithChildren<{
    data: appreciate_master[];
    user: User[];
}>) {
    const [allMaster, setAllMaster] = React.useState<appreciate_master[]>(data);
    const [eventDialog, setEventDialog] = React.useState<appreciate_master_dialog_event>({
        status: null,
        data: null,
    });

    const axiosClient = useApi();
    const [userDict, setUserDict] = React.useState<Map<string, string>>(new Map<string, string>);

    React.useEffect(() => {
        let userDict_ = new Map<string, string>()
        user.forEach(element => {
            userDict_.set(element.username, element.nama)
        });
        setUserDict(userDict_)
    }, [user]);

    function hapusPeran(appreciate_master_: appreciate_master){
        progressIndikator.start()
        axiosClient.post(route('appreciation-master-hapus', {'master': appreciate_master_.id}))
            .then(function (response) {
                setAllMaster((prev) => prev.filter((item) => item.id !== appreciate_master_.id));
                progressIndikator.finish()
                toast.success("Berhasil meghapus master.");
            })
            .catch(function (error) {
                progressIndikator.finish()
                toast.success("Gagal meghapus master.");
            });
    };

    function addAppreciateMaster(appreciate_master_: appreciate_master){
        setAllMaster([...allMaster, appreciate_master_]);
    };

    function updateAppreciateMaster(appreciate_master_: appreciate_master){
        setAllMaster((prev) => prev.map((e) => (e.id === appreciate_master_.id ? appreciate_master_ : e)));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Apresiasi Master" />
            <AppreciateMaterProvider
                appreciateMaster={allMaster}
                user={user}
                userDict={userDict}
                eventDialog={eventDialog}
                setEventDialog={setEventDialog}
                addAppreciateMaster={addAppreciateMaster}
                updateAppreciateMaster={updateAppreciateMaster}
            >
                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                    <div className="w-full">
                        <Heading
                            title="Pengaturan Master"
                            description="Admin atur yang diapresiasi menurut tanggal."
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
                                Tambah Master
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
                                        <TableHead className="text-left">
                                            Awal
                                        </TableHead>
                                        <TableHead className="text-left">
                                            Akhir
                                        </TableHead>
                                        <TableHead>
                                            Target
                                        </TableHead>
                                        <TableHead className="text-center">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody className="text-accent-foreground">
                                    {allMaster.length > 0 ? (
                                        allMaster.map((item, index) => {
                                            return (
                                                <TableRow key={"-" + item.id}>
                                                    <TableCell className="text-center">
                                                        {index+1}
                                                    </TableCell>
                                                    <TableCellWrap>
                                                        {item.nama}
                                                    </TableCellWrap>
                                                    <TableCellWrap>
                                                        {format(new Date(item.isi_awal), "EEEE, MMMM d, yyyy", {locale: id})}
                                                    </TableCellWrap>
                                                     <TableCellWrap>
                                                        {format(new Date(item.isi_akhir), "EEEE, MMMM d, yyyy", {locale: id})}
                                                    </TableCellWrap>
                                                    <TableCellWrap className="text-left">
                                                        {item.target.length > 0 ? (
                                                            item.target.map((item, index) => {
                                                                if(userDict.has(item.username)){
                                                                    return (
                                                                        <>- {userDict.get(item.username)}<br/></>
                                                                    )
                                                                }else{
                                                                    return item.username+", "
                                                                }
                                                            })

                                                        ): (<>- Tidak ada -</>)}

                                                    </TableCellWrap>
                                                    <TableCell className="text-center">
                                                        <Button variant="outline"
                                                            onClick={e =>{
                                                                setEventDialog({
                                                                    status: "ubah",
                                                                    data: item,
                                                                })
                                                            }}
                                                            >Ubah</Button>
                                                        <AlertDialog>
                                                            <AlertDialogTrigger asChild>
                                                                <Button variant="outline">Hapus</Button>
                                                            </AlertDialogTrigger>
                                                            <AlertDialogContent>
                                                                <AlertDialogHeader>
                                                                <AlertDialogTitle className="text-secondary">Apakah kamu yakin?</AlertDialogTitle>
                                                                    <AlertDialogDescription>
                                                                        Apakah anda yakin untuk menghapus master {item.nama} ?
                                                                    </AlertDialogDescription>
                                                                </AlertDialogHeader>
                                                                <AlertDialogFooter>
                                                                    <AlertDialogCancel className="text-secondary">Cancel</AlertDialogCancel>
                                                                    <AlertDialogAction
                                                                        onClick={e =>{
                                                                            hapusPeran(item)}}>Hapus</AlertDialogAction>
                                                                </AlertDialogFooter>
                                                            </AlertDialogContent>
                                                        </AlertDialog>
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
            </AppreciateMaterProvider>
        </AppLayout>
    )
}

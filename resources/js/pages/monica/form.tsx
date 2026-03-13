import AppLayout from "@/layouts/app-layout";
import {
    type User,
    type BreadcrumbItem,
} from "@/types";
import { Head} from "@inertiajs/react";

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
import PeranProvider from "@/props/peran/provider";
import {
    type peran_lopi,
    type peran_dialog_event,
    type satuan_kerja_tahun,
} from "@/props/peran/types";
import { useApi } from "@/lib/API/useApi";
import { progress as progressIndikator } from '@inertiajs/react'
import { toast } from "sonner";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Peran",
        href: "/peran",
    },
];

export default function Index({
    peranLopi,
    user,
    satuanKerjaTahun,
}: React.PropsWithChildren<{
    peranLopi: peran_lopi[];
    user: User[];
    satuanKerjaTahun: satuan_kerja_tahun[];
}>) {

    const [allPeran, setAllPeran] = React.useState<peran_lopi[]>(peranLopi);
    const [eventDialog, setEventDialog] = React.useState<peran_dialog_event>({
        status: null,
        data: null,
    });

    const axiosClient = useApi();
    const [userDict, setUserDict] = React.useState<Map<string, string>>(new Map<string, string>);
    const [satuanKerjaTahunDict, setSatuanKerjaTahunDict] = React.useState<Map<string, string>>(new Map<string, string>);

    React.useEffect(() => {
        let userDict_ = new Map<string, string>()
        user.forEach(element => {
            userDict_.set(element.username, element.nama)
        });
        setUserDict(userDict_)

        let satuanKerjaTahunDict_ =  new Map<string, string>()
        satuanKerjaTahun.forEach(element => {
            satuanKerjaTahunDict_.set(element.id, element.nama)
        });
        setSatuanKerjaTahunDict(satuanKerjaTahunDict_)
    }, [user, satuanKerjaTahun]);

    function hapusPeran(peran_lopi_: peran_lopi){
        progressIndikator.start()
        axiosClient.post(route('peran-hapus', {'peran_id': peran_lopi_.id}))
            .then(function (response) {
                setAllPeran((prev) => prev.filter((item) => item.id !== peran_lopi_.id));
                progressIndikator.finish()
                toast.success("Berhasil meghapus peran.");
            })
            .catch(function (error) {
                progressIndikator.finish()
                toast.success("Gagal meghapus peran.");
            });
    };

    function addPeran(peran_lopi_: peran_lopi){
        setAllPeran([...allPeran, peran_lopi_]);
    };


    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Peran" />
            <PeranProvider
                peranLopi={allPeran}
                user={user}
                satuanKerjaTahun={satuanKerjaTahun}
                eventDialog={eventDialog}
                setEventDialog={setEventDialog}
                userDict={userDict}
                satuanKerjaTahunDict={satuanKerjaTahunDict}
                addPeran={addPeran}
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
                                        <TableHead>
                                            Satuan Kerja
                                        </TableHead>
                                        <TableHead className="text-center">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody className="text-accent-foreground">
                                    {allPeran.length > 0 ? (
                                        allPeran.map((item, index) => {
                                            return (
                                                <TableRow key={"-" + item.id}>
                                                    <TableCell className="text-center">
                                                        {index+1}
                                                    </TableCell>
                                                    <TableCellWrap>
                                                        {userDict.has(item.pemilik_username) ?
                                                            (<>{userDict.get(item.pemilik_username)}</>) :
                                                            (<>{item.pemilik_username}</>)
                                                        }
                                                    </TableCellWrap>
                                                    <TableCell className="text-center">
                                                        {(item.peran == "zoom") && (<>Pinjam Zoom</>)}
                                                        {(item.peran == "ruang") && (<>Pinjam Ruang</>)}
                                                        {(item.peran == "mobil") && (<>Pinjam Mobil</>)}
                                                        {(item.peran == "appreciate") && (<>Toples Apresiasi</>)}
                                                        {(item.peran == "arsip") && (<>Arsip</>)}
                                                    </TableCell>
                                                    <TableCellWrap>
                                                        {satuanKerjaTahunDict.has(item.parent_id) ?
                                                            (<>{satuanKerjaTahunDict.get(item.parent_id)}</>) :
                                                            (<>- Tidak Ditemukan-</>)
                                                        }
                                                    </TableCellWrap>
                                                    <TableCell className="text-center">
                                                        <AlertDialog>
                                                            <AlertDialogTrigger asChild>
                                                                <Button variant="outline">Hapus</Button>
                                                            </AlertDialogTrigger>
                                                            <AlertDialogContent>
                                                                <AlertDialogHeader>
                                                                <AlertDialogTitle className="text-secondary">Apakah kamu yakin?</AlertDialogTitle>
                                                                    <AlertDialogDescription>
                                                                        Apakah anda yakin untuk menghapus peran {item.peran} <br/>
                                                                        pada pengguna {userDict.has(item.pemilik_username) ? userDict.get(item.pemilik_username) : item.pemilik_username} ?
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

import { useForm } from "@inertiajs/react";

import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from "@/components/ui/command"
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover"


import { Label } from "@/components/ui/label";

import { Button } from "@/components/ui/button";
import { useAppreciateMaterContext } from "../context";
import React, { useEffect } from "react";
import InputError from "@/components/input-error";

import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Check, ChevronsUpDown, LoaderCircle } from "lucide-react"
import { cn } from "@/lib/utils"
import { toast } from "sonner";
import { progress as progressIndikator } from '@inertiajs/react'
import { appreciate_master } from "../../types";
import { Input } from "@/components/ui/input";
import { DatePicker22 } from "@/components/ui/date-picker";
import { formatDate } from "@/components/calender_modules/helpers";
import { Table, TableBody, TableCell, TableCellWrap, TableHead, TableHeader, TableRow } from "@/components/ui/table";

type PeranForm = {
    nama: string;
    isi_awal: string;
    isi_akhir: string;
    target: string[];
    status: string;
};

export default function DialogFormManage() {
    const { eventDialog, setEventDialog, userDict, axiosClient, addAppreciateMaster, updateAppreciateMaster, user } = useAppreciateMaterContext();
    const [openPengguna, setOpenPengguna] = React.useState(false)
    const [processing, setProcessing] = React.useState(false)

    const { data, setData, errors, setError, clearErrors } = useForm<
        Required<PeranForm>
    >({
        nama: "",
        isi_awal: "",
        isi_akhir: "",
        target: [],
        status: "",
    });

    useEffect(() => {
        if(eventDialog.data){
            setData("nama", eventDialog.data.nama);
            setData("isi_awal", eventDialog.data.isi_awal);
            setData("isi_akhir", eventDialog.data.isi_akhir);

            const target_: string[] = []
            eventDialog.data.target.forEach(element => {
                target_.push(element.username)
            });
            setData("target", target_);
            setData("status", "");
        }
    }, [eventDialog]);

    const submit: React.FormEventHandler = (e) => {
        e.preventDefault();
        clearErrors("nama", "isi_awal","isi_akhir")
        setProcessing(true)
        console.log(eventDialog)
         if(eventDialog.status == 'tambah'){

            axiosClient.post(route("appreciation-master-tambah"), data)
                .then(function (response: any) {
                    try {
                        const return_data: appreciate_master = response.data.data as appreciate_master;
                        addAppreciateMaster(return_data)
                        progressIndikator.finish()
                        toast.success('Berhasil menambahkan data baru')
                        clearErrors()
                        close_dialog()
                    } catch (error) {
                        console.log(error)
                        progressIndikator.finish()
                        toast.error('Gagal menambahkan data baru')
                    }
                    setProcessing(false)
                })
                .catch(function (error: any) {
                    console.log(error)
                    progressIndikator.finish()
                    toast.error('Gagal menambahkan peran baru')

                    clearErrors();
                    if (error.response?.status === 422) {
                        Object.keys(error.response.data.errors).forEach((key) => {
                            setError(key, error.response.data.errors[key][0]);
                        });
                    }
                    setProcessing(false)
                });
         }else if(eventDialog.status == 'ubah' && eventDialog.data != null){
            axiosClient.post(route("appreciation-master-ubah", {'master': eventDialog.data?.id}), data)
                .then(function (response: any) {
                    try {
                        const return_data: appreciate_master = response.data.data as appreciate_master;
                        updateAppreciateMaster(return_data)
                        progressIndikator.finish()
                        toast.success('Berhasil mengubah data baru')
                        clearErrors()
                        close_dialog()
                    } catch (error) {
                        console.log(error)
                        progressIndikator.finish()
                        toast.error('Gagal mengubah data baru')
                    }
                    setProcessing(false)
                })
                .catch(function (error: any) {
                    console.log(error)
                    progressIndikator.finish()
                    toast.error('Gagal mengubah peran baru')

                    clearErrors();
                    if (error.response?.status === 422) {
                        Object.keys(error.response.data.errors).forEach((key) => {
                            setError(key, error.response.data.errors[key][0]);
                        });
                    }
                    setProcessing(false)
                });
         }

    };

    function deleteUser(username: string){
        setData('target', data.target.filter(item => item != username))
    }

    function addUser(username: string){
        if(!data.target.includes(username)){
            const newTaeget: string[] = data.target
            newTaeget.push(username)
            setData('target', newTaeget)
        }
    }

    function close_dialog(){
        clearErrors("nama", "isi_awal","isi_akhir")
        setData("nama", "");
        setData("isi_awal", "");
        setData("isi_akhir", "");
        setData("target", []);
        setEventDialog({ status: null, data: null })
        return true
    };

     if (eventDialog.status == null) {
        return <></>;
    }

     return (
        <Dialog open={true} onOpenChange={close_dialog}>
            <DialogContent className="sm:max-w-[425px]">
                <form className="flex flex-col gap-6" onSubmit={submit}>
                    <DialogHeader className="text-accent-foreground">
                        <DialogTitle>Tambah/Ubah Master</DialogTitle>
                        <DialogDescription>
                            Isi isian diatas. Klik Simpan Jika Selesai.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4">
                        <div className="grid gap-3">
                            <Label className="text-secondary" htmlFor="title-1">
                                Nama
                            </Label>
                            <Input
                                id="title-1"
                                name="title"
                                required
                                value={data.nama}
                                onChange={(e) =>
                                    setData("nama", e.target.value)
                                }
                                className="text-secondary"
                            />
                            <InputError message={errors.nama} />
                        </div>
                        <div className="grid gap-3">
                            <Label
                                className="text-secondary"
                                htmlFor="starttime-1"
                            >
                                Pengisian Awal
                            </Label>
                            <DatePicker22
                                value={new Date(data.isi_awal)}
                                setValue={(value) => {
                                    setData("isi_awal", formatDate(value.toISOString()));
                                }}
                                modal={true}
                            />
                            <InputError message={errors.isi_awal} />
                        </div>
                        <div className="grid gap-3">
                            <Label
                                className="text-secondary"
                                htmlFor="starttime-1"
                            >
                                Pengisian Akhir
                            </Label>
                            <DatePicker22
                                value={new Date(data.isi_akhir)}
                                setValue={(value) => {
                                    setData("isi_akhir", formatDate(value.toISOString()));
                                }}
                                modal={true}
                            />
                            <InputError message={errors.isi_akhir} />
                        </div>


                        <div className="grid gap-3">
                            <Label className="text-secondary" htmlFor="user-1">Pengguna</Label>
                            <Popover open={openPengguna} onOpenChange={setOpenPengguna} modal={true}>
                                <PopoverTrigger asChild>
                                    <Button
                                        variant="outline"
                                        role="combobox"
                                        className="justify-between text-secondary"
                                    >
                                        Pilih Target...
                                        <ChevronsUpDown className="opacity-50" />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent className="p-0 w-full">
                                    <Command>
                                        <CommandInput placeholder="Cari Pengguna..." className="h-9" />
                                        <CommandList>
                                            <CommandEmpty>Tidak ditemukan.</CommandEmpty>
                                            <CommandGroup>
                                            {user.map((item) => (
                                                <CommandItem
                                                    key={item.username}
                                                    value={item.username}
                                                    onSelect={(currentValue) => {
                                                        addUser(currentValue)
                                                        // setData("username", currentValue === data.username ? "" : currentValue)
                                                        setOpenPengguna(false)
                                                    }}
                                                >
                                                    {item.nama}
                                                {/* {userDict.has(item.pemilik_username) ? userDict.get(item.pemilik_username) : item.pemilik_username} */}
                                                {/* <Check
                                                    className={cn(
                                                    "ml-auto",
                                                        data.username === item.pemilik_username ? "opacity-100" : "opacity-0"
                                                    )}
                                                /> */}
                                                </CommandItem>
                                            ))}
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>
                        </div>

                    <InputError message={errors.status} />
                    <div className="grid gap-3">
                        <Label className="text-secondary" htmlFor="user-1">Daftar Target</Label>
                        <Table>
                            <TableHeader className="bg-accent">
                                <TableRow>
                                    <TableHead>Nama</TableHead>
                                    <TableHead className="text-center">
                                        Aksi
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody className="text-accent-foreground">
                                {data.target.length > 0 ? (
                                    data.target.map((item, index) => {
                                        return (
                                            <TableRow key={"-" + index}>
                                                <TableCellWrap className="text-left">
                                                    {userDict.has(item) ?
                                                        (userDict.get(item))
                                                    : (item)}

                                                </TableCellWrap>
                                                <TableCell className="text-center">
                                                    <Button type="button" variant="outline" onClick={() => {deleteUser(item)}}>Hapus</Button>
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
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button  className="text-accent-foreground" variant="outline">Batal</Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

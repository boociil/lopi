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
import { usePeranContext } from "../context";
import React from "react";
import InputError from "@/components/input-error";

import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { type peran_lopi } from "../types";
import { Check, ChevronsUpDown, LoaderCircle } from "lucide-react"
import { cn } from "@/lib/utils"
import { toast } from "sonner";
import { progress as progressIndikator } from '@inertiajs/react'

type PeranForm = {
    peran: string;
    username: string;
    satuan_kerja_tahun: string;
    status: string;
};

export default function DialogFormManage() {
    const { eventDialog, setEventDialog, satuanKerjaTahun, userDict, axiosClient, addPeran } = usePeranContext();
    const [penggunaArr, setPenggunaArr] = React.useState<peran_lopi[]>([])
    const [openPengguna, setOpenPengguna] = React.useState(false)
    const [processing, setProcessing] = React.useState(false)

    const { data, setData, errors, setError, clearErrors } = useForm<
        Required<PeranForm>
    >({
        peran: "zoom",
        username: "",
        satuan_kerja_tahun: "",
        status: "",
    });

    const submit: React.FormEventHandler = (e) => {
        e.preventDefault();
        clearErrors("peran", "username","satuan_kerja_tahun","status")
        if(data.username == ""){
            setError("username", "Pengguna harus terisi")
            return
        }
        setProcessing(true)
        axiosClient.post(route("peran-tambah"), data)
            .then(function (response: any) {
                try {
                    const return_data: peran_lopi = response.data as peran_lopi;
                    addPeran(return_data)
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
    };

    function setPenggunaArrMeta(value: String){
        let find = satuanKerjaTahun.find(item => item.id == value)
        if(find){
            let username_unique: String [] = []
            let peran_ :  peran_lopi [] = []
            find.peran_daftar.forEach(element => {
                if(!username_unique.includes(element.pemilik_username)){
                    username_unique.push(element.pemilik_username)
                    peran_.push(element)
                }
            });
            setPenggunaArr(peran_)
        }
    }

    function close_dialog(){
        clearErrors("peran", "username","satuan_kerja_tahun","status")
        setData("peran", "zoom");
        setData("username", "");
        setData("satuan_kerja_tahun", "");
        setData("status", "");
        setEventDialog({ status: null, data: null })
        setPenggunaArr([])
        return true
    };

    if(eventDialog.status == 'tambah'){
         return (
            <Dialog open={true} onOpenChange={close_dialog}>
                <DialogContent className="sm:max-w-[425px]">
                    <form className="flex flex-col gap-6" onSubmit={submit}>
                        <DialogHeader className="text-accent-foreground">
                            <DialogTitle>Tambah Peran</DialogTitle>
                            <DialogDescription>
                                Isi isian diatas. Klik Simpan Jika Selesai.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-4">
                            <div className="grid gap-3">
                                <Label className="text-secondary" htmlFor="peran-1">Peran</Label>
                                <Select required
                                    defaultValue={data.peran}
                                    onValueChange={(value) => setData(
                                                "peran",
                                               value)}>
                                    <SelectTrigger
                                        name="peran"
                                        className="text-secondary">
                                        <SelectValue placeholder="Pilih Peran" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectItem value="zoom">Zoom</SelectItem>
                                            <SelectItem value="ruang">Ruang</SelectItem>
                                            <SelectItem value="mobil">Mobil</SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>

                                <InputError message={errors.peran} />
                            </div>
                            <div className="grid gap-3">
                                <Label className="text-secondary" htmlFor="satuan-kerja-tahun-1">Satuan Kerja</Label>
                                <Select required
                                    defaultValue={data.satuan_kerja_tahun}
                                    onValueChange={(value) => {
                                        setData("satuan_kerja_tahun",value)
                                        setData("username", "")
                                        setPenggunaArrMeta(value)
                                    }}>
                                    <SelectTrigger
                                        name="satuan_kerja"
                                        className="text-secondary">
                                        <SelectValue placeholder="Pilih Satuan Kerja" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectGroup>
                                                {satuanKerjaTahun.map((item, index) =>
                                                    ( <SelectItem key={item.id} value={item.id}>{item.nama}</SelectItem>)
                                                )}
                                            </SelectGroup>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.satuan_kerja_tahun} />
                            </div>
                            {data.satuan_kerja_tahun ?
                            <>
                                <div className="grid gap-3">
                                    <Label className="text-secondary" htmlFor="user-1">Pengguna</Label>
                                    <Popover open={openPengguna} onOpenChange={setOpenPengguna} modal={true}>
                                        <PopoverTrigger asChild>
                                            <Button
                                                variant="outline"
                                                role="combobox"
                                                className="justify-between text-secondary"
                                            >
                                                { (data.username != "")
                                                    ? userDict.has(data.username) ? userDict.get(data.username) : data.username
                                                    : "Pilih Pengguna..."}
                                                <ChevronsUpDown className="opacity-50" />
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent className="p-0 w-full">
                                            <Command>
                                                <CommandInput placeholder="Cari Pengguna..." className="h-9" />
                                                <CommandList>
                                                    <CommandEmpty>Tidak ditemukan.</CommandEmpty>
                                                    <CommandGroup>
                                                    {penggunaArr.map((item) => (
                                                        <CommandItem
                                                            key={item.pemilik_username}
                                                            value={item.pemilik_username}
                                                            onSelect={(currentValue) => {
                                                                setData("username", currentValue === data.username ? "" : currentValue)
                                                                setOpenPengguna(false)
                                                            }}
                                                        >
                                                        {userDict.has(item.pemilik_username) ? userDict.get(item.pemilik_username) : item.pemilik_username}
                                                        <Check
                                                            className={cn(
                                                            "ml-auto",
                                                                data.username === item.pemilik_username ? "opacity-100" : "opacity-0"
                                                            )}
                                                        />
                                                        </CommandItem>
                                                    ))}
                                                    </CommandGroup>
                                                </CommandList>
                                            </Command>
                                        </PopoverContent>
                                    </Popover>
                                    <InputError message={errors.username} />
                                </div>
                            </>
                            : <></>}
                        <InputError message={errors.status} />
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
    }else {
        return <></>;
    }


}

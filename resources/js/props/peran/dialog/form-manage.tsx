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

import { Label } from "@/components/ui/label";

import { Button } from "@/components/ui/button";
import { usePeranContext } from "../context";
import React from "react";
import InputError from "@/components/input-error";

import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { peran_lopi } from "../types";


type PeranForm = {
    peran: string;
    username: string;
    satuan_kerja_tahun: string;
    status: string;
};

export default function DialogFormManage() {
    const { eventDialog, setEventDialog, satuanKerjaTahun, userDict } = usePeranContext();
    const [penggunaArr, setPenggunaArr] = React.useState<peran_lopi[]>([])

    const { data, setData, post, processing, errors, reset } = useForm<
        Required<PeranForm>
    >({
        peran: "zoom",
        username: "",
        satuan_kerja_tahun: "",
        status: "",
    });

    const submit: React.FormEventHandler = (e) => {
        e.preventDefault();
        post(route("peran-tambah"), {
            onSuccess: () => {
                close_dialog()
            }
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
                                    <Select required
                                        defaultValue={data.username}
                                        onValueChange={(value) => setData(
                                            "username",
                                            value)}>
                                        <SelectTrigger
                                            name="satuan_kerja"
                                            className="text-secondary">
                                                <SelectValue placeholder="Pilih Pengguna" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {penggunaArr.map((item, index) =>
                                                (
                                                    <SelectItem key={item.id}  value={item.pemilik_username}>
                                                        { userDict.has(item.pemilik_username) ? <>{userDict.get(item.pemilik_username)}</> : <>{item.pemilik_username}</> }
                                                    </SelectItem>
                                                )
                                            )}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.satuan_kerja_tahun} />
                                </div>
                            </>
                            : <></>}
                        {errors.status && <div className="mb-4 text-center text-sm font-medium text-red-400">{errors.status}</div>}
                        </div>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button  className="text-accent-foreground" variant="outline">Batal</Button>
                            </DialogClose>
                            <Button type="submit">Simpan</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        );
    }else {
        return <></>;
    }


}

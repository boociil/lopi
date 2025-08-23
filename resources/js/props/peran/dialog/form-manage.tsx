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

import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger } from "@/components/ui/select";


type PeranForm = {
    peran: string;
    username: string;
    satuan_kerja_tahun: string;
};

export default function DialogFormManage() {
    const { eventDialog, setEventDialog, satuanKerjaTahun, userDict } = usePeranContext();

    const { data, setData, post, processing, errors, reset } = useForm<
        Required<PeranForm>
    >({
        peran: "",
        username: "",
        satuan_kerja_tahun: "",
    });

    const submit: React.FormEventHandler = (e) => {
        e.preventDefault();
        post(route("zoom-tambah"), {
            onSuccess: () => {
                setData("peran", "");
                setData("username", "");
                setData("satuan_kerja_tahun", "");
            },
        });
    };

    function close_dialog(){
        setEventDialog({ status: null, data: null })
        return true
    };

    if(eventDialog.status == 'tambah'){
         return (
            <Dialog open={true} onOpenChange={close_dialog}>
                <DialogContent className="sm:max-w-[425px]">
                    <form className="flex flex-col gap-6" onSubmit={submit}>
                        <DialogHeader className="text-accent-foreground">
                            <DialogTitle>Tambah Jadwal Zoom</DialogTitle>
                            <DialogDescription>
                                Isi isian diatas. Klik Simpan Jika Selesai.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-4">
                            <div className="grid gap-3">
                                <Label className="text-secondary" htmlFor="peran-1">Peran</Label>
                                <Select required>
                                    <SelectTrigger
                                        name="peran"
                                        className="text-secondary"
                                        value={data.peran}
                                        onChange={(e) =>
                                            setData(
                                                "peran",
                                               e.target.value,
                                            )
                                        }>
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
                                <Select required>
                                    <SelectTrigger
                                        name="satuan_kerja"
                                        className="text-secondary"
                                        value={data.satuan_kerja_tahun}
                                        onChange={(e) =>
                                            setData(
                                                "satuan_kerja_tahun",
                                                e.target.value,
                                            )
                                        }>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            {satuanKerjaTahun.map((item, index) =>
                                                ( <SelectItem value={item.id}>{item.nama}</SelectItem>)
                                            )}
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.satuan_kerja_tahun} />
                            </div>
                            <div className="grid gap-3">
                                <Label className="text-secondary" htmlFor="satuan-kerja-tahun-1">Satuan Kerja</Label>
                                <Select required>
                                    <SelectTrigger
                                        name="satuan_kerja"
                                        className="text-secondary"
                                        value={data.satuan_kerja_tahun}
                                        onChange={(e) =>
                                            setData(
                                                "satuan_kerja_tahun",
                                                e.target.value,
                                            )
                                        }>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            {satuanKerjaTahun.map((item, index) =>
                                                ( <SelectItem value={item.id}>{item.nama}</SelectItem>)
                                            )}
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.satuan_kerja_tahun} />
                            </div>
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

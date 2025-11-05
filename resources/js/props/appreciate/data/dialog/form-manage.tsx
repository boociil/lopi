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
import { useAppreciateDataContext } from "../context";
import React, { useEffect } from "react";

import { toast } from "sonner";
import { progress as progressIndikator } from '@inertiajs/react'
import { appreciate_data } from "../../types";
import { Input } from "@/components/ui/input";
import TipTap from "../TipTap/tip-tap";
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";

type PeranForm = {
    tujuan: string;
    pesan: string;
    status_anonim: string;
};

export default function DialogFormManage() {
    const { eventDialog, setEventDialog, userDict, axiosClient, ubahData, user } = useAppreciateDataContext();
     const [processing, setProcessing] = React.useState(false)

    const { data, setData, errors, setError, clearErrors } = useForm<
        Required<PeranForm>
    >({
        tujuan: "",
        status_anonim: "0",
        pesan: "<p>Tulis Pesan DIsini</p>"
    });

    useEffect(() => {
        if(eventDialog.data){
            if(eventDialog.data.data){
                setData("status_anonim", eventDialog.data.data.status_anonim);
                setData("tujuan", eventDialog.data.target.username);
                setData("pesan", eventDialog.data.data.pesan);
            }else{
                setData("status_anonim", '0');
                setData("tujuan", eventDialog.data.target.username);
                setData("pesan", '<p></p>');
            }
        }
    }, [eventDialog]);

    const submit: React.FormEventHandler = (e) => {
        e.preventDefault();
        clearErrors("status_anonim", "tujuan","pesan")
        setProcessing(true)

        progressIndikator.start()
        axiosClient.post(route('appreciation-tambah'), data)
            .then(function (response) {
                const data: appreciate_data = response.data as appreciate_data
                if(eventDialog.data){
                    ubahData({target: eventDialog.data.target, data: data, number1: eventDialog.data.number1, number2: eventDialog.data.number2});
                }
                progressIndikator.finish()
                toast.success("Berhasil menambah data.");
                clearErrors()
                close_dialog()
                setProcessing(false)
            })
            .catch(function (error) {
                progressIndikator.finish()
                toast.success("Gagal menambah data.");
                setProcessing(false)
            });

    };
    function close_dialog(){
        clearErrors("tujuan", "status_anonim","pesan")
        setData("tujuan",  "")
        setData("status_anonim",  "0")
        setData("pesan",  "")
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
                        <DialogTitle>Tulis Appresiasi</DialogTitle>
                        <DialogDescription>
                            Isi isian diatas. Klik Simpan Jika Selesai.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4">
                        <div className="grid gap-3">
                            <Label className="text-secondary" htmlFor="title-1">
                                Kepada
                            </Label>
                            <Input
                                id="title-1"
                                name="title"
                                required
                                value={userDict.has(data.tujuan) ? userDict.get(data.tujuan) : ""}
                                disabled
                                className="text-secondary"
                            />
                        </div>
                    </div>
                    <div className="grid gap-3">
                        <Label className="text-secondary" htmlFor="peran-1">Anonim</Label>
                        <Select required
                            defaultValue={data.status_anonim}
                            onValueChange={(value) => setData(
                                        "status_anonim",
                                        value)}>
                            <SelectTrigger
                                name="peran"
                                className="text-secondary">
                                <SelectValue placeholder="Pilih Anonim" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup>
                                    <SelectItem value="0">Tidak Anonim</SelectItem>
                                    <SelectItem value="1">Anonim</SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="grid gap-4">
                        <div className="grid gap-3">
                            <Label className="text-secondary" htmlFor="title-1">
                                Pesan
                            </Label>
                            <TipTap
                                html={data.pesan}
                                setHtml={(value: string) => {
                                    console.log(value)
                                    setData("pesan", value)
                                }}
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button  className="text-accent-foreground" variant="outline">Batal</Button>
                        </DialogClose>
                        <Button type="submit">
                            Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

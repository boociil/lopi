import {router, useForm, usePage } from "@inertiajs/react";

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
import { DateTimePicker } from "@/components/ui/datetime-picker";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useZoomContext } from "../context";
import React, { useEffect, useState } from "react";
import InputError from "@/components/input-error";

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
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from "@/components/ui/select";
import { type SharedData } from "@/types";


type ZoomForm = {
    topic: string;
    password: string;
    start_time: string;
    start_time_penyesuaian: number;
    duration: number;
    zoom_status:  number;
    zoom_akun: string;
    status: string
};

export default function DialogFormManage() {
    const { eventDialog, setEventDialog, haveZoomRole } = useZoomContext();

    const { data, setData, post, processing, errors, reset } = useForm<
        Required<ZoomForm>
    >({
        topic: "Kegiatan",
        password: "BPS",
        start_time: new Date().toISOString(),
        start_time_penyesuaian: (new Date()).getTimezoneOffset(),
        duration: 120,
        zoom_status: 0,
        zoom_akun: "",
        status: "",
    });

    const auth = usePage<SharedData>().props.auth;
    const [date24, setDate24] = useState<Date | undefined>(new Date());

    useEffect(() => {
        if(date24){
            setData('start_time', date24.toISOString())
        }
    }, [date24]);

    useEffect(() => {
        if(eventDialog.data){
            setData("topic", eventDialog.data.topic);
            setData("password", eventDialog.data.password);
            setData("start_time", eventDialog.data.start_time);
            setData("duration", eventDialog.data.duration);
            setData("zoom_status", eventDialog.data.zoom_status);
            if(eventDialog.data.zoom_akun != null){
                setData("zoom_akun", eventDialog.data.zoom_akun);
            }
            setDate24(new Date(eventDialog.data.start_time))
        }

    }, [eventDialog]);

    const submit: React.FormEventHandler = (e) => {
        e.preventDefault();
        if(eventDialog.status == 'tambah'){
            post(route("zoom-tambah"), {
                onSuccess: () => {
                    close_dialog()
                },
            });
        }else if(eventDialog.status == 'ubah'){
            post(route("zoom-ubah", {'zoom_id': eventDialog.data?.id}), {
                onSuccess: () => {
                    close_dialog()
                },
            });
        }else if(eventDialog.status == 'approval'){
            post(route("zoom-approval", {'zoom_id': eventDialog.data?.id}), {
                onSuccess: () => {
                    close_dialog()
                },
            });
        }


    };

    function close_dialog(){
        setData("topic", "Kegiatan")
        setData("password", "BPS")
        setData("start_time",  new Date().toISOString())
        setData("start_time_penyesuaian",  (new Date()).getTimezoneOffset())
        setData("duration",  120)
        setData("zoom_status",  0)
        setData("zoom_akun",  "")
        setData("status",  "")
        setEventDialog({ status: null, data: null })
        return true
    };

    function delete_data(){
        router.post(route('zoom-hapus', {'zoom_id': eventDialog.data?.id}))
        setEventDialog({ status: null, data: null });
    };



    if (eventDialog.status == null) {
        return <></>;
    }

    if(eventDialog.status == 'hapus'){
        return (
            <AlertDialog open={true} onOpenChange={close_dialog}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Apakah kamu yakin?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Tindakan ini tidak dapat dibatalkan. Ini akan menghapus Zoom <b>{eventDialog.data?.topic}</b> Anda secara permanen dan menghapus data Anda dari server kami.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={delete_data}>Continue</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        )
    }else if(eventDialog.status == 'lihat' || eventDialog.status == 'tambah' || eventDialog.status == 'ubah'  || eventDialog.status == 'approval'){
         return (
            <Dialog open={true} onOpenChange={close_dialog}>
                <DialogContent className="sm:max-w-[425px]">
                    <form className="flex flex-col gap-6" onSubmit={submit}>
                        <DialogHeader className="text-accent-foreground">
                            {eventDialog.status == 'lihat' &&
                                <DialogTitle>Lihat Jadwal Zoom</DialogTitle>
                            }
                            {eventDialog.status == 'tambah' &&
                                <DialogTitle>Tambah Jadwal Zoom</DialogTitle>
                            }
                            {eventDialog.status == 'ubah' &&
                                <DialogTitle>Ubah Jadwal Zoom</DialogTitle>
                            }
                            {eventDialog.status == 'approval' &&
                                <DialogTitle>Approve Jadwal Zoom</DialogTitle>
                            }

                            <DialogDescription>
                                Isi isian diatas. Klik Simpan Jika Selesai.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-4">
                            <div className="grid gap-3">
                                <Label className="text-secondary" htmlFor="topic-1">Topic</Label>
                                <Input
                                    id="topic-1"
                                    name="topic"
                                    required
                                    disabled={eventDialog.status == 'lihat'}
                                    value={data.topic}
                                    onChange={(e) =>
                                        setData("topic", e.target.value)
                                    }
                                    className="text-secondary"
                                />
                                <InputError message={errors.topic} />
                            </div>
                            <div className="grid gap-3">
                                <Label className="text-secondary" htmlFor="password-1">Password</Label>
                                <Input
                                    id="password-1"
                                    name="password"
                                    required
                                    disabled={eventDialog.status == 'lihat'}
                                    value={data.password}
                                    onChange={(e) =>
                                        setData("password", e.target.value)
                                    }
                                    className="text-secondary"
                                />
                                <InputError message={errors.password} />
                            </div>
                            <div className="grid gap-3">
                                <Label className="text-secondary" htmlFor="starttime-1">Mulai</Label>
                                <DateTimePicker
                                    hourCycle={24}
                                    granularity="minute"
                                    value={date24}
                                    onChange={setDate24}
                                    disabled={eventDialog.status == 'lihat'}
                                    className="text-secondary"
                                    modal={true}
                                />
                                <InputError message={errors.start_time} />
                            </div>
                            <div className="grid gap-3">
                                <Label className="text-secondary" htmlFor="duration-1">Durasi</Label>
                                <Input
                                    type="number"
                                    id="duration-1"
                                    name="duration"
                                    required
                                    disabled={eventDialog.status == 'lihat'}
                                    value={data.duration}
                                    onChange={(e) =>
                                        setData(
                                            "duration",
                                            parseInt(e.target.value),
                                        )
                                    }
                                    className="text-secondary"
                                />
                                <InputError message={errors.duration} />
                            </div>
                            { haveZoomRole   && (
                                <>
                                    <div className="grid gap-3">
                                        <Label className="text-secondary" htmlFor="approval-1">Approval</Label>
                                        <Select required
                                            value={""+(data.zoom_status)}
                                            onValueChange={(value) => {
                                                setData("zoom_status", parseInt(value))
                                            }}>
                                            <SelectTrigger
                                                name="zoom_status"
                                                className="text-secondary">
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectGroup>
                                                    <SelectItem value="2">Setuju</SelectItem>
                                                    <SelectItem value="1">Tolak</SelectItem>
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>

                                        <InputError message={errors.zoom_status} />
                                    </div>
                                    { data.zoom_status == 2   && (
                                         <div className="grid gap-3">
                                            <Label className="text-secondary" htmlFor="duration-1">Akun</Label>
                                            <Select
                                                required
                                                disabled={eventDialog.status == 'lihat'}
                                                value={""+(data.zoom_status)}
                                                onValueChange={(value) => {
                                                    setData("zoom_akun", value)
                                                }}>
                                                <SelectTrigger
                                                    name="zoom_status"
                                                    className="text-secondary">
                                                    <SelectValue placeholder="Pilih Akun Zoom" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectGroup>
                                                        <SelectItem value="statsulbar">statsulbar</SelectItem>
                                                        <SelectItem value="sp2020">sp2020</SelectItem>
                                                    </SelectGroup>
                                                </SelectContent>
                                            </Select>

                                            <InputError message={errors.zoom_status} />
                                        </div>
                                    )}
                                </>
                            )}
                        </div>
                        {errors.status && <div className="mb-4 text-center text-sm font-medium text-red-400">{errors.status}</div>}
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
    }


}

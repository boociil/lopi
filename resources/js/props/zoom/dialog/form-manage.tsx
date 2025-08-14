import {router, useForm } from "@inertiajs/react";

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

type ZoomForm = {
    topic: string;
    password: string;
    start_time: string;
    start_time_penyesuaian: number;
    duration: number;
};

export default function DialogFormManage() {
    const { eventDialog, setEventDialog } = useZoomContext();

    const { data, setData, post, processing, errors, reset } = useForm<
        Required<ZoomForm>
    >({
        topic: "Kegiatan",
        password: "BPS",
        start_time: new Date().toISOString(),
        start_time_penyesuaian: (new Date()).getTimezoneOffset(),
        duration: 120,
    });

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
            setDate24(new Date(eventDialog.data.start_time))
        }

    }, [eventDialog]);

    const submit: React.FormEventHandler = (e) => {
        e.preventDefault();
        if(eventDialog.status == 'tambah'){
            post(route("zoom-tambah"), {
                onSuccess: () => {
                    setData("topic", "Kegiatan");
                    setData("password", "BPS");
                    setData("start_time", new Date().toISOString());
                    setData("start_time_penyesuaian", 0);
                    setData("duration", 120);
                    setEventDialog({ status: null, data: null });
                },
            });
        }else if(eventDialog.status == 'ubah'){
            post(route("zoom-ubah", {'zoom_id': eventDialog.data?.id}), {
                onSuccess: () => {
                    setData("topic", "Kegiatan");
                    setData("password", "BPS");
                    setData("start_time", new Date().toISOString());
                    setData("start_time_penyesuaian", 0);
                    setData("duration", 120);
                    setEventDialog({ status: null, data: null });
                },
            });
        }


    };

    function close_dialog(){
        setEventDialog({ status: null, data: null })
        return true
    };

    function delete_data(){
        router.post(route('zoom-hapus', {'zoom_id': eventDialog.data?.id}))
        setEventDialog({ status: null, data: null });
    };

    function approval_data(aproval_stataus: number){
        router.post(route('zoom-approval', {'zoom_id': eventDialog.data?.id}), {'asas': aproval_stataus})
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
    }else if(eventDialog.status == 'tambah' || eventDialog.status == 'ubah'  || eventDialog.status == 'approval'){
         return (
            <Dialog open={true} onOpenChange={close_dialog}>
                <DialogContent className="sm:max-w-[425px]">
                    <form className="flex flex-col gap-6" onSubmit={submit}>
                        <DialogHeader>
                            {eventDialog.status == 'tambah' &&
                                <DialogTitle>Tambah Jadwal Zoom</DialogTitle>
                            }
                            {eventDialog.status == 'ubah' &&
                                <DialogTitle>Ubah Jadwal Zoom</DialogTitle>
                            }
                            {eventDialog.status == 'ubah' &&
                                <DialogTitle>Approve Jadwal Zoom</DialogTitle>
                            }

                            <DialogDescription>
                                Isi isian diatas. Klik Simpan Jika Selesai.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-4">
                            <div className="grid gap-3">
                                <Label htmlFor="topic-1">Topic</Label>
                                <Input
                                    id="topic-1"
                                    name="topic"
                                    defaultValue="Kegiatan"
                                    required
                                    disabled={eventDialog.status == 'approval'}
                                    onChange={(e) =>
                                        setData("topic", e.target.value)
                                    }
                                />
                                <InputError message={errors.topic} />
                            </div>
                            <div className="grid gap-3">
                                <Label htmlFor="password-1">Password</Label>
                                <Input
                                    id="password-1"
                                    name="password"
                                    defaultValue="BPS"
                                    required
                                    disabled={eventDialog.status == 'approval'}
                                    onChange={(e) =>
                                        setData("password", e.target.value)
                                    }
                                />
                                <InputError message={errors.password} />
                            </div>
                            <div className="grid gap-3">
                                <Label htmlFor="starttime-1">Mulai</Label>
                                <DateTimePicker
                                    hourCycle={24}
                                    granularity="minute"
                                    value={date24}
                                    onChange={setDate24}
                                    disabled={eventDialog.status == 'approval'}
                                />
                                <InputError message={errors.start_time} />
                            </div>
                            <div className="grid gap-3">
                                <Label htmlFor="duration-1">Durasi</Label>
                                <Input
                                    type="number"
                                    id="duration-1"
                                    name="duration"
                                    defaultValue={data.duration}
                                    required
                                    disabled={eventDialog.status == 'approval'}
                                    onChange={(e) =>
                                        setData(
                                            "duration",
                                            parseInt(e.target.value),
                                        )
                                    }
                                />
                                <InputError message={errors.duration} />
                            </div>
                        </div>
                        <DialogFooter>
                            { (eventDialog.status == 'tambah' || eventDialog.status == 'ubah') &&
                                <>
                                    <DialogClose asChild>
                                        <Button variant="outline">Batal</Button>
                                    </DialogClose>
                                    <Button type="submit">Simpan</Button>
                                </>
                            }
                             {eventDialog.status == 'approval' &&
                                <>
                                    <DialogClose asChild>
                                        <Button variant="destructive" onClick={(e) => approval_data(0)}>Tolak</Button>
                                    </DialogClose>
                                    <DialogClose asChild>
                                        <Button variant="destructive"  onClick={(e) => approval_data(1)}>Setujui</Button>
                                    </DialogClose>
                                </>
                            }

                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        );
    }


}

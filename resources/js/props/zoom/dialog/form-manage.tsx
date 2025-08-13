import {useForm } from "@inertiajs/react";

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

    };

    function close_dialog(){
        setEventDialog({ status: null, data: null })
        return true
    }

    if (eventDialog.status == null) {
        return <></>;
    }

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
                        <DialogClose asChild>
                            <Button variant="outline">Batal</Button>
                        </DialogClose>
                        <Button type="submit">Simpan</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

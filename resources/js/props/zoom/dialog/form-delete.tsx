import { Head, useForm } from '@inertiajs/react';

import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Label } from '@/components/ui/label';
import { DateTimePicker } from '@/components/ui/datetime-picker';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useZoomContext } from '../context';
import React, { useState } from 'react';
import InputError from '@/components/input-error';

type ZoomForm = {
    topic: string;
    password: string;
    start_time: string;
    start_time_penyesuaian: number,
    duration: number,
};


export default function DialogFormAdd () {
    const { newEventDialogOpen, setNewEventDialogOpen } = useZoomContext()

    const { data, setData, post, processing, errors, reset } = useForm<Required<ZoomForm>>({
        topic: 'Kegiatan',
        password: 'BPS',
        start_time: (new Date()).toISOString(),
        start_time_penyesuaian: 0,
        duration: 120,
    });

    const batas = new Date(new Date().setHours(0,0,0))
    const [date24, setDate24] = useState<Date | undefined>(undefined);
    const [error, setError] = useState(false);

    const calculateSum = (pembanding: Date) => {
        if(batas>pembanding){
            setError( true)
        }
        setData('start_time_penyesuaian', pembanding.getTimezoneOffset())
    };


    const submit: React.FormEventHandler = (e) => {
        e.preventDefault();
        if(error){
            return
        }else{
            post(route('zoom-tambah'), {
                onFinish: () => {
                    setData('topic','Kegiatan');
                    setData('password','BPS');
                    setData('start_time',(new Date()).toISOString());
                    setData('start_time_penyesuaian',0);
                    setData('duration',120);
                    setNewEventDialogOpen(false);
                },
            });
        }
    };

  return (
     <Dialog open={newEventDialogOpen} onOpenChange={setNewEventDialogOpen}>
        <DialogContent className="sm:max-w-[425px]">
            <form className="flex flex-col gap-6"  onSubmit={submit}>
                <DialogHeader>
                    <DialogTitle>Tambah Jadwal Zoom</DialogTitle>
                    <DialogDescription>
                        Isi isian diatas. Klik Simpan Jika Selesai.
                    </DialogDescription>
                </DialogHeader>
                <div className="grid gap-4">
                    <div className="grid gap-3">
                        <Label htmlFor="topic-1">Topic</Label>
                        <Input id="topic-1" name="topic" defaultValue="Kegiatan" required
                            onChange={(e) => setData('topic', e.target.value)}
                            />
                    </div>
                    <div className="grid gap-3">
                        <Label htmlFor="password-1">Password</Label>
                        <Input id="password-1" name="password" defaultValue="BPS" required
                            onChange={(e) => setData('password', e.target.value)}
                            />
                    </div>
                    <div className="grid gap-3">
                        <Label htmlFor="starttime-1">Mulai</Label>
                        <DateTimePicker hourCycle={24} value={date24} onChange={setDate24} />

                         {error ?
                            <InputError message="Tanggal Waktu harus lebih atau sama dengan tanggal ini"></InputError>
                           :<></>
                        }

                    </div>
                    <div className="grid gap-3">
                        <Label htmlFor="duration-1">Durasi</Label>
                        <Input type="number" id="duration-1" name="duration" defaultValue={data.duration} required
                            onChange={(e) => setData('duration', parseInt(e.target.value))}/>
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

import { Head, useForm } from '@inertiajs/react';

import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog"
import { Label } from '@/components/ui/label';
import { Calendar } from "@/components/ui/calendar"

import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover"
import { DateTimePicker24h } from '@/components/ui/date-time-picker-24h';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { zoom } from '../types';
import { useZoomContext } from '../context';
import { useEffect, useState } from 'react';
import InputError from '@/components/input-error';

type ZoomForm = {
    topic: string;
    password: string;
    start_time: string;
    start_time_penyesuaian: number,
    duration: number,
};


export default function DialogFormEdit () {
    const { editEventDialogOpen, setEditEventDialogOpen } = useZoomContext()
    const { seletedZoom, setSeletedZoom } = useZoomContext()

    const { data, setData, post, processing, errors, reset } = useForm<Required<ZoomForm>>({
        topic: 'Kegiatan',
        password: 'BPS',
        start_time: (new Date()).toISOString(),
        start_time_penyesuaian: 0,
        duration: 120,
    });

    const batas = new Date(new Date().setHours(0,0,0))
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
                    setEditEventDialogOpen(false);
                },
            });
        }
    };

  return (
     <Dialog open={editEventDialogOpen} onOpenChange={setEditEventDialogOpen}>
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
                        <Input id="topic-1" name="topic" value={seletedZoom?.topic} required
                            onChange={(e) => setData('topic', e.target.value)}
                            />
                    </div>
                    <div className="grid gap-3">
                        <Label htmlFor="password-1">Password</Label>
                        <Input id="password-1" name="password" value={seletedZoom?.password}  required
                            onChange={(e) => setData('password', e.target.value)}
                            />
                    </div>
                    <div className="grid gap-3">
                        <Label htmlFor="starttime-1">Mulai</Label>
                        <DateTimePicker24h value={null}
                            setDateTime={(e) => {setData('start_time', e); calculateSum(new Date(e));}}
                        />

                         {error ?
                            <InputError message="Tanggal Waktu harus lebih atau sama dengan tanggal ini"></InputError>
                           :<></>
                        }

                    </div>
                    <div className="grid gap-3">
                        <Label htmlFor="duration-1">Durasi</Label>
                        <Input id="duration-1" name="duration" value={seletedZoom?.duration} required/>
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

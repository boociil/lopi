"use client";

import { format, parseISO } from "date-fns";
import { Calendar, Clock, Copy, Link, Text, Type, User, Video } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import {
	Dialog,
	DialogClose,
	DialogContent,
	DialogHeader,
	DialogTitle,
	DialogTrigger,
} from "@/components/ui/dialog";
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
import { ScrollArea } from "@/components/ui/scroll-area";
import { useCalendar } from "@/components/calender_modules/contexts/calendar-context";
import { capitalizeWords, formatTime } from "@/components/calender_modules/helpers";
import { Event_Details_Dialog_IProps } from "@/components/calender_modules/dialogs/props-dialog";
import { progress as progressIndikator } from '@inertiajs/react'
import { CalenderUser, IEvent } from "@/components/calender_modules/interfaces";
import { pairIEventDenganUser, jadwal, JadwalIEvent, produceJadwalIEvent } from "../types";
import { useEffect, useState } from "react";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";

interface Approval_Event {
    event: JadwalIEvent;
    zoom_akun: 'statsulbar' | 'sp2020' | null;
    status: 'disetujui'|'ditolak' | null;
}


export function EventDetailsDialog({ _event, children }: Event_Details_Dialog_IProps) {
    const [event, setEvent] = useState<JadwalIEvent>(_event as JadwalIEvent)
	const startDate = parseISO(event.startDate);
	const endDate = parseISO(event.endDate);
	const { settingCalender, use24HourFormat, updateEvent, removeEvent, AddEditEventDialog, canChange, canApprove, axiosClient, users } = useCalendar();

    const [approval, setApproval] = useState<'statsulbar' | 'sp2020' | undefined>(undefined)

    useEffect(() => {
        if(event){
           if(event.zoom_akun == 'statsulbar'){
                setApproval('statsulbar')
           }else if(event.zoom_akun == 'sp2020'){
                setApproval('sp2020')
           }else{
                setApproval(undefined)
           }
        }else{
            setApproval(undefined)
        }
    }, [event]);

    const deleteEvent = (event_id: string) => {
        progressIndikator.start()
        axiosClient.post(route("pinjam-zoom-hapus", {pinjamZoom: event_id}))
            .then(function (response) {
                removeEvent(event_id);
                progressIndikator.finish()
                toast.success(capitalizeWords(settingCalender.name_event)+" telah dihapus dengan sukses.");
            })
            .catch(function (error) {
                progressIndikator.finish()
                toast.error("Kesalahan saat menghapus "+capitalizeWords(settingCalender.name_event)+".");
            });
    };

    const onUpdateOpenChange = (event: IEvent) => {

    }

    const approvalEvent = (event_id: string, status: 'disetujui'|'ditolak') => {
        if(approval == undefined){
            toast('Pilih akun zoom terlebih dahulu')
            return
        }
        progressIndikator.start()
        axiosClient.post(route("pinjam-zoom-approval", {pinjamZoom: event_id}), {status: status, zoom_akun: approval})
            .then(function (response) {
                 try {
                    const return_data: IEvent[] = produceJadwalIEvent([response.data.data] as jadwal[]);
                    pairIEventDenganUser(return_data as JadwalIEvent[], users  as CalenderUser[])
                    updateEvent(return_data[0])

                    progressIndikator.finish()
                    toast.success('Berhasil mengubah data')
                } catch (error) {
                    console.log(error)
                    progressIndikator.finish()
                    toast.error('Gagal mengubah data')
                }
            })
            .catch(function (error) {
                progressIndikator.finish()
                toast.error("Kesalahan saat menghapus "+capitalizeWords(settingCalender.name_event)+".");
            });
    };

	return (
		<Dialog>
			<DialogTrigger asChild>{children}</DialogTrigger>
			<DialogContent>
				<DialogHeader>
					<DialogTitle className="text-secondary">{event.title}</DialogTitle>
				</DialogHeader>

				<ScrollArea className="max-h-[80vh]">
					<div className="space-y-4 p-4">
                        {event.jadwal_ori &&
                            <div className="flex items-start gap-2">
                                <User className="mt-1 size-4 shrink-0 text-muted-foreground" />
                                <div>
                                    <p className="text-sm font-medium text-secondary">Yang bertanggung jawab</p>
                                    <p className="text-sm text-muted-foreground">
                                        {event.user?.name}
                                    </p>
                                </div>
                            </div>
                        }


                        <div className="flex items-start gap-2">
							<Type className="mt-1 size-4 shrink-0 text-muted-foreground" />
							<div>
								<p className="text-sm font-medium text-secondary">Tipe</p>
								<p className="text-sm text-muted-foreground">
                                    {event._startDate != event._endDate ? 'Berulang' : 'Sekali'}
								</p>
							</div>
						</div>

                        { (event as JadwalIEvent).zoom_akun != null ? <>

                            <div className="flex items-start gap-2">
                                <Video className="mt-1 size-4 shrink-0 text-muted-foreground" />
                                <div>
                                    <p className="text-sm font-medium text-secondary">Zoom Akun</p>
                                    <p className="text-sm text-muted-foreground">
                                        {(event as JadwalIEvent).zoom_akun}
                                    </p>
                                </div>
                            </div>
                        </> : <></>}

                         { (event as JadwalIEvent).join_url != null ? <>

                            <div className="flex items-start gap-2">
                                <Link className="mt-1 size-4 shrink-0 text-muted-foreground" />
                                <div>
                                    <p className="text-sm font-medium text-secondary">Join Zoom
                                        <button onClick={() => {navigator.clipboard.writeText((event as JadwalIEvent).join_url!); toast('Copy Link')}} className="text-sm text-muted-foreground">
                                           <Copy className="ml-3 size-4 shrink-0 text-muted-foreground" />
                                        </button>
                                    </p>
                                    <a target="_blank" href={(event as JadwalIEvent).join_url!} className="text-sm text-muted-foreground">
                                        <span>Buka Join Link</span>
                                    </a>
                                </div>
                            </div>
                        </> : <></>}

                         { (event as JadwalIEvent).start_url != null ? <>

                            <div className="flex items-start gap-2">
                                <Link className="mt-1 size-4 shrink-0 text-muted-foreground" />
                                <div>
                                    <p className="text-sm font-medium text-secondary">Start Zoom
                                        <button onClick={() => {navigator.clipboard.writeText((event as JadwalIEvent).start_url!); toast('Copy Link')}} className="text-sm text-muted-foreground">
                                           <Copy className="ml-3 size-4 shrink-0 text-muted-foreground" />
                                        </button>
                                    </p>
                                    <a target="_blank" href={(event as JadwalIEvent).start_url!} className="text-sm text-muted-foreground">
                                        <span>Buka Start Link</span>
                                    </a>
                                </div>
                            </div>
                        </> : <></>}

                        {event._startDate != event._endDate ?
                        <>
                            <div className="flex items-start gap-2">
                                <Calendar className="mt-1 size-4 shrink-0 text-muted-foreground" />
                                <div>
                                    <p className="text-sm font-medium text-secondary">Tanggal Awal</p>
                                    <p className="text-sm text-muted-foreground">
                                        {format(startDate, "EEEE dd MMMM")}
                                    </p>
                                </div>
                            </div>

                             <div className="flex items-start gap-2">
                                <Calendar className="mt-1 size-4 shrink-0 text-muted-foreground" />
                                <div>
                                    <p className="text-sm font-medium text-secondary">Tanggal Akhir</p>
                                    <p className="text-sm text-muted-foreground">
                                        {format(endDate, "EEEE dd MMMM")}
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start gap-2">
                                <Clock className="mt-1 size-4 shrink-0 text-muted-foreground" />
                                <div>
                                    <p className="text-sm font-medium text-secondary">Waktu Mulai</p>
                                    <p className="text-sm text-muted-foreground">
                                        {formatTime(parseISO(event.startDate), use24HourFormat)}
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-start gap-2">
                                <Clock className="mt-1 size-4 shrink-0 text-muted-foreground" />
                                <div>
                                    <p className="text-sm font-medium text-secondary">Waktu Akhir</p>
                                    <p className="text-sm text-muted-foreground">
                                        {formatTime(parseISO(event.endDate), use24HourFormat)}
                                    </p>
                                </div>
                            </div>
                        </>
                        :
                        <>
                            <div className="flex items-start gap-2">
                                <Calendar className="mt-1 size-4 shrink-0 text-muted-foreground" />
                                <div>
                                    <p className="text-sm font-medium text-secondary">Mulai</p>
                                    <p className="text-sm text-muted-foreground">
                                        {format(startDate, "EEEE dd MMMM")}
                                        <span className="mx-1">at</span>
                                        {formatTime(parseISO(event.startDate), use24HourFormat)}
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start gap-2">
                                <Clock className="mt-1 size-4 shrink-0 text-muted-foreground" />
                                <div>
                                    <p className="text-sm font-medium text-secondary">Berakhir</p>
                                    <p className="text-sm text-muted-foreground">
                                        {format(endDate, "EEEE dd MMMM")}
                                        <span className="mx-1">at</span>
                                        {formatTime(parseISO(event.endDate), use24HourFormat)}
                                    </p>
                                </div>
                            </div>
                        </>
                        }

					</div>
				</ScrollArea>
				<div className="flex justify-end gap-2">
                    {canApprove(event) &&
                        <AlertDialog>
                            <AlertDialogTrigger asChild>
                                <Button variant="outline" className="text-secondary">Approval</Button>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <AlertDialogTitle className="text-secondary">Konfirmasi tindakan</AlertDialogTitle>
                                <AlertDialogDescription>
                                    Apakah Anda ingin menyetujui atau menolak permintaan ini?
                                </AlertDialogDescription>
                                </AlertDialogHeader>
                                <div className="grid gap-3">
                                    <Label
                                        className="text-secondary"
                                        htmlFor="tipe-1"
                                    >
                                        Zoom Akun
                                    </Label>
                                    <Select
                                        name="tipe"
                                        required
                                        value={approval}
                                        onValueChange={(value) => {
                                            console.log(value)
                                            setApproval(value as 'statsulbar' | 'sp2020' | undefined)
                                        }}
                                    >
                                        <SelectTrigger
                                            name="zoom_status"
                                            className="text-secondary"
                                        >
                                            <SelectValue placeholder="Pilih Tipe" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem value="statsulbar">
                                                    statsulbar
                                                </SelectItem>
                                                <SelectItem value="sp2020">
                                                    sp2020
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <AlertDialogFooter>
                                <AlertDialogCancel className="text-secondary">Tutup</AlertDialogCancel>
                                <AlertDialogAction onClick={() => approvalEvent(event.id, 'ditolak')}>Tolak</AlertDialogAction>
                                <AlertDialogAction onClick={() => approvalEvent(event.id, 'disetujui')}>Setujui</AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
                    }
                    {canChange(event) &&
                        <AddEditEventDialog event={event} onUpdateOpenChange={(_event: IEvent) => setEvent(_event as JadwalIEvent)}>
                            <Button variant="outline" className="text-accent-foreground">Ubah</Button>
                        </AddEditEventDialog>
                    }

					<Button
						variant="destructive"
						onClick={() => {
							deleteEvent(event.id);
						}}
					>
						Hapus
					</Button>
				</div>
				<DialogClose />
			</DialogContent>
		</Dialog>
	);
}

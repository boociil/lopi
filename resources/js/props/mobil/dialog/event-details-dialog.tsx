"use client";

import { format, parseISO } from "date-fns";
import { Calendar, Clock, Text, Type, User } from "lucide-react";
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
import { formatTime } from "@/components/calender_modules/helpers";
import { Event_Details_Dialog_IProps } from "@/components/calender_modules/dialogs/props-dialog";
import { progress as progressIndikator } from '@inertiajs/react'
import { CalenderUser, IEvent } from "@/components/calender_modules/interfaces";
import { pairIEventDenganUser, pinjam_mobil, PinjamMobilIEvent, producePinjamMobilIEvent } from "../types";

export function EventDetailsDialog({ event, children }: Event_Details_Dialog_IProps) {
	const startDate = parseISO(event.startDate);
	const endDate = parseISO(event.endDate);
	const { settingCalender, use24HourFormat, updateEvent, removeEvent, AddEditEventDialog, canChange, canApprove, axiosClient, users } = useCalendar();


    const deleteEvent = (event_id: string) => {
        progressIndikator.start()
        axiosClient.post(route("pinjam-mobil-hapus", {pinjamMobil: event_id}))
            .then(function (response) {
                removeEvent(event_id);
                progressIndikator.finish()
                toast.success(settingCalender.name_event+" telah dihapus dengan sukses.");
            })
            .catch(function (error) {
                progressIndikator.finish()
                toast.error("Kesalahan saat menghapus "+settingCalender.name_event+".");
            });
    };

    const approvalEvent = (event_id: string, repeat_id: string|null, status: 'disetujui'|'ditolak') => {
        progressIndikator.start()
        axiosClient.post(route("pinjam-mobil-approval", {pinjamMobil: event_id}), {status: status})
            .then(function (response) {
                 try {
                    const return_data: IEvent[] = producePinjamMobilIEvent(response.data.data as pinjam_mobil[]);
                    pairIEventDenganUser(return_data as PinjamMobilIEvent[], users  as CalenderUser[])

                    if(repeat_id){
                        updateEvent(return_data, [event_id], [repeat_id])
                    }else{
                        updateEvent(return_data, [event_id], [])
                    }
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
                toast.error("Kesalahan saat menghapus "+settingCalender.name_event+".");
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
						<div className="flex items-start gap-2">
							<User className="mt-1 size-4 shrink-0 text-muted-foreground" />
							<div>
								<p className="text-sm font-medium text-secondary">Yang bertanggung jawab</p>
								<p className="text-sm text-muted-foreground">
									{event.user?.name}
								</p>
							</div>
						</div>

                        <div className="flex items-start gap-2">
							<Type className="mt-1 size-4 shrink-0 text-muted-foreground" />
							<div>
								<p className="text-sm font-medium text-secondary">Tipe</p>
								<p className="text-sm text-muted-foreground">
                                    {event.repeat_id ? 'Berulang' : 'Sekali'}
								</p>
							</div>
						</div>
                        {event.repeat_id ?
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
                                        {format(startDate, "EEEE dd MMMM")}
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
                                <AlertDialogFooter>
                                <AlertDialogCancel className="text-secondary">Tutup</AlertDialogCancel>
                                <AlertDialogAction onClick={() => approvalEvent(event.id, event.repeat_id, 'ditolak')}>Tolak</AlertDialogAction>
                                <AlertDialogAction onClick={() => approvalEvent(event.id, event.repeat_id, 'disetujui')}>Setujui</AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
                    }
                    {canChange(event) &&
                        <AddEditEventDialog event={event}>
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

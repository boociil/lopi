"use client";

import {
    CalendarDays,
    History,
    User,
    UserCheck,
} from "lucide-react";
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
} from "@/components/ui/alert-dialog";
import { useCalendar } from "@/components/calender_modules/contexts/calendar-context";
import {
    capitalizeWords,
    formatDate,
    formatTime,
    formatRangeDate,
    formatTanggal,
} from "@/components/calender_modules/helpers";
import { Event_Details_Dialog_IProps } from "@/components/calender_modules/dialogs/props-dialog";
import { progress as progressIndikator } from "@inertiajs/react";
import { CalenderUser, IEvent } from "@/components/calender_modules/interfaces";
import {
    pairIEventDenganUser,
    pinjam_mobil,
    PinjamMobilIEvent,
    producePinjamMobilIEvent,
} from "../types";
import { useState } from "react";
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from "@/components/ui/tooltip";

export function EventDetailsDialog({
    _event,
    children,
}: Event_Details_Dialog_IProps) {
    const [event, setEvent] = useState(_event);
    const {
        settingCalender,
        updateEvent,
        removeEvent,
        AddEditEventDialog,
        canChange,
        canApprove,
        axiosClient,
        users,
        userDict
    } = useCalendar();

    const deleteEvent = (event_id: string) => {
        progressIndikator.start();
        axiosClient
            .post(route("pinjam-mobil-hapus", { pinjamMobil: event_id }))
            .then(function (response) {
                removeEvent(event_id);
                progressIndikator.finish();
                toast.success(
                    capitalizeWords(settingCalender.name_event) +
                        " telah dihapus dengan sukses.",
                );
            })
            .catch(function (error) {
                progressIndikator.finish();
                toast.error(
                    "Kesalahan saat menghapus " +
                        capitalizeWords(settingCalender.name_event) +
                        ".",
                );
            });
    };

    const onUpdateOpenChange = (event: IEvent) => {};

    const approvalEvent = (
        event_id: string,
        status: "disetujui" | "ditolak",
    ) => {
        progressIndikator.start();
        axiosClient
            .post(route("pinjam-mobil-approval", { pinjamMobil: event_id }), {
                status: status,
            })
            .then(function (response) {
                try {
                    const return_data: IEvent[] = producePinjamMobilIEvent([
                        response.data.data,
                    ] as pinjam_mobil[]);
                    pairIEventDenganUser(
                        return_data as PinjamMobilIEvent[],
                        users as CalenderUser[],
                    );
                    updateEvent(return_data[0]);

                    progressIndikator.finish();
                    toast.success("Berhasil mengubah data");
                } catch (error) {
                    console.log(error);
                    progressIndikator.finish();
                    toast.error("Gagal mengubah data");
                }
            })
            .catch(function (error) {
                progressIndikator.finish();
                toast.error(
                    "Kesalahan saat menghapus " +
                        capitalizeWords(settingCalender.name_event) +
                        ".",
                );
            });
    };

      const getStatusStyle = (status : 'menunggu'|'disetujui'|'ditolak') => {
        switch (status) {
        case 'disetujui': return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'ditolak': return 'bg-rose-100 text-rose-700 border-rose-200';
        default: return 'bg-amber-100 text-amber-700 border-amber-200';
        }
    };

    return (
        <Dialog>
            <Tooltip>
                <TooltipTrigger asChild>
                    <DialogTrigger asChild>{children}</DialogTrigger>
                </TooltipTrigger>
                <TooltipContent className="bg-background p-1 m-1 shadow-2xl rounded-2xl">
                    <div className="p-4">
                        <div className="mb-1">
                            <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">
                                Nama Kegiatan
                            </label>
                           <p className="font-bold text-lg text-slate-800 leading-tight">
                                {event.title}
                            </p>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div className="space-y-3">
                                <div>
                                    <div className="flex items-center gap-2 text-slate-400 mb-2">
                                        <User size={16} />
                                        <span className="text-xs font-bold uppercase tracking-wider">
                                            Pemohon
                                        </span>
                                    </div>
                                    <div className="bg-slate-50 p-2 rounded-2xl border border-slate-100 flex items-center gap-1">
                                        <div className="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold uppercase border border-blue-200">
                                            {(event as PinjamMobilIEvent)
                                                .pinjam_mobil_ori
                                                ? (
                                                      event as PinjamMobilIEvent
                                                  ).pinjam_mobil_ori.pembuat_username.charAt(
                                                      0,
                                                  )
                                                : "-"}
                                        </div>
                                        <div>
                                            <p className="text-sm font-bold text-slate-500 ml-1">
                                                {( (event as PinjamMobilIEvent).pinjam_mobil_ori.pembuat_username && userDict.has((event as PinjamMobilIEvent).pinjam_mobil_ori.pembuat_username as string) )
                                                    ? userDict.get((event as PinjamMobilIEvent).pinjam_mobil_ori.pembuat_username as string)?.name
                                                    : (event as PinjamMobilIEvent).pinjam_mobil_ori.pembuat_username
                                                }
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {(event as PinjamMobilIEvent).pinjam_mobil_ori
                                    .status == "disetujui" && (
                                    <div>
                                        <div className="flex items-center gap-2 text-slate-400 mb-2">
                                            <UserCheck size={16} />
                                            <span className="text-xs font-bold uppercase tracking-wider">
                                                Disetujui Oleh
                                            </span>
                                        </div>
                                        <div className="bg-slate-50 p-2 rounded-2xl border border-slate-100 flex items-center gap-1">
                                            <div className="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 font-bold uppercase border border-emerald-200">
                                                {(event as PinjamMobilIEvent)
                                                    .pinjam_mobil_ori
                                                    ? (
                                                          event as PinjamMobilIEvent
                                                      ).pinjam_mobil_ori.confirm_username?.charAt(
                                                          0,
                                                      )
                                                    : "-"}
                                            </div>
                                            <div>
                                                <p className="text-sm font-bold text-emerald-500">
                                                    {( (event as PinjamMobilIEvent).pinjam_mobil_ori.confirm_username && userDict.has((event as PinjamMobilIEvent).pinjam_mobil_ori.confirm_username as string) )
                                                        ? userDict.get((event as PinjamMobilIEvent).pinjam_mobil_ori.confirm_username as string)?.name
                                                        : (event as PinjamMobilIEvent).pinjam_mobil_ori.confirm_username
                                                    }
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>
                            <div className="space-y-3">
                                <div>
                                    <div className="flex items-center gap-2 text-slate-400 mb-2">
                                        <CalendarDays size={16} />
                                        <span className="text-xs font-bold uppercase tracking-wider">
                                            Waktu Pelaksanaan
                                        </span>
                                    </div>
                                    <div className="bg-slate-50 p-2 rounded-2xl border border-slate-100">
                                        <p className="text-sm font-bold text-slate-500">
                                            {event._startDate == event._endDate ? (
                                                formatTanggal(event.startDate)
                                            ) : (
                                                formatRangeDate(event._startDate, event._endDate)
                                            )}
                                        </p>
                                        <p className="text-sm font-bold text-slate-500">
                                            {formatTime(event.startDate, true)+ '–'+formatTime(event.endDate, true)}
                                        </p>
                                    </div>
                                </div>

                            </div>


                        </div>
                    </div>
                </TooltipContent>
            </Tooltip>
            <DialogContent>
                <DialogHeader className="p-1">
                    <DialogTitle className="text-secondary flex items-center p-1">
                        <p className="flex grow">Rincian Peminjaman</p>

                        <div className={`flex px-4 py-1.5 rounded-full text-xs font-bold border justify-end ${getStatusStyle((event as PinjamMobilIEvent).pinjam_mobil_ori.status)} uppercase tracking-wider`}>
                            {(event as PinjamMobilIEvent).pinjam_mobil_ori.status}
                        </div>
                    </DialogTitle>
                </DialogHeader>

                <div className="w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
                    <div className="p-2">
                        <div className="mb-1">
                            <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">
                                Nama Kegiatan
                            </label>
                           <p className="font-bold text-lg text-slate-800 leading-tight">
                                {event.title}
                            </p>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 mt-2">
                            <div className="space-y-3">
                                <div>
                                    <div className="flex items-center gap-2 text-slate-400 mb-2">
                                        <User size={16} />
                                        <span className="text-xs font-bold uppercase tracking-wider">
                                            Pemohon
                                        </span>
                                    </div>
                                    <div className="bg-slate-50 p-2 rounded-2xl border border-slate-100 flex items-center gap-1">
                                        <div className="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold uppercase border border-blue-200">
                                            {(event as PinjamMobilIEvent)
                                                .pinjam_mobil_ori
                                                ? (
                                                      event as PinjamMobilIEvent
                                                  ).pinjam_mobil_ori.pembuat_username.charAt(
                                                      0,
                                                  )
                                                : "-"}
                                        </div>
                                        <div>
                                            <p className="text-sm font-bold text-slate-500 ml-1">
                                                {( (event as PinjamMobilIEvent).pinjam_mobil_ori.pembuat_username && userDict.has((event as PinjamMobilIEvent).pinjam_mobil_ori.pembuat_username as string) )
                                                    ? userDict.get((event as PinjamMobilIEvent).pinjam_mobil_ori.pembuat_username as string)?.name
                                                    : (event as PinjamMobilIEvent).pinjam_mobil_ori.pembuat_username
                                                }
                                            </p>
                                        </div>
                                    </div>
                                </div>

                               {(event as PinjamMobilIEvent).pinjam_mobil_ori
                                    .status == "disetujui" && (
                                    <div>
                                        <div className="flex items-center gap-2 text-slate-400 mb-2">
                                            <UserCheck size={16} />
                                            <span className="text-xs font-bold uppercase tracking-wider">
                                                Disetujui Oleh
                                            </span>
                                        </div>
                                        <div className="bg-slate-50 p-2 rounded-2xl border border-slate-100 flex items-center gap-1">
                                            <div className="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 font-bold uppercase border border-emerald-200">
                                                {(event as PinjamMobilIEvent)
                                                    .pinjam_mobil_ori
                                                    ? (
                                                          event as PinjamMobilIEvent
                                                      ).pinjam_mobil_ori.confirm_username?.charAt(
                                                          0,
                                                      )
                                                    : "-"}
                                            </div>
                                            <div>
                                                <p className="text-sm font-bold text-emerald-500">
                                                    {( (event as PinjamMobilIEvent).pinjam_mobil_ori.confirm_username && userDict.has((event as PinjamMobilIEvent).pinjam_mobil_ori.confirm_username as string) )
                                                        ? userDict.get((event as PinjamMobilIEvent).pinjam_mobil_ori.confirm_username as string)?.name
                                                        : (event as PinjamMobilIEvent).pinjam_mobil_ori.confirm_username
                                                    }
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>
                            <div className="space-y-3">
                                <div>
                                    <div className="flex items-center gap-2 text-slate-400 mb-2">
                                        <CalendarDays size={16} />
                                        <span className="text-xs font-bold uppercase tracking-wider">
                                            Waktu Pelaksanaan
                                        </span>
                                    </div>
                                    <div className="bg-slate-50 p-2 rounded-2xl border border-slate-100">
                                        <p className="text-sm font-bold text-slate-500">
                                            {event._startDate == event._endDate ? (
                                                formatTanggal(event.startDate)
                                            ) : (
                                                formatRangeDate(event._startDate, event._endDate)
                                            )}
                                        </p>
                                        <p className="text-sm font-bold text-slate-500">
                                            {formatTime(event.startDate, true)+ '–'+formatTime(event.endDate, true)}
                                        </p>
                                    </div>
                                </div>

                            </div>


                        </div>
                    </div>
                </div>

                <div className="flex justify-end gap-2">
                    {canApprove(event) && (
                        <AlertDialog>
                            <AlertDialogTrigger asChild>
                                <Button
                                    variant="outline"
                                    className="text-secondary"
                                >
                                    Approval
                                </Button>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <AlertDialogTitle className="text-secondary">
                                        Konfirmasi tindakan
                                    </AlertDialogTitle>
                                    <AlertDialogDescription>
                                        Apakah Anda ingin menyetujui atau
                                        menolak permintaan ini?
                                    </AlertDialogDescription>
                                </AlertDialogHeader>
                                <AlertDialogFooter>
                                    <AlertDialogCancel className="text-secondary">
                                        Tutup
                                    </AlertDialogCancel>
                                    <AlertDialogAction
                                        onClick={() =>
                                            approvalEvent(event.id, "ditolak")
                                        }
                                    >
                                        Tolak
                                    </AlertDialogAction>
                                    <AlertDialogAction
                                        onClick={() =>
                                            approvalEvent(event.id, "disetujui")
                                        }
                                    >
                                        Setujui
                                    </AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
                    )}
                    {canChange(event) && (
                        <AddEditEventDialog
                            event={event}
                            onUpdateOpenChange={(_event: IEvent) =>
                                setEvent(_event)
                            }
                        >
                            <Button
                                variant="outline"
                                className="text-accent-foreground"
                            >
                                Ubah
                            </Button>
                        </AddEditEventDialog>
                    )}
                    {canChange(event) && (
                        <Button
                            variant="destructive"
                            onClick={() => {
                                deleteEvent(event.id);
                            }}
                        >
                            Batalkan
                        </Button>
                    )}
                    <DialogClose asChild>
                        <Button
                            variant="outline"
                            className="px-6 py-2 text-sm font-bold text-slate-500 hover:text-slate-800 transition-colors uppercase tracking-widest"
                        >
                            Tutup
                        </Button>
                    </DialogClose>
                </div>
            </DialogContent>
        </Dialog>
    );
}

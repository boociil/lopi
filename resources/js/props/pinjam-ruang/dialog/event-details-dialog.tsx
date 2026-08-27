"use client";

import { parseISO } from "date-fns";
import {
    Calendar,
    CheckCircle,
    Clock,
    ClockIcon,
    LogIn,
    MapPin,
    PlayCircle,
    Users,
    Video,
    XCircle,
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
import { ScrollArea } from "@/components/ui/scroll-area";
import { useCalendar } from "@/components/calender_modules/contexts/calendar-context";
import {
    capitalizeWords,
    formatRangeDate,
    formatTanggal,
    formatTime,
} from "@/components/calender_modules/helpers";
import { Event_Details_Dialog_IProps } from "@/components/calender_modules/dialogs/props-dialog";
import { progress as progressIndikator } from "@inertiajs/react";
import { IEvent } from "@/components/calender_modules/interfaces";
import {
    pairIEventDenganUser,
    jadwal,
    JadwalIEvent,
    produceJadwalIEvent,
} from "../types";
import { useEffect, useState } from "react";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from "@/components/ui/tooltip";

export function EventDetailsDialog({
    _event,
    children,
}: Event_Details_Dialog_IProps) {
    const [event, setEvent] = useState<JadwalIEvent>(_event as JadwalIEvent);
    const {
        events,
        settingCalender,
        updateEvent,
        removeEvent,
        AddEditEventDialog,
        canChange,
        canApprove,
        canDelete,
        axiosClient,
        users,
        userDict,
        havePeran,
    } = useCalendar();

    const deleteEvent = (event_id: string) => {
        progressIndikator.start();
        axiosClient
            .post(route("jadwal-hapus", { jadwal: event_id }))
            .then(function () {
                removeEvent(event_id);
                progressIndikator.finish();
                toast.success(
                    capitalizeWords(settingCalender.name_event) +
                        " telah dihapus dengan sukses.",
                );
            })
            .catch(function () {
                progressIndikator.finish();
                toast.error(
                    "Kesalahan saat menghapus " +
                        capitalizeWords(settingCalender.name_event) +
                        ".",
                );
            });
    };

    const onUpdateOpenChange = (event: IEvent) => {};

    const approvalRuangEvent = (
        event_id: string,
        ruang_status: "disetujui" | "ditolak",
    ) => {
        progressIndikator.start();
        axiosClient
            .post(route("jadwal-approval-ruang", { jadwal: event_id }), {
                ruang_status: ruang_status,
            })
            .then(function (response: any) {
                try {
                    const return_data: IEvent[] = produceJadwalIEvent([
                        response.data.data,
                    ] as jadwal[]);
                    pairIEventDenganUser(
                        return_data as JadwalIEvent[],
                        users,
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
            .catch(function () {
                progressIndikator.finish();
                toast.error(
                    "Kesalahan saat menghapus " +
                        capitalizeWords(settingCalender.name_event) +
                        ".",
                );
            });
    };


    const StatusBadge = ({
        status,
    }: {
        status: "menunggu" | "disetujui" | "ditolak" | "langsung";
    }) => {
        const config = {
            menunggu: {
                color: "bg-yellow-100 text-yellow-800 border-yellow-200",
                icon: <ClockIcon size={12} />,
            },
            disetujui: {
                color: "bg-green-100 text-green-800 border-green-200",
                icon: <CheckCircle size={12} />,
            },
            ditolak: {
                color: "bg-red-100 text-red-800 border-red-200",
                icon: <XCircle size={12} />,
            },
            langsung: {
                color: "bg-blue-100 text-blue-800 border-blue-200",
                icon: <Video size={12} />,
            },
        };
        const item = config[status] || config.menunggu;
        return (
            <span
                className={`flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black border ${item.color}`}
            >
                {item.icon}
                {status.toUpperCase()}
            </span>
        );
    };

    const [openDialog, setOpenDialog] = useState<boolean>(false)
    const [openToolbox, setOpenToolbox] = useState<boolean>(false)


    return (
        <Dialog open={openDialog} onOpenChange={setOpenDialog}>
             <Tooltip open={openToolbox} onOpenChange={setOpenToolbox}>
                <TooltipTrigger asChild>
                    <DialogTrigger asChild>{children}</DialogTrigger>
                </TooltipTrigger>
                <TooltipContent className="bg-background p-1 m-1 shadow-2xl rounded-2xl">
                    <div className="max-h-[40vh] overflow-auto p-2">
                        <div>
                            <div className="text-secondary flex-1 mb-1">
                                <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">
                                    Nama Kegiatan
                                </label>
                                <h2 className="font-bold text-lg text-slate-800 leading-tight">
                                    {event.title}
                                </h2>
                            </div>
                        </div>
                        <div className="space-y-4 py-0">
                            <div className="grid grid-cols-2 gap-2">
                                <div className="p-3 bg-slate-50 rounded-xl border border-slate-100 relative overflow-hidden">
                                    <div className="flex justify-between items-start mb-2">
                                        <p className="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">
                                            Jadwal
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <Calendar
                                            className="text-blue-600"
                                            size={18}
                                        />
                                        <span className="flex items-start gap-1.5 bg-slate-50  rounded-xl border border-slate-100 w-fit text-sm text-slate-700 font-medium">
                                            {event._startDate ==
                                            event._endDate ? (
                                                formatTanggal(event.startDate)
                                            ) : (
                                                formatRangeDate(event._startDate, event._endDate)
                                            )}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <Clock
                                            className="text-blue-600"
                                            size={18}
                                        />
                                        <span className="flex items-start gap-1.5 bg-slate-50  rounded-xl border border-slate-100 w-fit text-sm text-slate-700 font-medium">
                                            {formatTime(event.startDate, true)+ '–'+formatTime(event.endDate, true)}
                                        </span>
                                    </div>
                                </div>

                                <div className="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <div className="flex justify-between items-start mb-2">
                                        <p className="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">
                                            Lokasi Utama
                                        </p>
                                        {event.jadwal_ori.ruang_status !==
                                            "tidak" && (
                                            <StatusBadge
                                                status={
                                                    event.jadwal_ori
                                                        .ruang_status ||
                                                    "menunggu"
                                                }
                                            />
                                        )}
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <MapPin
                                            className={`${event.jadwal_ori.ruang_status !== "tidak" ? "text-amber-600" : "text-slate-400"}`}
                                            size={18}
                                        />
                                        <span className="flex items-start gap-1.5 bg-slate-50  rounded-xl border border-slate-100 w-fit text-sm text-slate-700 font-medium">
                                            {event.jadwal_ori.ruang_status !==
                                            "tidak"
                                                ? capitalizeWords(
                                                      event.jadwal_ori
                                                          .ruang as string,
                                                  )
                                                : "Hanya Online"}
                                        </span>
                                    </div>
                                    {!(
                                        event.jadwal_ori.ruang_status !==
                                        "tidak"
                                    ) && (
                                        <p className="flex items-start gap-1.5 bg-slate-50  rounded-xl border border-slate-100 w-fit text-xs text-slate-700 font-medium italic">
                                            *Tidak menggunakan fasilitas ruang
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div></div>
                        </div>
                    </div>
                </TooltipContent>
            </Tooltip>
            <DialogContent>
                <DialogHeader>
                    <label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">
                        Nama Kegiatan
                    </label>
                    <p className="font-bold text-lg text-slate-800 leading-tight">
                        {event.title}
                    </p>
                </DialogHeader>

                <ScrollArea className="max-h-[60vh] py-0">
                    <div className="space-y-4 py-0">
                        <div className="grid grid-cols-2 gap-2">
                            <div className="p-3 bg-slate-50 rounded-xl border border-slate-100 relative overflow-hidden">
                                <div className="flex justify-between items-start mb-2">
                                    <p className="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">
                                        Jadwal
                                    </p>
                                </div>
                                <div className="flex items-center gap-1">
                                    <Calendar
                                        className="text-blue-600"
                                        size={18}
                                    />
                                    <span className="flex items-start gap-1.5 bg-slate-50  rounded-xl border border-slate-100 w-fit text-sm text-slate-700 font-medium">
                                        {event._startDate ==
                                        event._endDate ? (
                                            formatTanggal(event.startDate)
                                        ) : (
                                            formatRangeDate(event._startDate, event._endDate)
                                        )}
                                    </span>
                                </div>
                                <div className="flex items-center gap-1">
                                    <Clock
                                        className="text-blue-600"
                                        size={18}
                                    />
                                    <span className="flex items-start gap-1.5 bg-slate-50  rounded-xl border border-slate-100 w-fit text-sm text-slate-700 font-medium">
                                        {formatTime(event.startDate, true)+ '–'+formatTime(event.endDate, true)}
                                    </span>
                                </div>
                            </div>

                        <div className="p-3 bg-slate-50 rounded-xl border border-slate-100">
                            <div className="flex justify-between items-start mb-2">
                                    <p className="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">
                                        Lokasi Utama
                                    </p>
                                    {event.jadwal_ori.ruang_status !==
                                        "tidak" && (
                                        <StatusBadge
                                            status={
                                                event.jadwal_ori
                                                    .ruang_status ||
                                                "menunggu"
                                            }
                                        />
                                    )}
                                </div>
                                <div className="flex items-center gap-1">
                                    <MapPin
                                        className={`${event.jadwal_ori.ruang_status !== "tidak" ? "text-amber-600" : "text-slate-400"}`}
                                        size={18}
                                    />
                                    <span className="flex items-start gap-1.5 bg-slate-50  rounded-xl border border-slate-100 w-fit text-sm text-slate-700 font-medium">
                                        {event.jadwal_ori.ruang_status !==
                                        "tidak"
                                            ? capitalizeWords(
                                                    event.jadwal_ori
                                                        .ruang as string,
                                                )
                                            : "Hanya Online"}
                                    </span>
                                </div>
                                {!(
                                    event.jadwal_ori.ruang_status !==
                                    "tidak"
                                ) && (
                                    <p className="flex items-start gap-1.5 bg-slate-50  rounded-xl border border-slate-100 w-fit text-xs text-slate-700 font-medium italic">
                                        *Tidak menggunakan fasilitas ruang
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                </ScrollArea>
                <div className="flex flex-wrap justify-end gap-2">
                    {/* Approval Ruang */}
                    {canApprove(event) && (
                        <AlertDialog>
                        <AlertDialogTrigger asChild>
                            <Button
                                    variant="outline"
                                    className="text-secondary"
                                >
                                Approval Ruang
                            </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle className="text-secondary">
                                    Konfirmasi Tindakan
                                </AlertDialogTitle>
                                <AlertDialogDescription>
                                    Apakah Anda menyetujui atau menolak permintaan pemakaian ruangan?
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel className="text-secondary">
                                    Tutup
                                </AlertDialogCancel>
                                <AlertDialogAction
                                    onClick={() => approvalRuangEvent(event.id, "ditolak")}
                                >
                                    Tolak
                                </AlertDialogAction>
                                <AlertDialogAction
                                    onClick={() => approvalRuangEvent(event.id, "disetujui")}
                                >
                                    Setujui
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                        </AlertDialog>
                    )}

                    <DialogClose asChild>
                        <Button variant="outline">Tutup</Button>
                    </DialogClose>
                    </div>
                <DialogClose />
            </DialogContent>
        </Dialog>
    );
}

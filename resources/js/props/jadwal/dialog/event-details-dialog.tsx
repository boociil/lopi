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

    const [zoomAkun, setZoomAkun] = useState<
        "statsulbar" | "sp2020" | undefined
    >(undefined);
    useEffect(() => {
        const itemSelect = events.find((item) => item.id == _event.id);
        if (itemSelect) {
            setEvent(itemSelect as JadwalIEvent);
            if ((itemSelect as JadwalIEvent)?.jadwal_ori.zoom_pinjam) {
                setZoomAkun(
                    (itemSelect as JadwalIEvent).jadwal_ori.zoom_pinjam
                        ?.zoom_akun as "statsulbar" | "sp2020",
                );
            }
        }
    }, [_event, events]);

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

    const approvalZoomEvent = (
        event_id: string,
        zoom_status: "disetujui" | "ditolak",
    ) => {
        progressIndikator.start();
        axiosClient
            .post(route("jadwal-approval-zoom", { jadwal: event_id }), {
                zoom_status: zoom_status,
                zoom_akun: zoomAkun,
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

    const [startUrl, setStartUrl] = useState<string|undefined>(undefined)

    const get_startUrl = () => {
        if(event.jadwal_ori.zoom_pinjam){
            if(event.jadwal_ori.zoom_pinjam.zoom_meeting_id && event.jadwal_ori.zoom_pinjam.zoom_akun){
                 axiosClient.post(route("pinjam-zoom-get-meeting"), {zoom_meeting_id: event.jadwal_ori.zoom_pinjam.zoom_meeting_id, zoom_akun: event.jadwal_ori.zoom_pinjam.zoom_akun})
                    .then(function (response: any) {
                        try {
                            const return_data: {
                                    status:  boolean,
                                    data:  {start_url: string}
                                }
                                = response.data  as  {
                                    status:  boolean,
                                    data:  {start_url: string}
                                }
                                setStartUrl(return_data.data.start_url)
                        } catch (error) {
                            console.log(error)
                        }
                    })
                    .catch(function () {
                    });
            }
        }

    };

    const [openDialog, setOpenDialog] = useState<boolean>(false)
    const [openToolbox, setOpenToolbox] = useState<boolean>(false)

    useEffect(() => {
      if(openDialog || openToolbox){
        get_startUrl()
      }else{
        setStartUrl(undefined)
      }
    }, [openDialog, openToolbox]);

    const hasRuang = Boolean(event.jadwal_ori.ruang);
    const lokasiUtama = hasRuang
        ? capitalizeWords(event.jadwal_ori.ruang as string)
        : event.jadwal_ori.zoom_status !== "tidak"
          ? "Hanya Daring"
          : "Hanya Online";

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
                                            {lokasiUtama}
                                        </span>
                                    </div>
                                    {!hasRuang && (
                                        <p className="flex items-start gap-1.5 bg-slate-50  rounded-xl border border-slate-100 w-fit text-xs text-slate-700 font-medium italic">
                                            *Tidak menggunakan fasilitas ruang
                                        </p>
                                    )}
                                </div>
                            </div>

                            {event.jadwal_ori.zoom_status !== "tidak" && (
                                <div className="p-4 bg-blue-50/50 border-2 border-dashed border-blue-200 rounded-[2rem]">
                                    <div className="flex items-center justify-between mb-2">
                                        <div className="flex items-center gap-4">
                                            <div className="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white shadow-lg">
                                                <Video size={16} />
                                            </div>
                                            <div>
                                                <h4 className="font-black text-blue-900 leading-none">
                                                    Akses Zoom Meeting
                                                </h4>
                                            </div>
                                        </div>
                                        <StatusBadge
                                            status={
                                                event.jadwal_ori.zoom_status ||
                                                "langsung"
                                            }
                                        />
                                    </div>
                                    <div className="grid grid-cols-2 gap-3">
                                        <a
                                            href={
                                                startUrl ?? "#"
                                            }
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            aria-disabled={
                                                !startUrl
                                            }
                                            className={`flex items-center justify-center gap-2 py-2 rounded-lg text-xs font-black transition-all shadow-xl shadow-blue-100
                                            ${
                                                startUrl
                                                    ? "bg-blue-600 hover:bg-blue-700 text-white"
                                                    : "bg-gray-400 cursor-not-allowed text-white"
                                            }`}
                                            onClick={(e) => {
                                                if (
                                                    !startUrl
                                                )
                                                    e.preventDefault();
                                            }}
                                        >
                                            <PlayCircle size={18} />
                                            START ZOOM
                                        </a>

                                        <a
                                            href={
                                                (event.jadwal_ori.zoom_pinjam
                                                    ?.zoom_join_url ||
                                                    event.jadwal_ori
                                                        .zoom_langsung_join_url) ??
                                                "#"
                                            }
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            aria-disabled={
                                                !(
                                                    event.jadwal_ori.zoom_pinjam
                                                        ?.zoom_join_url ||
                                                    event.jadwal_ori
                                                        .zoom_langsung_join_url
                                                )
                                            }
                                            onClick={(e) => {
                                                if (
                                                    !(
                                                        event.jadwal_ori
                                                            .zoom_pinjam
                                                            ?.zoom_join_url ||
                                                        event.jadwal_ori
                                                            .zoom_langsung_join_url
                                                    )
                                                )
                                                    e.preventDefault();
                                            }}
                                            className={`flex items-center justify-center gap-2 py-2 rounded-lg text-xs font-black transition-all border-2
                                            ${
                                                event.jadwal_ori.zoom_pinjam
                                                    ?.zoom_join_url ||
                                                event.jadwal_ori
                                                    .zoom_langsung_join_url
                                                    ? "bg-white text-blue-600 border-blue-600 hover:bg-blue-50"
                                                    : "bg-gray-100 text-gray-400 border-gray-300 cursor-not-allowed"
                                            }`}
                                        >
                                            <LogIn size={18} />
                                            JOIN ZOOM
                                        </a>
                                    </div>
                                </div>
                            )}

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
                                        {lokasiUtama}
                                    </span>
                                </div>
                                {!hasRuang && (
                                    <p className="flex items-start gap-1.5 bg-slate-50  rounded-xl border border-slate-100 w-fit text-xs text-slate-700 font-medium italic">
                                        *Tidak menggunakan fasilitas ruang
                                    </p>
                                )}
                            </div>
                        </div>

                        {event.jadwal_ori.zoom_status !== "tidak" && (
                            <div className="p-4 bg-blue-50/50 border-2 border-dashed border-blue-200 rounded-[2rem]">
                                <div className="flex items-center justify-between mb-2">
                                    <div className="flex items-center gap-4">
                                        <div className="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white shadow-lg">
                                            <Video size={16} />
                                        </div>
                                        <div>
                                            <h4 className="font-black text-blue-900 leading-none">
                                                Akses Zoom Meeting
                                            </h4>
                                        </div>
                                    </div>
                                    <StatusBadge
                                        status={
                                            event.jadwal_ori.zoom_status ||
                                            "langsung"
                                        }
                                    />
                                </div>
                                <div className="grid grid-cols-2 gap-3">
                                    <a
                                        href={
                                            startUrl ?? "#"
                                        }
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-disabled={
                                            !startUrl
                                        }
                                        className={`flex items-center justify-center gap-2 py-2 rounded-lg text-xs font-black transition-all shadow-xl shadow-blue-100
                                            ${
                                                startUrl
                                                    ? "bg-blue-600 hover:bg-blue-700 text-white"
                                                    : "bg-gray-400 cursor-not-allowed text-white"
                                            }`}
                                        onClick={(e) => {
                                            if (
                                                !startUrl
                                            )
                                                e.preventDefault();
                                        }}
                                    >
                                        <PlayCircle size={18} />
                                        START ZOOM
                                    </a>

                                    <a
                                        href={
                                            (event.jadwal_ori.zoom_pinjam
                                                ?.zoom_join_url ||
                                                event.jadwal_ori
                                                    .zoom_langsung_join_url) ??
                                            "#"
                                        }
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-disabled={
                                            !(
                                                event.jadwal_ori.zoom_pinjam
                                                    ?.zoom_join_url ||
                                                event.jadwal_ori
                                                    .zoom_langsung_join_url
                                            )
                                        }
                                        onClick={(e) => {
                                            if (
                                                !(
                                                    event.jadwal_ori.zoom_pinjam
                                                        ?.zoom_join_url ||
                                                    event.jadwal_ori
                                                        .zoom_langsung_join_url
                                                )
                                            )
                                                e.preventDefault();
                                        }}
                                        className={`flex items-center justify-center gap-2 py-2 rounded-lg text-xs font-black transition-all border-2
                                            ${
                                                event.jadwal_ori.zoom_pinjam
                                                    ?.zoom_join_url ||
                                                event.jadwal_ori
                                                    .zoom_langsung_join_url
                                                    ? "bg-white text-blue-600 border-blue-600 hover:bg-blue-50"
                                                    : "bg-gray-100 text-gray-400 border-gray-300 cursor-not-allowed"
                                            }`}
                                    >
                                        <LogIn size={18} />
                                        JOIN ZOOM
                                    </a>
                                </div>
                            </div>
                        )}

                        <div>
                            <div className="flex items-center justify-between mb-4">
                                <h4 className="text-sm font-black text-slate-800 flex items-center gap-2">
                                    <Users
                                        size={18}
                                        className="text-slate-400"
                                    />{" "}
                                    DAFTAR PESERTA
                                </h4>
                                <span className="px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-[10px] font-black">
                                    {event.jadwal_ori.peserta?.length || 0}{" "}
                                    ORANG
                                </span>
                            </div>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-32">
                                {event.jadwal_ori.peserta?.map((p, idx) => (
                                    <div
                                        key={idx}
                                        className="flex items-center gap-2 p-2 bg-white border border-slate-100 rounded-2xl hover:border-blue-200 transition-all group"
                                    >
                                        <div className="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-sm ">
                                            {p ? p.id.charAt(0) : "?"}
                                        </div>
                                        <span className="text-sm  text-slate-700">
                                            {userDict.get(p.peserta_type+'-+-'+p.peserta_id)?.name}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </ScrollArea>
                <div className="flex flex-wrap justify-end gap-2">
                    {/* Approval Ruang */}
                    {canApprove(event, "ruang") && (
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

                    {canApprove(event, 'zoom') && !["tidak", "langsung"].includes(event.jadwal_ori?.zoom_status) && (
                        <AlertDialog>
                            <AlertDialogTrigger asChild>
                                <Button
                                    variant="outline"
                                    className="text-secondary"
                                >
                                    Approval Zoom
                                </Button>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <AlertDialogTitle className="text-secondary">
                                        Konfirmasi Tindakan
                                    </AlertDialogTitle>
                                    <AlertDialogDescription>
                                        Pilih akun Zoom, lalu setujui atau tolak permintaan ini.
                                    </AlertDialogDescription>
                                </AlertDialogHeader>
                                <div className="grid gap-3">
                                    <Label
                                        className="text-secondary"
                                        htmlFor="zoom-account-select"
                                        >
                                        Zoom Akun
                                    </Label>
                                    <Select
                                        name="tipe"
                                        value={zoomAkun}
                                        onValueChange={(value) =>
                                            setZoomAkun(value as "statsulbar" | "sp2020")
                                        }
                                    >
                                    <SelectTrigger
                                        id="zoom-account-select"
                                        className="text-secondary">
                                        <SelectValue placeholder="Pilih Akun" />
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
                                    <AlertDialogCancel className="text-secondary">
                                        Tutup
                                    </AlertDialogCancel>
                                    <AlertDialogAction
                                        onClick={() => approvalZoomEvent(event.id, "ditolak")}
                                        >
                                        Tolak
                                    </AlertDialogAction>
                                    <AlertDialogAction
                                        disabled={!zoomAkun}
                                        onClick={() => approvalZoomEvent(event.id, "disetujui")}
                                    >
                                    Setujui
                                    </AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
                        )}

                    {/* Ubah Event */}
                    {canChange(event) && (
                        <AddEditEventDialog
                        event={event}
                        onUpdateOpenChange={(_event: IEvent) =>
                            setEvent(_event as JadwalIEvent)
                        }
                        >
                        <Button
                            variant="outline"
                            className="text-accent-foreground">
                            Ubah
                        </Button>
                        </AddEditEventDialog>
                    )}

                    {canDelete(event) && (
                        <Button
                            variant="destructive"
                            onClick={() => deleteEvent(event.id)}
                            >
                        Hapus
                        </Button>
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

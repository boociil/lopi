"use client";

import { format, parseISO } from "date-fns";
import { AlertCircle, Calendar, Check, CheckCircle, Clock, Copy, ExternalLink, Link, Play, Text, Type, User, Video, X, XCircle } from "lucide-react";
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
import { capitalizeWords, formatDate, formatTime } from "@/components/calender_modules/helpers";
import { Event_Details_Dialog_IProps } from "@/components/calender_modules/dialogs/props-dialog";
import { progress as progressIndikator } from '@inertiajs/react'
import { CalenderUser, IEvent } from "@/components/calender_modules/interfaces";
import { pairIEventDenganUser, pinjam_zoom, PinjamZoomIEvent, producePinjamZoomIEvent } from "../types";
import { useEffect, useState } from "react";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Tooltip, TooltipContent, TooltipTrigger } from "@/components/ui/tooltip";

interface Approval_Event {
    event: PinjamZoomIEvent;
    zoom_akun: 'statsulbar' | 'sp2020' | null;
    status: 'disetujui'|'ditolak' | null;
}


export function EventDetailsDialog({ _event, children }: Event_Details_Dialog_IProps) {
    const [event, setEvent] = useState<PinjamZoomIEvent>(_event as PinjamZoomIEvent)
	const startDate = parseISO(event.startDate);
	const endDate = parseISO(event.endDate);
	const { settingCalender, use24HourFormat, updateEvent, removeEvent, AddEditEventDialog, canChange, canApprove, axiosClient, users } = useCalendar();

    const [approval, setApproval] = useState<'statsulbar' | 'sp2020' | undefined>(undefined)

    console.log(event)

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

    const [copied, setCopied] = useState(false);

    const copyInvitation = async () => {
        if (!event) return;

       const tanggal =
            event._startDate === event._endDate
            ? formatDate(event._startDate)
            : `${formatDate(event._startDate)} s/d ${formatDate(event._endDate)}`;

        const meetingId =
            event.pinjam_zoom_ori && event.pinjam_zoom_ori.zoom_meeting_id
            ? event.pinjam_zoom_ori.zoom_meeting_id
            : '-';

        const passcode =
            event.pinjam_zoom_ori && event.pinjam_zoom_ori.password
            ? event.pinjam_zoom_ori.password
            : '-';

        const text =
        `UNDANGAN ZOOM MEETING\n\nKegiatan: ${event.title}\nTanggal: ${tanggal}\nWaktu: ${formatTime(event.startDate, true)} - ${formatTime(event.endDate, true)} WITA\n\nJoin Zoom Meeting:\n${event.join_url || '-'}\nMeeting ID: ${meetingId}\nPasscode: ${passcode}\n\nTerima kasih.`;

         try {
            // Attempt to copy the text to the clipboard
            await navigator.clipboard.writeText(text);
            setCopied(true)
        } catch (err) {
            // Handle potential errors
            console.error('Failed to copy text: ', err);
        }
    };

    const approvalEvent = (event_id: string, status: 'disetujui'|'ditolak') => {
        if(approval == undefined){
            toast('Pilih akun zoom terlebih dahulu')
            return
        }
        progressIndikator.start()
        axiosClient.post(route("pinjam-zoom-approval", {pinjamZoom: event_id}), {status: status, zoom_akun: approval})
            .then(function (response) {
                 try {
                    const return_data: IEvent[] = producePinjamZoomIEvent([response.data.data] as pinjam_zoom[]);
                    pairIEventDenganUser(return_data as PinjamZoomIEvent[], users  as CalenderUser[])
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
             <Tooltip>
                <TooltipTrigger asChild>
                    <DialogTrigger asChild>{children}</DialogTrigger>
                </TooltipTrigger>
                <TooltipContent className="bg-background p-1 m-1 shadow-2xl rounded-2xl">
                     <div className="m-3">
                                <div>
                                    <div className="text-secondary flex-1">
                                        <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">
                                            Nama Kegiatan
                                        </label>
                                        <h2 className="font-bold text-lg text-slate-800 leading-tight">
                                            {event.title}
                                        </h2>
                                    </div>
                                </div>

                                <div className="max-h-[80vh]">

                                        <div className="p-0 space-y-6">
                                            <div className="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">
                                                        Jadwal Tanggal
                                                    </label>
                                                    <div className="flex items-center gap-2 text-sm text-slate-700 font-medium">
                                                        <div className="bg-blue-100 p-1.5 rounded-lg text-blue-600">
                                                            <Calendar size={16} />
                                                        </div>
                                                        {event._startDate == event._endDate ? (
                                                            event._startDate
                                                        ) : (
                                                            <>
                                                                <>
                                                                    {formatDate(event._startDate)}{" "}
                                                                    s/d{" "}
                                                                </>
                                                                <>{formatDate(event._endDate)}</>
                                                            </>
                                                        )}
                                                    </div>
                                                </div>
                                                <div>
                                                    <label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">
                                                        Status Persetujuan
                                                    </label>
                                                    <StatusBadge
                                                        status={
                                                            event.pinjam_zoom_ori
                                                                ? event.pinjam_zoom_ori.status
                                                                : "disetujui"
                                                        }
                                                    />
                                                </div>
                                            </div>
                                            <div className="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">
                                                        Waktu Pelaksanaan
                                                    </label>
                                                    <div className="flex items-center gap-2 text-sm text-slate-700 font-bold bg-slate-50 p-2.5 rounded-xl border border-slate-100 w-fit">
                                                        <Clock
                                                            size={16}
                                                            className="text-blue-500"
                                                        />
                                                        {formatTime(event.startDate, true)} -{" "}
                                                        {formatTime(event.endDate, true)}
                                                    </div>
                                                </div>
                                                <div>
                                                    <label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">
                                                        Zoom Akun
                                                    </label>
                                                    <div className="flex items-center gap-2 text-sm text-slate-700 font-bold bg-slate-50 p-2.5 rounded-xl border border-slate-100 w-fit">
                                                        {event.zoom_akun || "-"}
                                                    </div>
                                                </div>
                                            </div>
                                            {event.join_url && (
                                                <>
                                                    <div className="space-y-2">
                                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4 pt-3 border-t border-blue-200">
                                                            <a
                                                                href={
                                                                    event.pinjam_zoom_ori
                                                                        ? event.pinjam_zoom_ori
                                                                              .zoom_start_url
                                                                            ? event.pinjam_zoom_ori
                                                                                  .zoom_start_url
                                                                            : "#"
                                                                        : "#"
                                                                }
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                className={`flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-bold transition-all shadow-md active:scale-95 ${
                                                                    event.pinjam_zoom_ori &&
                                                                    event.pinjam_zoom_ori
                                                                        .zoom_start_url
                                                                        ? "bg-green-600 text-white hover:bg-green-700 hover:shadow-green-200"
                                                                        : "bg-slate-200 text-slate-400 cursor-not-allowed"
                                                                }`}
                                                            >
                                                                <Play
                                                                    size={18}
                                                                    fill="currentColor"
                                                                />{" "}
                                                                Mulai Rapat
                                                            </a>

                                                            <a
                                                                href={
                                                                    event.pinjam_zoom_ori &&
                                                                    event.pinjam_zoom_ori
                                                                        .zoom_join_url
                                                                        ? event.pinjam_zoom_ori
                                                                              .zoom_join_url
                                                                        : "#"
                                                                }
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                className={`flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-bold transition-all border-2 shadow-sm active:scale-95 ${
                                                                    event.pinjam_zoom_ori
                                                                        ? "bg-white border-blue-600 text-blue-600 hover:bg-blue-50"
                                                                        : "bg-slate-50 border-slate-200 text-slate-300 cursor-not-allowed"
                                                                }`}
                                                            >
                                                                <ExternalLink size={18} /> Gabung
                                                                Peserta
                                                            </a>
                                                        </div>
                                                    </div>
                                                </>
                                            )}
                                        </div>

                                </div>
                            </div>
                </TooltipContent>
            </Tooltip>

			<DialogContent>
				<DialogHeader>
					<DialogTitle className="text-secondary flex-1">
                        <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Nama Kegiatan</label>
                        <h2 className="font-bold text-lg text-slate-800 leading-tight">{event.title}</h2>
                    </DialogTitle>
				</DialogHeader>

				<div className="max-h-[80vh]">

              <ScrollArea className="h-86">
                <div className="p-0 space-y-6">
                    <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">Jadwal Tanggal</label>
                        <div className="flex items-center gap-2 text-sm text-slate-700 font-medium">
                        <div className="bg-blue-100 p-1.5 rounded-lg text-blue-600">
                            <Calendar size={16} />
                        </div>
                        { (event._startDate == event._endDate)
                            ? event._startDate
                            : <><>{formatDate(event._startDate)} s/d </>
                                <>{formatDate(event._endDate)}</></>
                        }
                        </div>
                    </div>
                    <div>
                        <label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">Status Persetujuan</label>
                        <StatusBadge status={event.pinjam_zoom_ori? event.pinjam_zoom_ori.status : 'disetujui'} />
                    </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">Waktu Pelaksanaan</label>
                        <div className="flex items-center gap-2 text-sm text-slate-700 font-bold bg-slate-50 p-2.5 rounded-xl border border-slate-100 w-fit">
                            <Clock size={16} className="text-blue-500" />
                            {formatTime(event.startDate, true)} - {formatTime(event.endDate, true)}
                        </div>
                    </div>
                        <div>
                            <label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">Zoom Akun</label>
                            <div className="flex items-center gap-2 text-sm text-slate-700 font-bold bg-slate-50 p-2.5 rounded-xl border border-slate-100 w-fit">
                                {event.zoom_akun || '-'}
                            </div>
                        </div>
                    </div>
                    {event.join_url &&
                    <>
                        <div className="bg-blue-50 rounded-2xl p-4 border border-blue-100 space-y-4 shadow-sm">

                            <div className="flex items-center justify-between">
                                <label className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Teks Undangan Rapat</label>
                                <button
                                onClick={copyInvitation}
                                className={`flex items-center gap-1.5 px-3 py-1 rounded-md text-[10px] font-bold transition-all ${
                                    copied ? "bg-green-100 text-green-700" : "bg-blue-600 text-white hover:bg-blue-700 shadow-sm"
                                }`}
                                >
                                {copied ? <Check size={12} /> : <Copy size={12} />}
                                {copied ? "TERSALIN" : "SALIN UNDANGAN"}
                                </button>
                            </div>
                            <div className="bg-slate-50 rounded-xl p-4 border border-slate-200 relative group">
                                <pre className="text-[11px] text-slate-600 font-sans leading-relaxed whitespace-pre-wrap">
                                {`UNDANGAN ZOOM MEETING\n\nKegiatan: ${event.title}\nTanggal: ${ event._startDate == event._endDate
                ? formatDate(event._startDate)
                : `${formatDate(event._startDate)} s/d ${formatDate(event._endDate)}`}\nWaktu: ${formatTime(event.startDate, true)} - ${formatTime(event.endDate, true)} WITA\n\nJoin Zoom Meeting:\n${event.join_url || '-'}\nMeeting ID: ${event.pinjam_zoom_ori && event.pinjam_zoom_ori.zoom_meeting_id ? event.pinjam_zoom_ori.zoom_meeting_id : '-'}\nPasscode:${event.pinjam_zoom_ori && event.pinjam_zoom_ori.password ? event.pinjam_zoom_ori.password : '-'}\n\nTerima kasih.`}
                                </pre>
                            </div>
                        </div>
                        <div className="space-y-2">

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4 pt-3 border-t border-blue-200">
                        <a
                        href={event.pinjam_zoom_ori? event.pinjam_zoom_ori.zoom_start_url? event.pinjam_zoom_ori.zoom_start_url : '#' : '#'}
                        target="_blank"
                        rel="noopener noreferrer"
                        className={`flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-bold transition-all shadow-md active:scale-95 ${
                            (event.pinjam_zoom_ori && event.pinjam_zoom_ori.zoom_start_url)
                            ? "bg-green-600 text-white hover:bg-green-700 hover:shadow-green-200"
                            : "bg-slate-200 text-slate-400 cursor-not-allowed"
                        }`}
                        >
                        <Play size={18} fill="currentColor" /> Mulai Rapat
                        </a>

                        <a
                        href={event.pinjam_zoom_ori && event.pinjam_zoom_ori.zoom_join_url ? event.pinjam_zoom_ori.zoom_join_url : '#'}
                        target="_blank"
                        rel="noopener noreferrer"
                        className={`flex items-center justify-center gap-2 px-4 py-3 rounded-xl text-sm font-bold transition-all border-2 shadow-sm active:scale-95 ${
                            event.pinjam_zoom_ori
                            ? "bg-white border-blue-600 text-blue-600 hover:bg-blue-50"
                            : "bg-slate-50 border-slate-200 text-slate-300 cursor-not-allowed"
                        }`}
                        >
                        <ExternalLink size={18} /> Gabung Peserta
                        </a>
                    </div>
                    </div>

                    </>
                    }
                </div>
              </ScrollArea>

              <div className="p-0 border-t border-slate-100 flex justify-end mt-2 pt-2 gap-x-1">
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
                        <AddEditEventDialog event={event} onUpdateOpenChange={(_event: IEvent) => setEvent(_event as PinjamZoomIEvent)}>
                            <Button variant="outline" className="text-accent-foreground">Ubah</Button>
                        </AddEditEventDialog>
                    }
                    {canChange(event) &&
                        <Button
                            variant="destructive"
                            onClick={() => {
                                deleteEvent(event.id);
                            }}
                        >
                            Hapus
                        </Button>
                    }
                 <DialogClose asChild>
                    <Button variant="outline" className="px-6 py-2 text-sm font-bold text-slate-500 hover:text-slate-800 transition-colors uppercase tracking-widest">Tutup</Button>
                </DialogClose>
              </div>
            </div>



				<DialogClose />
			</DialogContent>
		</Dialog>
	);
}

 const StatusBadge = ({ status }: {status: 'menunggu'| 'disetujui' | 'ditolak'| 'diapp'}) => {
    const styles = {
      menunggu: 'bg-yellow-100 text-yellow-800 border-yellow-200',
      disetujui: 'bg-green-100 text-green-800 border-green-200',
      ditolak: 'bg-red-100 text-red-800 border-red-200',
      diapp: 'bg-green-100 text-green-800 border-green-200',
    };
    const icons = {
      menunggu: <AlertCircle size={14} className="mr-1" />,
      disetujui: <CheckCircle size={14} className="mr-1" />,
      ditolak: <XCircle size={14} className="mr-1" />,
      diapp: <CheckCircle size={14} className="mr-1" />,
    };
    return (
      <span className={`flex items-center w-fit px-2.5 py-0.5 rounded-full text-xs font-medium border ${styles[status]}`}>
        {icons[status]}
        {status.toUpperCase()}
      </span>
    );
  };

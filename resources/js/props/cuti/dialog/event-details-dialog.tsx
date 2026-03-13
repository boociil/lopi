"use client";

import {
    CalendarDays,
    User,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";

import { useCalendar } from "@/components/calender_modules/contexts/calendar-context";
import {
    formatTime,
    formatRangeDate,
    formatTanggal,
} from "@/components/calender_modules/helpers";
import { Event_Details_Dialog_IProps } from "@/components/calender_modules/dialogs/props-dialog";
import { IEvent } from "@/components/calender_modules/interfaces";
import {
    CutiIEvent,
} from "../types";
import { useEffect, useState } from "react";
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from "@/components/ui/tooltip";

export function EventDetailsDialog({
    _event,
    children,
}: Event_Details_Dialog_IProps) {
    const [event, setEvent] = useState<CutiIEvent>(_event as CutiIEvent);
    const {
        userDict,
        events,
    } = useCalendar();

    useEffect(() => {
        const itemSelect = events.find((item) => item.id == _event.id);
        if (itemSelect) {
            setEvent(itemSelect as CutiIEvent);
        }
    }, [_event, events]);

    return (
        <Dialog>
            <Tooltip>
                <TooltipTrigger asChild>
                    <DialogTrigger asChild>{children}</DialogTrigger>
                </TooltipTrigger>
                <TooltipContent className="bg-background p-1 m-1 shadow-2xl rounded-2xl">
                    <div className="p-2">
                        <div className="mb-1">
                           <p className="font-bold text-lg text-slate-800 leading-tight">
                                {event.title}
                            </p>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 mt-4">
                            <div className="space-y-3">
                                <div>
                                    <div className="flex items-center gap-2 text-slate-400 mb-2">
                                        <User size={16} />
                                        <span className="text-xs font-bold uppercase tracking-wider">
                                            Jenis Cuti
                                        </span>
                                    </div>
                                    <div className="bg-slate-50 p-2 rounded-lg border border-slate-100 flex items-center gap-1">
                                        <p className="text-xs font-semibold text-slate-500">
                                            { (event as CutiIEvent).description }
                                        </p>
                                    </div>
                                </div>

                            </div>
                            <div className="space-y-3">
                                <div>
                                    <div className="flex items-center gap-2 text-slate-400 mb-2">
                                        <CalendarDays size={16} />
                                        <span className="text-xs font-bold uppercase tracking-wider">
                                            Waktu Pelaksanaan
                                        </span>
                                    </div>
                                    <div className="bg-slate-50 p-2 rounded-lg  border border-slate-100">
                                        <p className="text-xs font-semibold text-slate-500">
                                            {event._startDate == event._endDate ? (
                                                formatTanggal(event.startDate)
                                            ) : (
                                                formatRangeDate(event._startDate, event._endDate)
                                            )}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </TooltipContent>
            </Tooltip>
            <DialogContent>
                <DialogHeader className="p-0">
                    <DialogTitle className="text-secondary flex items-center p-0">
                        <p className="flex grow">Rincian Cuti</p>

                    </DialogTitle>
                </DialogHeader>

                <div className="w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
                    <div className="p-2">
                        <div className="mb-1">
                           <p className="font-bold text-lg text-slate-800 leading-tight">
                                {event.title}
                            </p>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 mt-4">
                            <div className="space-y-3">
                                <div>
                                    <div className="flex items-center gap-2 text-slate-400 mb-2">
                                        <User size={16} />
                                        <span className="text-xs font-bold uppercase tracking-wider">
                                            Jenis Cuti
                                        </span>
                                    </div>
                                    <div className="bg-slate-50 p-2 rounded-lg border border-slate-100 flex items-center gap-1">
                                        <p className="text-xs font-semibold text-slate-500">
                                            { (event as CutiIEvent).description }
                                        </p>
                                    </div>
                                </div>

                            </div>
                            <div className="space-y-3">
                                <div>
                                    <div className="flex items-center gap-2 text-slate-400 mb-2">
                                        <CalendarDays size={16} />
                                        <span className="text-xs font-bold uppercase tracking-wider">
                                            Waktu Pelaksanaan
                                        </span>
                                    </div>
                                    <div className="bg-slate-50 p-2 rounded-lg  border border-slate-100">
                                        <p className="text-xs font-semibold text-slate-500">
                                            {event._startDate == event._endDate ? (
                                                formatTanggal(event.startDate)
                                            ) : (
                                                formatRangeDate(event._startDate, event._endDate)
                                            )}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="flex justify-end gap-2">
                    <DialogClose asChild>
                        <Button
                            variant="secondary"
                        >
                            Tutup
                        </Button>
                    </DialogClose>
                </div>
            </DialogContent>
        </Dialog>
    );
}

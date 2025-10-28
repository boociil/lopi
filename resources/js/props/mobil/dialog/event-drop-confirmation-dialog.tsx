"use client";

import {format} from "date-fns";

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import type {IEvent} from "@/components/calender_modules/interfaces";
import {formatTime, getColorClass} from "@/components/calender_modules/helpers";
import {cn} from "@/lib/utils";
import {useCalendar} from "@/components/calender_modules/contexts/calendar-context";
import { Event_Drop_Confirmation_Dialog_IProps } from "@/components/calender_modules/dialogs/props-dialog";


export function EventDropConfirmationDialog({
                                                open,
                                                onOpenChange,
                                                event,
                                                newStartDate,
                                                newEndDate,
                                                onConfirm,
                                                onCancel,
                                            }: Event_Drop_Confirmation_Dialog_IProps) {

    const {use24HourFormat, settingCalender} = useCalendar();

    if (!event || !newStartDate || !newEndDate) {
        return null;
    }

    const originalStart = new Date(event.startDate);

    const formatDate = (date: Date) => {
        return format(date, "MMM dd, yyyy 'at '") + formatTime(date, use24HourFormat);
    };

    const handleConfirm = () => {
        onConfirm();
        onOpenChange(false);
    };

    const handleCancel = () => {
        onCancel();
        onOpenChange(false);
    };

    return (
        <AlertDialog open={open} onOpenChange={onOpenChange}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Konfirmasi Pemindahan {settingCalender.name_event}</AlertDialogTitle>
                    <AlertDialogDescription>
                        Apakah Anda yakin ingin memindahkan
                        <span className={cn(getColorClass(event.color), "mx-1 py-0.5 px-1 rounded-md")}>
							{event.title}
						</span>
                        dari
                        <strong className="mx-1">{formatDate(originalStart)}</strong> ke
                        <strong className="mx-1">{formatDate(newStartDate)}</strong>?
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel onClick={handleCancel}>Cancel</AlertDialogCancel>
                    <AlertDialogAction onClick={handleConfirm}>
                        Pindahkan
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}

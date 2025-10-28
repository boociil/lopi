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
import { ScrollArea } from "@/components/ui/scroll-area";
import { useCalendar } from "@/components/calender_modules/contexts/calendar-context";
import { formatTime } from "@/components/calender_modules/helpers";
import { Event_Details_Dialog_IProps } from "@/components/calender_modules/dialogs/props-dialog";

export function EventDetailsDialog({ event, children }: Event_Details_Dialog_IProps) {
	const startDate = parseISO(event.startDate);
	const endDate = parseISO(event.endDate);
	const { settingCalender, use24HourFormat, removeEvent, AddEditEventDialog, canChange, canApprove, axiosClient } = useCalendar();


    const deleteEvent = (event_id: string) => {
       axiosClient.post(route("pinjam-mobil-hapus", {pinjamMobil: event_id}))
            .then(function (response) {
                removeEvent(event_id);
                toast.success(settingCalender.name_event+" telah dihapus dengan sukses.");
            })
            .catch(function (error) {
                toast.error("Kesalahan saat menghapus "+settingCalender.name_event+".");
            });
    };

	return (
		<Dialog>
			<DialogTrigger asChild>{children}</DialogTrigger>
			<DialogContent>
				<DialogHeader>
					<DialogTitle>{event.title}</DialogTitle>
				</DialogHeader>

				<ScrollArea className="max-h-[80vh]">
					<div className="space-y-4 p-4">
						<div className="flex items-start gap-2">
							<User className="mt-1 size-4 shrink-0 text-muted-foreground" />
							<div>
								<p className="text-sm font-medium">Yang bertanggung jawab</p>
								<p className="text-sm text-muted-foreground">
									{event.user?.name}
								</p>
							</div>
						</div>

                        <div className="flex items-start gap-2">
							<Type className="mt-1 size-4 shrink-0 text-muted-foreground" />
							<div>
								<p className="text-sm font-medium">Tipe</p>
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
                                    <p className="text-sm font-medium">Tanggal Awal</p>
                                    <p className="text-sm text-muted-foreground">
                                        {format(startDate, "EEEE dd MMMM")}
                                    </p>
                                </div>
                            </div>

                             <div className="flex items-start gap-2">
                                <Calendar className="mt-1 size-4 shrink-0 text-muted-foreground" />
                                <div>
                                    <p className="text-sm font-medium">Tanggal Akhir</p>
                                    <p className="text-sm text-muted-foreground">
                                        {format(startDate, "EEEE dd MMMM")}
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start gap-2">
                                <Clock className="mt-1 size-4 shrink-0 text-muted-foreground" />
                                <div>
                                    <p className="text-sm font-medium">Waktu Mulai</p>
                                    <p className="text-sm text-muted-foreground">
                                        {formatTime(parseISO(event.startDate), use24HourFormat)}
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-start gap-2">
                                <Clock className="mt-1 size-4 shrink-0 text-muted-foreground" />
                                <div>
                                    <p className="text-sm font-medium">Waktu Akhir</p>
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
                                    <p className="text-sm font-medium">Mulai</p>
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
                                    <p className="text-sm font-medium">Berakhir</p>
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
                    {canChange(event) &&
                        <AddEditEventDialog event={event}>
                            <Button variant="outline">Edit</Button>
                        </AddEditEventDialog>
                    }
                    {/* {canApprove(event) && } */}

					<Button
						variant="destructive"
						onClick={() => {
							deleteEvent(event.id);
						}}
					>
						Delete
					</Button>
				</div>
				<DialogClose />
			</DialogContent>
		</Dialog>
	);
}

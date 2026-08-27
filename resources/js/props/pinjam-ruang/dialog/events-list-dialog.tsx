import {format} from "date-fns";
import { id } from 'date-fns/locale';
import type {ReactNode} from "react";
import {
    Modal,
    ModalContent,
    ModalHeader,
    ModalTitle,
    ModalTrigger,
} from "@/components/ui/responsive-modal";
import {cn} from "@/lib/utils";
import {useCalendar} from "@/components/calender_modules/contexts/calendar-context";
import {capitalizeWords, formatTime} from "@/components/calender_modules/helpers";
import type {IEvent} from "@/components/calender_modules/interfaces";
import {dayCellVariants} from "@/components/calender_modules/views/month-view/day-cell";
import {EventBullet} from "@/components/calender_modules/views/month-view/event-bullet";
import { Event_List_Dialog_IProps } from "@/components/calender_modules/dialogs/props-dialog";



export function EventListDialog({
                                    date,
                                    events,
                                    maxVisibleEvents = 3,
                                    children,
                                }: Event_List_Dialog_IProps) {
    const cellEvents = events;
    const hiddenEventsCount = Math.max(cellEvents.length - maxVisibleEvents, 0);
    const {badgeVariant, use24HourFormat, EventDetailsDialog, settingCalender} = useCalendar();

    const defaultTrigger = (
        <span className="cursor-pointer">
			<span className="sm:hidden">+{hiddenEventsCount}</span>
			<span className="hidden sm:inline py-0.5 px-2 my-1 rounded-xl border">
				{hiddenEventsCount}
                <span className="mx-1">more...</span>
			</span>
		</span>
    );

    return (
        <Modal>
            <ModalTrigger asChild>{children || defaultTrigger}</ModalTrigger>
            <ModalContent className="sm:max-w-[425px]">
                <ModalHeader>
                    <ModalTitle className="my-2">
                        <div className="flex items-center gap-2">
                            <EventBullet color={cellEvents[0]?.color} className=""/>
                            <p className="text-sm font-medium">
                                {capitalizeWords(settingCalender.name_event)} pada {format(date, "EEEE, MMMM d, yyyy", {locale: id})}
                            </p>
                        </div>
                    </ModalTitle>
                </ModalHeader>
                <div className="max-h-[60vh] overflow-y-auto space-y-2">
                    {cellEvents.length > 0 ? (
                        cellEvents.map((event) => (
                            <EventDetailsDialog _event={event} key={event.id}>
                                <div
                                    className={cn(
                                        "flex items-center gap-2 p-2 border rounded-md hover:bg-muted cursor-pointer",
                                        {
                                            [dayCellVariants({color: event.color})]:
                                                badgeVariant === "colored",
                                        },
                                    )}
                                >
                                        <EventBullet color={event.color}/>
                                        <div className="flex justify-between items-center w-full">
                                            <p className="text-sm font-medium">{event.title}</p>
                                            {settingCalender.showTime &&
                                                <p className="text-xs">
                                                    {formatTime(event.startDate, use24HourFormat)} - {formatTime(event.endDate, use24HourFormat)}
                                                </p>
                                            }

                                        </div>
                                </div>
                            </EventDetailsDialog>
                        ))
                    ) : (
                        <p className="text-sm text-muted-foreground">
                           Tidak ada {capitalizeWords(settingCalender.name_event)} untuk tanggal ini..
                        </p>
                    )}
                </div>
            </ModalContent>
        </Modal>
    );
}

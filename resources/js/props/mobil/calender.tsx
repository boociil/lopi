import { CalendarBody } from "@/components/calender_modules/calendar-body";
import { CalendarProvider } from "@/components/calender_modules/contexts/calendar-context";
import { DndProvider } from "@/components/calender_modules/contexts/dnd-context";
import { CalendarHeader } from "@/components/calender_modules/header/calendar-header";
import { pinjam_mobil, producePinjamMobilIEvent } from "./types";
import { User } from "@/types";
import { produceCalenderUser, SettingCalender, type IEvent, type IUser } from "@/components/calender_modules/interfaces";
import { AddEditEventDialog } from "./dialog/add-edit-event-dialog";
import DeleteEventDialog from "./dialog/delete-event-dialog";
import { DndConfirmationDialog } from "./dialog/dnd-confirmation-dialog";
import { EventDetailsDialog } from "./dialog/event-details-dialog";
import { EventDropConfirmationDialog } from "./dialog/event-drop-confirmation-dialog";
import { EventListDialog } from "./dialog/events-list-dialog";
import { TCalendarView } from "@/components/calender_modules/types";
import { router } from "@inertiajs/react";

export function PinjamMobilCalender({
        pinjam_mobil_data,
        user,
    }: {
        pinjam_mobil_data: pinjam_mobil[]
        user: User[]
}) {

    const settingCalender: SettingCalender = {name_event: 'kegiatan', calender_show: ["week", "month","agenda"], show_filter: false, show_filter_user: false}
    const _user: IUser[] = produceCalenderUser(user);
    const _allEvents: IEvent[] = producePinjamMobilIEvent(pinjam_mobil_data);


    /////////////////////
    function reload_data(selectedDate: Date, currentView: TCalendarView, setAllEventsOri: (_allEvents: IEvent[]) => void){
        router.post(route("pinjam-mobil-index"), {selectedDate: selectedDate, view: currentView}
            , { async: true, showProgress: true,
                onSuccess: (page) => {
                    try {
                        setAllEventsOri(producePinjamMobilIEvent(page.props.data))
                    } catch (error) {
                        console.log(error)
                    }

                }
            }
        )
    }



    return (
        <CalendarProvider settingCalender={settingCalender}
                _user={_user}
                _allEvents={_allEvents}

                AddEditEventDialog={AddEditEventDialog}
                DeleteEventDialog={DeleteEventDialog}
                DndConfirmationDialog={DndConfirmationDialog}
                EventDetailsDialog={EventDetailsDialog}
                EventDropConfirmationDialog={EventDropConfirmationDialog}
                EventListDialog={EventListDialog}
                reload_data={reload_data}
            >
            <DndProvider showConfirmation={false}>
                <div className="w-full rounded-xl border-accent-foreground mx-2">
                    <CalendarHeader />
                    <CalendarBody />
                </div>
            </DndProvider>
        </CalendarProvider>
    );
}

import { CalendarBody } from "@/components/calender_modules/calendar-body";
import { CalendarProvider } from "@/components/calender_modules/contexts/calendar-context";
import { DndProvider } from "@/components/calender_modules/contexts/dnd-context";
import { CalendarHeader } from "@/components/calender_modules/header/calendar-header";
import { pairIEventDenganUser, CutiIEvent, produceCutiIEvent, cuti } from "./types";
import { type User } from "@/types";
import { CalenderUser, produceCalenderUser, SettingCalender, type IEvent, type IUser } from "@/components/calender_modules/interfaces";
import { AddEditEventDialog } from "./dialog/add-edit-event-dialog";
import DeleteEventDialog from "./dialog/delete-event-dialog";
import { DndConfirmationDialog } from "./dialog/dnd-confirmation-dialog";
import { EventDetailsDialog } from "./dialog/event-details-dialog";
import { EventDropConfirmationDialog } from "./dialog/event-drop-confirmation-dialog";
import { EventListDialog } from "./dialog/events-list-dialog";
import { TCalendarView } from "@/components/calender_modules/types";


export function CutiCalender({
        cuti_data,
        user,
    }: {
        cuti_data: cuti[];
        user: User[];
}) {

    const settingCalender: SettingCalender = {
        showTime: false,
        name_event: 'Pegawai',
        calender_show: ["month","agenda"],
        show_filter: true,
        filter_name: [['green', 'statsulbar'], ['yellow', 'sp2020']],
        show_filter_user: false,
    }
    const _user: IUser[] = produceCalenderUser(user);
    const _allEvents: CutiIEvent[] = produceCutiIEvent(cuti_data, _user as CalenderUser[]);

    pairIEventDenganUser(_allEvents as CutiIEvent[], _user as CalenderUser[])

    /////////////////////
    function reload_data(selectedDate: Date, currentView: TCalendarView, setAllEventsOri: (_allEvents: IEvent[]) => void){

    }

    function updateDataRequest(event: IEvent, use24HourFormat: boolean, updateEvent: (newEvent: IEvent) => void, cancel: () => void){

    }

    function havePeran(peran : string):  boolean{
        return false;
    }

    function canAdd():  boolean{
        return false;
    }
    function canChange(event: IEvent):  boolean{
         return false;
    }
    function canApprove(event: IEvent):  boolean{
         return false;
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
                updateDataRequest={updateDataRequest}
                havePeran={havePeran}
                canAdd={canAdd}
                canChange={canChange}
                canApprove={canApprove}
            >
            <DndProvider showConfirmation={true}>
                <div className="w-full rounded-xl mx-2">
                    <CalendarHeader />
                    <CalendarBody />
                </div>
            </DndProvider>
        </CalendarProvider>
    );
}

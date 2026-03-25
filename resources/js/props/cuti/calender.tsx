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
import { useEffect, useState } from "react";
import { useLoading } from "../LoadingContext";
import { useApi } from "@/lib/API/useApi";
import { toast } from "sonner";
import { progress } from "@inertiajs/react";


export function CutiCalender({
        // cuti_data,
        user,
    }: {
        // cuti_data: cuti[];
        user: User[];
}) {
    const {setIsLoading} = useLoading();
    const [isFirst, setIsFirst] = useState<boolean>(true);
    const axiosClient = useApi();

    const settingCalender: SettingCalender = {
        showTime: false,
        name_event: 'Pegawai',
        calender_show: ["month","agenda"],
        show_filter: true,
        filter_name: [['green', 'statsulbar'], ['yellow', 'sp2020']],
        show_filter_user: false,
    }


    const _user: IUser[] = produceCalenderUser(user);
    const [_allEvents, _setAllEvents] = useState<CutiIEvent[]>([])

    useEffect(() => {
        if(isFirst){
            setIsLoading(true)
            toast.info('Meload data baru')
            progress.start()
            axiosClient.post(route("cek-cuti-index"))
                .then(function (response: any) {
                    try {
                        const _allEvents_: CutiIEvent[] = produceCutiIEvent(response.data.cuti_daftar, _user as CalenderUser[]);

                        pairIEventDenganUser(_allEvents as CutiIEvent[], _user as CalenderUser[])

                        _setAllEvents(_allEvents_)
                        progress.finish()
                        setIsLoading(false)
                        setIsFirst(false)
                        toast.success('Berhasil mendapatkan data baru')
                    } catch (error) {
                        console.log(error)
                        progress.finish()
                        setIsLoading(false)
                        toast.error('Gagal mendapatkan data baru')
                    }
                })
                .catch(function (error: any) {
                    console.log(error)
                    progress.finish()
                    setIsLoading(false)
                    toast.error('Gagal mendapatkan data baru!')
                });
        }
    }, []);


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
        <>
        {!isFirst &&
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
        }
        </>


    );
}

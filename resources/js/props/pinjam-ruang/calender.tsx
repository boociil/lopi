import { CalendarBody } from "@/components/calender_modules/calendar-body";
import { CalendarProvider } from "@/components/calender_modules/contexts/calendar-context";
import { DndProvider } from "@/components/calender_modules/contexts/dnd-context";
import { CalendarHeader } from "@/components/calender_modules/header/calendar-header";
import { pairIEventDenganUser, pinjam_ruang, pinjam_ruang_req, PinjamRuangIEvent, producePinjamRuangIEvent } from "./types";
import { type SharedData, type User } from "@/types";
import { CalenderUser, produceCalenderUser, SettingCalender, type IEvent, type IUser } from "@/components/calender_modules/interfaces";
import { AddEditEventDialog } from "./dialog/add-edit-event-dialog";
import DeleteEventDialog from "./dialog/delete-event-dialog";
import { DndConfirmationDialog } from "./dialog/dnd-confirmation-dialog";
import { EventDetailsDialog } from "./dialog/event-details-dialog";
import { EventDropConfirmationDialog } from "./dialog/event-drop-confirmation-dialog";
import { EventListDialog } from "./dialog/events-list-dialog";
import { TCalendarView } from "@/components/calender_modules/types";
import { toast } from "sonner";
import { usePage } from "@inertiajs/react";
import { formatDate, formatTime } from "@/components/calender_modules/helpers";
import { useApi } from "@/lib/API/useApi";
import { progress } from '@inertiajs/react'

export function PinjamRuangCalender({
        pinjam_ruang_data,
        user,
        havePinjamRuangPeran,
    }: {
        pinjam_ruang_data: pinjam_ruang[];
        user: User[];
        havePinjamRuangPeran: boolean;
}) {

    const axiosClient = useApi();
    const auth = usePage<SharedData>().props.auth;

    const settingCalender: SettingCalender = {name_event: 'kegiatan', calender_show: ["week", "month","agenda"], show_filter: true, filter_name: [['blue', "Pengajuan"], ['green', 'Aula'], ['yellow', 'Pokja']], show_filter_user: false}
    const _user: IUser[] = produceCalenderUser(user);
    const _allEvents: IEvent[] = producePinjamRuangIEvent(pinjam_ruang_data);
    pairIEventDenganUser(_allEvents as PinjamRuangIEvent[], _user as CalenderUser[])

    /////////////////////
    function reload_data(selectedDate: Date, currentView: TCalendarView, setAllEventsOri: (_allEvents: IEvent[]) => void){
        toast.info('Meload data baru')
        progress.start()
        axiosClient.post(route("pinjam-ruang-index"), {'selectedDate': selectedDate.toISOString(), view: currentView})
            .then(function (response: any) {
                try {
                    const _allEvents_: IEvent[] = producePinjamRuangIEvent(response.data.data as pinjam_ruang[]);
                    pairIEventDenganUser(_allEvents_ as PinjamRuangIEvent[], _user as CalenderUser[])
                    setAllEventsOri(_allEvents_)
                    progress.finish()
                    toast.success('Berhasil mendapatkan data baru')
                } catch (error) {
                    console.log(error)
                    progress.finish()
                    toast.error('Gagal mendapatkan data baru')
                }
            })
            .catch(function (error: any) {
                console.log(error)
                progress.finish()
                toast.error('Gagal mendapatkan data baru!')
            });
    }

     function updateDataRequest(event: IEvent, use24HourFormat: boolean, updateEvent: (newEvent: IEvent) => void, cancel: () => void){
        const data = {
            id: event.id,
            kegiatan:  event.title,
            tipe: 'sekali',
            tanggal:  formatDate(event.startDate),
            tanggal_awal: formatDate(event.startDate),
            tanggal_akhir: formatDate(event.startDate),
            waktu_awal: formatTime(event.startDate, use24HourFormat),
            waktu_akhir:  formatTime(event.endDate, use24HourFormat),
            status: 'menunggu',
        }

        toast.info('Mencoba mengubah data')
        progress.start()
        axiosClient.post(route("pinjam-ruang-ubah", { pinjamRuang: event.id }), data)
            .then(function (response: any) {
                try {
                    const return_data: IEvent[] = producePinjamRuangIEvent([response.data.data] as pinjam_ruang[]);
                    pairIEventDenganUser(return_data  as PinjamRuangIEvent[], _user as CalenderUser[])
                    updateEvent(return_data[0])
                    progress.finish()
                    toast.success('Berhasil mengubah data')
                } catch (error) {
                    console.log(error)
                    progress.finish()
                    toast.error('Gagal mengubah data')
                    cancel()
                }
            })
            .catch(function (error: any) {
                console.log(error)
                progress.finish()
                toast.error('Gagal mengubah data')
            });
    }

    function havePeran():  boolean{
        return havePinjamRuangPeran
    }

    function canAdd():  boolean{
        return true
    }
    function canChange(event: IEvent):  boolean{
        const event_ = event as PinjamRuangIEvent
        if(havePinjamRuangPeran || event_.pinjam_ruang_ori.pembuat_username == auth.user.username){
            return true
        }else{
            return false
        }
    }
    function canApprove(event: IEvent):  boolean{
        const event_ = event as PinjamRuangIEvent
        if(havePinjamRuangPeran && event_.pinjam_ruang_ori.status == 'menunggu'){
            return true
        }else{
            return false
        }
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

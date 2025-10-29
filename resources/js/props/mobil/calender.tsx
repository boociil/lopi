import { CalendarBody } from "@/components/calender_modules/calendar-body";
import { CalendarProvider } from "@/components/calender_modules/contexts/calendar-context";
import { DndProvider } from "@/components/calender_modules/contexts/dnd-context";
import { CalendarHeader } from "@/components/calender_modules/header/calendar-header";
import { pairIEventDenganUser, pinjam_mobil, pinjam_mobil_req, PinjamMobilIEvent, producePinjamMobilIEvent } from "./types";
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
import { useApiForm } from "@/lib/API/useAPIForm";
import { usePage } from "@inertiajs/react";
import { useState } from "react";
import { formatDate, formatTime } from "@/components/calender_modules/helpers";
import { useApi } from "@/lib/API/useApi";
import { progress } from '@inertiajs/react'

export function PinjamMobilCalender({
        pinjam_mobil_data,
        user,
        havePinjamMobilPeran,
    }: {
        pinjam_mobil_data: pinjam_mobil[];
        user: User[];
        havePinjamMobilPeran: boolean;
}) {

    const axiosClient = useApi();
    const auth = usePage<SharedData>().props.auth;

    const settingCalender: SettingCalender = {name_event: 'kegiatan', calender_show: ["week", "month","agenda"], show_filter: false, show_filter_user: false}
    const _user: IUser[] = produceCalenderUser(user);
    const _allEvents: IEvent[] = producePinjamMobilIEvent(pinjam_mobil_data);
    pairIEventDenganUser(_allEvents as PinjamMobilIEvent[], _user as CalenderUser[])

    /////////////////////
    function reload_data(selectedDate: Date, currentView: TCalendarView, setAllEventsOri: (_allEvents: IEvent[]) => void){
        toast.info('Meload data baru')
        progress.start()
        axiosClient.post(route("pinjam-mobil-index"), {'selectedDate': selectedDate.toISOString(), view: currentView})
            .then(function (response: any) {
                try {
                    const _allEvents_: IEvent[] = producePinjamMobilIEvent(response.data.data as pinjam_mobil[]);
                    pairIEventDenganUser(_allEvents_ as PinjamMobilIEvent[], _user as CalenderUser[])
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

     function updateDataRequest(event: IEvent, use24HourFormat: boolean, updateEvent: (newEvent: IEvent[], oldEventIds: string[], oldBerulangEventIds: string[]) => void, cancel: () => void){
        const data = {
            id: event.id,
            kegiatan:  event.title,
            tipe: 'sekali',
            berulang_id: null,
            tanggal:  formatDate(event.startDate),
            tanggal_awal: formatDate(event.startDate),
            tanggal_akhir: formatDate(event.startDate),
            waktu_awal: formatTime(event.startDate, use24HourFormat),
            waktu_akhir:  formatTime(event.endDate, use24HourFormat),
            status: 'menunggu',
        }

        toast.info('Mencoba mengubah data')
        progress.start()
        axiosClient.post(route("pinjam-mobil-ubah", { pinjamMobil: event.id }), data)
            .then(function (response: any) {
                try {
                    const return_data: IEvent[] = producePinjamMobilIEvent(response.data.data as pinjam_mobil[]);
                    pairIEventDenganUser(return_data  as PinjamMobilIEvent[], _user as CalenderUser[])
                    if(event.repeat_id){
                        updateEvent(return_data, [event.id], [event.repeat_id])
                    }else{
                        updateEvent(return_data, [event.id], [])
                    }
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

    function canAdd():  boolean{
        return true
    }
    function canChange(event: IEvent):  boolean{
        const event_ = event as PinjamMobilIEvent
        if(havePinjamMobilPeran || event_.pinjam_mobil_ori.pembuat_username == auth.user.username){
            return true
        }else{
            return false
        }
    }
    function canApprove(event: IEvent):  boolean{
        return true
        if(havePinjamMobilPeran){
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
                canAdd={canAdd}
                canChange={canChange}
                canApprove={canApprove}
            >
            <DndProvider showConfirmation={false}>
                <div className="w-full rounded-xl mx-2">
                    <CalendarHeader />
                    <CalendarBody />
                </div>
            </DndProvider>
        </CalendarProvider>
    );
}

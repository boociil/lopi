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

export function PinjamMobilCalender({
        pinjam_mobil_data,
        user,
        havePinjamMobilPeran,
    }: {
        pinjam_mobil_data: pinjam_mobil[];
        user: User[];
        havePinjamMobilPeran: boolean;
}) {

    const refresForm = useApiForm<{
        selectedDate: string;
        currentView: string;
    }>({
        selectedDate: ""+(new Date()).toISOString(),
        currentView: 'week',
    });

    const updateForm = useApiForm<
        Required<pinjam_mobil_req>
    >({
        id: "12345",
        kegiatan:  "Peminjaman Mobil",
        tipe: 'string',
        berulang_id: null,
        tanggal: formatDate(new Date().toISOString()),
        tanggal_awal: formatDate(new Date().toISOString()),
        tanggal_akhir: formatDate(new Date().toISOString()),
        waktu_awal: "07:30:00",
        waktu_akhir: "16:00:00",
        status: 'menunggu',
        pembuat_username: '',
        confirm_username: null
    });


    const auth = usePage<SharedData>().props.auth;

    const settingCalender: SettingCalender = {name_event: 'kegiatan', calender_show: ["week", "month","agenda"], show_filter: false, show_filter_user: false}
    const _user: IUser[] = produceCalenderUser(user);
    const _allEvents: IEvent[] = producePinjamMobilIEvent(pinjam_mobil_data);
    pairIEventDenganUser(_allEvents as PinjamMobilIEvent[], _user as CalenderUser[])

    /////////////////////
    function reload_data(selectedDate: Date, currentView: TCalendarView, setAllEventsOri: (_allEvents: IEvent[]) => void){
        refresForm.post(route("pinjam-mobil-index"),
            { async: true, showProgress: true,
                onBefore:  (page) => {
                    toast.info('Meload data baru !')
                    refresForm.setData('selectedDate', selectedDate.toISOString())
                    refresForm.setData('currentView', currentView)
                },
                onSuccess: (page) => {
                    const _allEvents_: IEvent[] = producePinjamMobilIEvent(page.props.data as pinjam_mobil[]);
                    pairIEventDenganUser(_allEvents_ as PinjamMobilIEvent[], _user as CalenderUser[])
                    setAllEventsOri(_allEvents_)
                    toast.success('Berhasil mendapatkan data baru !')
                },
                onError: (page) => {
                    toast.error('Gagal mendapatkan data baru !')
                }
            }
        )
    }

     function updateDataRequest(event: IEvent, use24HourFormat: boolean, updateEvent: (event: IEvent[]) => void){
        updateForm.setData("id", event.id);
        updateForm.setData("kegiatan", event.title);
        updateForm.setData('tipe', 'sekali')
        updateForm.setData("berulang_id", null);
        updateForm.setData("tanggal_awal",  formatDate(event.startDate));
        updateForm.setData("tanggal_akhir",  formatDate(event.startDate));
        updateForm.setData("tanggal", formatDate(event.startDate));
        updateForm.setData("waktu_awal", formatTime(event.startDate, use24HourFormat));
        updateForm.setData("waktu_akhir", formatTime(event.endDate, use24HourFormat));
        updateForm.post(route("pinjam-mobil-ubah", { pinjamMobil: event.id }),
            { async: true, showProgress: true,
                onSuccess: (page) => {
                    try {
                        const return_data = page.props.data as pinjam_mobil[]
                        updateEvent(producePinjamMobilIEvent(return_data))
                        toast.success('Berhasil mengubah data !')
                    } catch (error) {
                        console.log(error)
                        toast.error('Gagal mengubah data !')
                    }
                },
                onError: (page) => {
                    toast.error('Gagal mengubah data !')
                }
            }
        )
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

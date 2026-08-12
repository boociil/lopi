import { CalendarBody } from "@/components/calender_modules/calendar-body";
import { CalendarProvider } from "@/components/calender_modules/contexts/calendar-context";
import { DndProvider } from "@/components/calender_modules/contexts/dnd-context";
import { CalendarHeader } from "@/components/calender_modules/header/calendar-header";
import { jadwal, pairIEventDenganUser, JadwalIEvent, produceJadwalIEvent, SettingCalenderJadwal, pairIEventDenganPeserta } from "./types";
import { Satuan_Kerja_Tahun, Tim, type SharedData, type User } from "@/types";
import { produceCalenderUser, SettingCalender, type IEvent, type IUser } from "@/components/calender_modules/interfaces";
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
import { useLoading } from "../LoadingContext";

export function JadwalCalender({
        jadwal_data,
        user,
        satuan_kerja_tahun,
        tim,
        have_zoom_peran,
        have_ruang_peran,
        ruang,
    }: {
        jadwal_data: jadwal[];
        user: User[];
        satuan_kerja_tahun: Satuan_Kerja_Tahun[];
        tim: Tim[];
        have_zoom_peran: boolean;
        have_ruang_peran: boolean;
        ruang: string[],
}) {
    const {setIsLoading} = useLoading();

    const axiosClient = useApi();
    const auth = usePage<SharedData>().props.auth;

    const settingCalender: SettingCalenderJadwal = {
        showTime: true,
        name_event: 'kegiatan',
        calender_show: ["week", "month","agenda"],
        show_filter: true,
        filter_name: [['green', 'statsulbar'], ['yellow', 'sp2020']],
        show_filter_user: true,
        ruang: ruang
    }

    const _user: IUser[] = produceCalenderUser(user, satuan_kerja_tahun, tim);
    const _allEvents: JadwalIEvent[] = produceJadwalIEvent(jadwal_data);
    pairIEventDenganPeserta(_allEvents as JadwalIEvent[], _user)

    /////////////////////
    function reload_data(selectedDate: Date, currentView: TCalendarView, setAllEventsOri: (_allEvents: IEvent[]) => void){
        setIsLoading(true)
        toast.info('Meload data baru')
        progress.start()
        axiosClient.post(route("jadwal-index"), {'selectedDate': selectedDate.toISOString(), view: currentView})
            .then(function (response: any) {
                try {
                    const _allEvents_: JadwalIEvent[] = produceJadwalIEvent(response.data.jadwal_daftar as jadwal[]);

                    pairIEventDenganUser(_allEvents_ as JadwalIEvent[], _user)

                    setAllEventsOri(_allEvents_)
                    progress.finish()
                    setIsLoading(false)
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
        setIsLoading(true)
        toast.info('Mencoba mengubah data')
        progress.start()
        axiosClient.post(route("jadwal-ubah", { jadwal: event.id }), data)
            .then(function (response: any) {
                try {
                    const return_data: IEvent[] = produceJadwalIEvent([response.data.data] as jadwal[]);
                    pairIEventDenganUser(return_data  as JadwalIEvent[], _user)
                    updateEvent(return_data[0])
                    progress.finish()
                    setIsLoading(false)
                    toast.success('Berhasil mengubah data')
                } catch (error) {
                    console.log(error)
                    progress.finish()
                    setIsLoading(false)
                    toast.error('Gagal mengubah data')
                    cancel()
                }
            })
            .catch(function (error: any) {
                console.log(error)
                progress.finish()
                setIsLoading(false)
                toast.error('Gagal mengubah data')
            });
    }

    function havePeran(peran : string):  boolean{
        if(peran == 'zoom'){
            return have_zoom_peran
        }else if(peran == 'ruang'){
            return have_ruang_peran
        }
        return have_zoom_peran && have_ruang_peran

    }
    function canAdd():  boolean{
        return true
    }
    function canChange(event: IEvent):  boolean{
        const event_ = event as JadwalIEvent
        if(!event_.jadwal_ori){
            return false
        }
        if(event_.jadwal_ori.pembuat_username == auth.user.username){
            return true
        }else{
            return false
        }
    }
    function canDelete(event: IEvent):  boolean{
        const event_ = event as JadwalIEvent
        if(!event_.jadwal_ori){
            return false
        }
        if(event_.jadwal_ori.pembuat_username == auth.user.username){
            return true
        }else{
            return false
        }
    }
    function canApprove(event: IEvent, type?: string):  boolean{
        const event_ = event as JadwalIEvent
        if(!event_.jadwal_ori){
            return false
        }
        if (type == null){
            return false
        }else if(type == 'zoom'){
            if(have_zoom_peran && event_.jadwal_ori.zoom_status == 'menunggu'){
                return true
            }else{
                return false
            }
        }else if(type == 'ruang'){
            if(have_zoom_peran && event_.jadwal_ori.ruang_status == 'menunggu'){
                return true
            }else{
                return false
            }
        }
        return false
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
                canDelete={canDelete}
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

import React from "react";
import { CalendarBody } from "@/components/calender_modules/calendar-body";
import { CalendarProvider } from "@/components/calender_modules/contexts/calendar-context";
import { DndProvider } from "@/components/calender_modules/contexts/dnd-context";
import { CalendarHeader } from "@/components/calender_modules/header/calendar-header";
import { pinjam_mobil, producePinjamMobilIEvent } from "./types";
import { User } from "@/types";
import { produceCalenderUser, SettingCalender, type IEvent, type IUser } from "@/components/calender_modules/interfaces";

export function PinjamMobilCalender({
        pinjam_mobil_data,
        user,
    }: {
        pinjam_mobil_data: pinjam_mobil[]
        user: User[]
}) {

    const  event_data: IEvent[] = producePinjamMobilIEvent(pinjam_mobil_data)
    const user_data: IUser[] = produceCalenderUser(user);
    const settingCalender: SettingCalender = {name_event: 'kegiatan', calender_show: ["week", "month","agenda"], show_filter: false, show_filter_user: false}

    return (
        <CalendarProvider events={event_data} users={user_data} settingCalender={settingCalender} view="week">
            <DndProvider showConfirmation={false}>
                <div className="w-full rounded-xl border-accent-foreground mx-2">
                    <CalendarHeader />
                    <CalendarBody />
                </div>
            </DndProvider>
        </CalendarProvider>
    );
}

import { useEffect, useState } from "react";
import { CalendarBody } from "@/components/calender_modules/calendar-body";
import { CalendarProvider } from "@/components/calender_modules/contexts/calendar-context";
import { DndProvider } from "@/components/calender_modules/contexts/dnd-context";
import { CalendarHeader } from "@/components/calender_modules/header/calendar-header";
import { pinjam_mobil, producePinjamMobilIEvent } from "./types";
import { User } from "@/types";
import { CalendarSettings, produceCalenderUser, SettingCalender, type IEvent, type IUser } from "@/components/calender_modules/interfaces";
import { AddEditEventDialog } from "./dialog/add-edit-event-dialog";
import DeleteEventDialog from "./dialog/delete-event-dialog";
import { DndConfirmationDialog } from "./dialog/dnd-confirmation-dialog";
import { EventDetailsDialog } from "./dialog/event-details-dialog";
import { EventDropConfirmationDialog } from "./dialog/event-drop-confirmation-dialog";
import { EventListDialog } from "./dialog/events-list-dialog";
import { TCalendarView, TEventColor } from "@/components/calender_modules/types";
import { useLocalStorage } from "@/components/calender_modules/hooks";
import { router } from "@inertiajs/react";

export function PinjamMobilCalender({
        pinjam_mobil_data,
        user,
    }: {
        pinjam_mobil_data: pinjam_mobil[]
        user: User[]
}) {

    const settingCalender: SettingCalender = {name_event: 'kegiatan', calender_show: ["week", "month","agenda"], show_filter: false, show_filter_user: false}
    const [users, setUsers] = useState<IUser[]>(produceCalenderUser(user));
    const [settings, setSettings] = useLocalStorage<CalendarSettings>(
		"pinjam_mobil_calender",
		{
			badgeVariant: "colored",
            use24HourFormat: true,
            agendaModeGroupBy: "date",
			view: "week",
		},
	);

	const [badgeVariant, setBadgeVariantState] = useState<"dot" | "colored">(
		settings.badgeVariant,
	);
	const [currentView, setCurrentViewState] = useState<TCalendarView>(
		settings.view,
	);
	const [use24HourFormat, setUse24HourFormatState] = useState<boolean>(
		settings.use24HourFormat,
	);
	const [agendaModeGroupBy, setAgendaModeGroupByState] = useState<
		"date" | "color"
	>(settings.agendaModeGroupBy);

	const [selectedDate, setSelectedDate] = useState(new Date());
	const [selectedUserId, setSelectedUserId] = useState<IUser["id"] | "all">(
		"all",
	);
	const [selectedColors, setSelectedColors] = useState<TEventColor[]>([]);

	const [allEvents, setAllEvents] = useState<IEvent[]>(producePinjamMobilIEvent(pinjam_mobil_data) || []);
	const [filteredEvents, setFilteredEvents] = useState<IEvent[]>(producePinjamMobilIEvent(pinjam_mobil_data) || []);

    /////////////////////
    useEffect(() => {
        console.log('reload_data')
        router.get(route("pinjam-mobil-index"), {selectedDate: selectedDate, view: currentView}
            , { async: true, showProgress: true,
                onSuccess: (page) => {
                    console.log('✅ Success!', page.props.as)
                    setAllEvents(producePinjamMobilIEvent(page.props.data))
                    const filtered = allEvents.filter((event) => {
                        const eventColor = event.color || "blue";
                        return selectedColors.includes(eventColor);
                    });
                    setFilteredEvents(filtered);
                }
            }
        )
    }, [currentView, selectedDate]);



    return (
        <CalendarProvider settingCalender={settingCalender}
                users={users}
                setUsers={setUsers}
                settings={settings}
                setSettings={setSettings}
                badgeVariant={badgeVariant}
                setBadgeVariantState={setBadgeVariantState}
                currentView={currentView}
                setCurrentViewState={setCurrentViewState}
                use24HourFormat={use24HourFormat}
                setUse24HourFormatState={setUse24HourFormatState}
                agendaModeGroupBy={agendaModeGroupBy}
                setAgendaModeGroupByState={setAgendaModeGroupByState}
                selectedDate={selectedDate}
                setSelectedDate={setSelectedDate}
                selectedUserId={selectedUserId}
                setSelectedUserId={setSelectedUserId}
                selectedColors={selectedColors}
                setSelectedColors={setSelectedColors}
                allEvents={allEvents}
                setAllEvents={setAllEvents}
                filteredEvents={filteredEvents}
                setFilteredEvents={setFilteredEvents}

                AddEditEventDialog={AddEditEventDialog}
                DeleteEventDialog={DeleteEventDialog}
                DndConfirmationDialog={DndConfirmationDialog}
                EventDetailsDialog={EventDetailsDialog}
                EventDropConfirmationDialog={EventDropConfirmationDialog}
                EventListDialog={EventListDialog}
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

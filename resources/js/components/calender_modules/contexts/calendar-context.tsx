"use client";

import type React from "react";
import { createContext, JSX, MemoExoticComponent, useContext, useEffect, useState } from "react";
import { useLocalStorage } from "@/components/calender_modules/hooks";
import type { CalendarSettings, IEvent, IUser, SettingCalender } from "@/components/calender_modules/interfaces";
import type {
	TCalendarView,
	TEventColor,
} from "@/components/calender_modules/types";
import { Add_Edit_Event_IProps, Delete_Event_Dialog_IProps, Event_Details_Dialog_IProps, Event_Drop_Confirmation_Dialog_IProps, Event_List_Dialog_IProps } from "../dialogs/props-dialog";
import { isSameDay, parseISO } from "date-fns";

interface ICalendarContext {
	selectedDate: Date;
	view: TCalendarView;
	setView: (view: TCalendarView) => void;
	agendaModeGroupBy: "date" | "color";
	setAgendaModeGroupBy: (groupBy: "date" | "color") => void;
	use24HourFormat: boolean;
	toggleTimeFormat: () => void;
	setSelectedDate: (date: Date | undefined) => void;
	selectedUserId: IUser["id"] | "all";
	setSelectedUserId: (userId: IUser["id"] | "all") => void;
	badgeVariant: "dot" | "colored";
	setBadgeVariant: (variant: "dot" | "colored") => void;
	selectedColors: TEventColor[];
	filterEventsBySelectedColors: (colors: TEventColor) => void;
	filterEventsBySelectedUser: (userId: IUser["id"] | "all") => void;
	users: IUser[];
	events: IEvent[];
	addEvent: (event: IEvent) => void;
	updateEvent: (event: IEvent) => void;
	removeEvent: (eventId: string) => void;
	clearFilter: () => void;
    getEvent: (eventId: string) => IEvent | undefined;
    getSingleDaysEvent: () => IEvent[],
    getMultipleDaysEvent: () => IEvent[],
    settingCalender: SettingCalender;

    AddEditEventDialog: React.ComponentType<Add_Edit_Event_IProps>;
    DeleteEventDialog: React.ComponentType<Delete_Event_Dialog_IProps>;
    DndConfirmationDialog: MemoExoticComponent<() => JSX.Element | null>;
    EventDetailsDialog: React.ComponentType<Event_Details_Dialog_IProps>;
    EventDropConfirmationDialog: React.ComponentType<Event_Drop_Confirmation_Dialog_IProps>;
    EventListDialog: React.ComponentType<Event_List_Dialog_IProps>;
}

const DEFAULT_SETTINGS: CalendarSettings = {
	badgeVariant: "colored",
	view: "day",
	use24HourFormat: true,
	agendaModeGroupBy: "date",
};

const CalendarContext = createContext({} as ICalendarContext);

export function CalendarProvider({
    settingCalender,
    _user,
    _allEvents,
    AddEditEventDialog,
    DeleteEventDialog,
    DndConfirmationDialog,
    EventDetailsDialog,
    EventDropConfirmationDialog,
    EventListDialog,
    reload_data,
    children,
}: {
    _user: IUser[];
    _allEvents: IEvent[];
	settingCalender: SettingCalender;
    AddEditEventDialog: React.ComponentType<Add_Edit_Event_IProps>;
    DeleteEventDialog: React.ComponentType<Delete_Event_Dialog_IProps>;
    DndConfirmationDialog: MemoExoticComponent<() => JSX.Element | null>;
    EventDetailsDialog: React.ComponentType<Event_Details_Dialog_IProps>;
    EventDropConfirmationDialog: React.ComponentType<Event_Drop_Confirmation_Dialog_IProps>;
    EventListDialog: React.ComponentType<Event_List_Dialog_IProps>;
    reload_data: (selectedDate: Date, currentView: TCalendarView, setAllEventsOri: (_allEvents: IEvent[]) => void) => void;
    children: React.ReactNode;
}) {

    const [users, setUsers] = useState<IUser[]>(_user);
    const [allEvents, setAllEvents] = useState<IEvent[]>(_allEvents || []);
    const [filteredEvents, setFilteredEvents] = useState<IEvent[]>(_allEvents || []);

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


	const updateSettings = (newPartialSettings: Partial<CalendarSettings>) => {
		setSettings({
			...settings,
			...newPartialSettings,
		});
	};

	const setBadgeVariant = (variant: "dot" | "colored") => {
		setBadgeVariantState(variant);
		updateSettings({ badgeVariant: variant });
	};

	const setView = (newView: TCalendarView) => {
		setCurrentViewState(newView);
		updateSettings({ view: newView });
        reload_data(selectedDate, currentView, setAllEventsOri)
	};

	const toggleTimeFormat = () => {
		const newValue = !use24HourFormat;
		setUse24HourFormatState(newValue);
		updateSettings({ use24HourFormat: newValue });
	};

	const setAgendaModeGroupBy = (groupBy: "date" | "color") => {
		setAgendaModeGroupByState(groupBy);
		updateSettings({ agendaModeGroupBy: groupBy });
	};

	const filterEventsBySelectedColors = (color: TEventColor) => {
		const isColorSelected = selectedColors.includes(color);
		const newColors = isColorSelected
			? selectedColors.filter((c) => c !== color)
			: [...selectedColors, color];
		setSelectedColors(newColors);

        runFilter();
	};

	const filterEventsBySelectedUser = (userId: IUser["id"] | "all") => {
        if(userId == null){
            return
        }
		setSelectedUserId(userId);
		runFilter();
	};

	const handleSelectDate = (date: Date | undefined) => {
		if (!date) return;
		setSelectedDate(date);
        reload_data(selectedDate, currentView, setAllEventsOri)
	};

	const addEvent = (event: IEvent) => {
		setAllEvents([...allEvents, event]);
		setFilteredEvents([...filteredEvents, event]);
	};

    const getEvent = (eventId: String) => {
        return allEvents.find(item => item.id == eventId)
	};

	const updateEvent = (event: IEvent) => {
		const updated = {
			...event,
			startDate: new Date(event.startDate).toISOString(),
			endDate: new Date(event.endDate).toISOString(),
		};

		setAllEvents(allEvents.map((e) => (e.id === event.id ? updated : e)));
		setFilteredEvents(
			filteredEvents.map((e) => (e.id === event.id ? updated : e)),
		);
	};

    const removeEvent = (eventId: string) => {
		setAllEvents(allEvents.filter((e) => e.id !== eventId));
		setFilteredEvents(filteredEvents.filter((e) => e.id !== eventId));
	};

	const clearFilter = () => {
		setFilteredEvents(allEvents);
		setSelectedColors([]);
		setSelectedUserId("all");
	};

    const getSingleDaysEvent = () : IEvent[] => {
        return allEvents
    }
    const getMultipleDaysEvent = () : IEvent[] =>  {
        const return_data : IEvent[] = []
        const repeat_id_s : string[] = []
        allEvents.forEach(element => {
            if(element.repeat_id != null){
                if(!repeat_id_s.includes(element.repeat_id)){
                    return_data.push(element)
                    repeat_id_s.push(element.repeat_id)
                }else{
                    if(element.startDate < return_data[repeat_id_s.findIndex((item) => item == element.repeat_id)].startDate){
                        return_data[repeat_id_s.findIndex((item) => item == element.repeat_id)].startDate = element.startDate
                    }
                    if(element.endDate > return_data[repeat_id_s.findIndex((item) => item == element.repeat_id)].endDate){
                        return_data[repeat_id_s.findIndex((item) => item == element.repeat_id)].endDate = element.endDate
                    }
                }
            }
        });
        return return_data
    }

    function setAllEventsOri(_oriAllEvent: IEvent[]){
        setAllEvents(_oriAllEvent)
        runFilter()
    }

    function runFilter(){
		if (selectedUserId === "all" && selectedColors.length == 0) {
			setFilteredEvents(allEvents);
		}else {
			let filtered = allEvents;
            if(selectedUserId != 'all'){
                filtered = filtered.filter((event) => event.user?.id === selectedUserId);
            }
            if(selectedColors.length > 0){
                filtered = filtered.filter((event) => {
                    const eventColor = event.color || "blue";
                    return selectedColors.includes(eventColor);
                });
            }
			setFilteredEvents(filtered);
		}
    }

	return (
		<CalendarContext.Provider value={{
            selectedDate,
            setSelectedDate: handleSelectDate,
            selectedUserId,
            setSelectedUserId,
            badgeVariant,
            setBadgeVariant,
            users,
            selectedColors,
            filterEventsBySelectedColors,
            filterEventsBySelectedUser,
            events: filteredEvents,
            view: currentView,
            use24HourFormat,
            toggleTimeFormat,
            setView,
            agendaModeGroupBy,
            setAgendaModeGroupBy,
            addEvent,
            updateEvent,
            removeEvent,
            getEvent,
            clearFilter,
            getSingleDaysEvent,
            getMultipleDaysEvent,
            settingCalender,
            AddEditEventDialog,
            DeleteEventDialog,
            DndConfirmationDialog,
            EventDetailsDialog,
            EventDropConfirmationDialog,
            EventListDialog,
        }}>
			{children}
		</CalendarContext.Provider>
	);
}

export function useCalendar(): ICalendarContext {
	const context = useContext(CalendarContext);
	if (!context)
		throw new Error("useCalendar must be used within a CalendarProvider.");
	return context;
}

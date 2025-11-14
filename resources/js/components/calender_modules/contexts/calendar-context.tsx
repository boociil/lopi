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
import { useApi } from '@/lib/API/useApi';
import { AxiosInstance } from 'axios';

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
    updateEventDragRezise: (event: IEvent) => void;
	updateEvent: (event: IEvent) => void;
    updateEventLokal: (event: IEvent) => void;
	removeEvent: (eventId: string) => void;
	clearFilter: () => void;
    getEvent: (eventId: string) => IEvent | undefined;
    settingCalender: SettingCalender;

    AddEditEventDialog: React.ComponentType<Add_Edit_Event_IProps>;
    DeleteEventDialog: React.ComponentType<Delete_Event_Dialog_IProps>;
    DndConfirmationDialog: MemoExoticComponent<() => JSX.Element | null>;
    EventDetailsDialog: React.ComponentType<Event_Details_Dialog_IProps>;
    EventDropConfirmationDialog: React.ComponentType<Event_Drop_Confirmation_Dialog_IProps>;
    EventListDialog: React.ComponentType<Event_List_Dialog_IProps>;

    havePeran: () => boolean;
    canAdd: () => boolean;
    canChange: (event: IEvent) => boolean;
    canApprove: (event: IEvent) => boolean;

    axiosClient: AxiosInstance;
}

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
    updateDataRequest,
    havePeran,
    canAdd,
    canChange,
    canApprove,
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
    updateDataRequest: (event: IEvent, use24HourFormat: boolean, updateEvent: (newEvent: IEvent) => void, cancel: () => void) => void;

    havePeran: () => boolean;
    canAdd: () => boolean;
    canChange: (event: IEvent) => boolean;
    canApprove: (event: IEvent) => boolean;
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


    useEffect(() => {
        runFilter()
    }, [allEvents, selectedUserId, selectedColors]);


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
        reload_data(selectedDate, newView, setAllEventsOri)
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
	};

	const handleSelectDate = (date: Date | undefined) => {
		if (!date) return;
		setSelectedDate(date);
        reload_data(date, currentView, setAllEventsOri)
	};

	const addEvent = (event: IEvent) => {
        setAllEvents((prev) => [...prev, event]);
		// setFilteredEvents([...filteredEvents, ...event]);
	};

    const getEvent = (eventId: String) => {
        return allEvents.find(item => item.id == eventId)
	};

    const updateEventDragRezise = (event: IEvent) => {
        updateDataRequest(event, use24HourFormat, updateEvent, runFilter)
	};

    const updateEvent = (newEvent: IEvent) => {
		setAllEvents((prev) => prev.map((e) => (e.id === newEvent.id ? newEvent : e)));
	};

    const updateEventLokal = (event: IEvent) => {
		const updated = {
			...event,
			startDate: new Date(event.startDate).toISOString(),
			endDate: new Date(event.endDate).toISOString(),
		};

		setFilteredEvents((prev) => prev.map((e) => (e.id === event.id ? updated : e)));
	};

    const removeEvent = (eventId: string) => {
        const item = allEvents.find(e => e.id == eventId)
        if(!item){
            return
        }
       	setAllEvents(allEvents.filter((e) => {
            if(e.id !== eventId){
                return true
            }
        }));
	};

	const clearFilter = () => {
		setFilteredEvents(allEvents);
		setSelectedColors([]);
		setSelectedUserId("all");
	};

    function setAllEventsOri(_oriAllEvent: IEvent[]){
        setAllEvents(_oriAllEvent)
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

    const axiosClient = useApi();

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
            updateEventLokal,
            updateEventDragRezise,
            removeEvent,
            getEvent,
            clearFilter,
            settingCalender,
            AddEditEventDialog,
            DeleteEventDialog,
            DndConfirmationDialog,
            EventDetailsDialog,
            EventDropConfirmationDialog,
            EventListDialog,

            havePeran,
            canAdd,
            canChange,
            canApprove,
            axiosClient,
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

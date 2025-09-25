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
	users,
    setUsers,
    settings,
    setSettings,
    badgeVariant,
    setBadgeVariantState,
    currentView,
    setCurrentViewState,
    use24HourFormat,
    setUse24HourFormatState,
    agendaModeGroupBy,
    setAgendaModeGroupByState,
    selectedDate,
    setSelectedDate,
    selectedUserId,
    setSelectedUserId,
    selectedColors,
    setSelectedColors,
    allEvents,
    setAllEvents,
    filteredEvents,
    setFilteredEvents,

    AddEditEventDialog,
    DeleteEventDialog,
    DndConfirmationDialog,
    EventDetailsDialog,
    EventDropConfirmationDialog,
    EventListDialog,
    children,
}: {
	settingCalender: SettingCalender;
	users: IUser[];
    setUsers: (users: IUser[]) => void;
    settings: CalendarSettings;
    setSettings: (settings: CalendarSettings) => void;
    badgeVariant: "dot" | "colored";
    setBadgeVariantState: (badgeVariant: "dot" | "colored") => void;
    currentView: TCalendarView;
    setCurrentViewState: (currentView: TCalendarView) => void;
    use24HourFormat: boolean;
    setUse24HourFormatState: (use24HourFormat: boolean) => void;
    agendaModeGroupBy: "date" | "color";
    setAgendaModeGroupByState: (agendaModeGroupBy: "date" | "color") => void;
    selectedDate: Date;
    setSelectedDate: (selectedDate: Date) => void;
    selectedUserId: IUser["id"] | "all";
    setSelectedUserId: (selectedUserId: IUser["id"] | "all") => void;
    selectedColors: TEventColor[];
    setSelectedColors: (selectedColors: TEventColor[]) => void;
    allEvents: IEvent[];
    setAllEvents: (allEvents: IEvent[]) => void;
    filteredEvents: IEvent[];
    setFilteredEvents: (filteredEvents: IEvent[]) => void;

    AddEditEventDialog: React.ComponentType<Add_Edit_Event_IProps>;
    DeleteEventDialog: React.ComponentType<Delete_Event_Dialog_IProps>;
    DndConfirmationDialog: MemoExoticComponent<() => JSX.Element | null>;
    EventDetailsDialog: React.ComponentType<Event_Details_Dialog_IProps>;
    EventDropConfirmationDialog: React.ComponentType<Event_Drop_Confirmation_Dialog_IProps>;
    EventListDialog: React.ComponentType<Event_List_Dialog_IProps>;
    children: React.ReactNode;
}) {

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

		if (newColors.length > 0) {
			const filtered = allEvents.filter((event) => {
				const eventColor = event.color || "blue";
				return newColors.includes(eventColor);
			});
			setFilteredEvents(filtered);
		} else {
			setFilteredEvents(allEvents);
		}

		setSelectedColors(newColors);
	};

	const filterEventsBySelectedUser = (userId: IUser["id"] | "all") => {
		setSelectedUserId(userId);
		if (userId === "all") {
			setFilteredEvents(allEvents);
		} else {
			const filtered = allEvents.filter((event) => event.user?.id === userId);
			setFilteredEvents(filtered);
		}
	};

	const handleSelectDate = (date: Date | undefined) => {
		if (!date) return;
		setSelectedDate(date);
	};

	const addEvent = (event: IEvent) => {
		setAllEvents([...allEvents, event]);
		setFilteredEvents([...filteredEvents, event]);
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
            clearFilter,
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

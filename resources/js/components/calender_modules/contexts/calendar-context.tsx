"use client";

import type React from "react";
import { createContext, JSX, MemoExoticComponent, useContext, useState } from "react";
import { useLocalStorage } from "@/components/calender_modules/hooks";
import type { IEvent, IUser, SettingCalender } from "@/components/calender_modules/interfaces";
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

interface CalendarSettings {
	badgeVariant: "dot" | "colored";
	view: TCalendarView;
	use24HourFormat: boolean;
	agendaModeGroupBy: "date" | "color";
}

const DEFAULT_SETTINGS: CalendarSettings = {
	badgeVariant: "colored",
	view: "day",
	use24HourFormat: true,
	agendaModeGroupBy: "date",
};

const CalendarContext = createContext({} as ICalendarContext);

export function CalendarProvider({
	children,
	users,
	events,
	badge = "colored",
	view = "day",
    settingCalender,
    AddEditEventDialog,
    DeleteEventDialog,
    DndConfirmationDialog,
    EventDetailsDialog,
    EventDropConfirmationDialog,
    EventListDialog
}: {
	children: React.ReactNode;
	users: IUser[];
	events: IEvent[];
	view?: TCalendarView;
	badge?: "dot" | "colored";
    settingCalender: SettingCalender;
    AddEditEventDialog: React.ComponentType<Add_Edit_Event_IProps>;
    DeleteEventDialog: React.ComponentType<Delete_Event_Dialog_IProps>;
    DndConfirmationDialog: MemoExoticComponent<() => JSX.Element | null>;
    EventDetailsDialog: React.ComponentType<Event_Details_Dialog_IProps>;
    EventDropConfirmationDialog: React.ComponentType<Event_Drop_Confirmation_Dialog_IProps>;
    EventListDialog: React.ComponentType<Event_List_Dialog_IProps>;
}) {
	const [settings, setSettings] = useLocalStorage<CalendarSettings>(
		"calendar-settings",
		{
			...DEFAULT_SETTINGS,
			badgeVariant: badge,
			view: view,
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

	const [allEvents, setAllEvents] = useState<IEvent[]>(events || []);
	const [filteredEvents, setFilteredEvents] = useState<IEvent[]>(events || []);

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
		setAllEvents((prev) => [...prev, event]);
		setFilteredEvents((prev) => [...prev, event]);
	};

	const updateEvent = (event: IEvent) => {
		const updated = {
			...event,
			startDate: new Date(event.startDate).toISOString(),
			endDate: new Date(event.endDate).toISOString(),
		};

		setAllEvents((prev) => prev.map((e) => (e.id === event.id ? updated : e)));
		setFilteredEvents((prev) =>
			prev.map((e) => (e.id === event.id ? updated : e)),
		);
	};

	const removeEvent = (eventId: string) => {
		setAllEvents((prev) => prev.filter((e) => e.id !== eventId));
		setFilteredEvents((prev) => prev.filter((e) => e.id !== eventId));
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

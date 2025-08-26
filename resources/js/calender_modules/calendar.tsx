import React from "react";
import { CalendarBody } from "@/calender_modules/calendar-body";
import { CalendarProvider } from "@/calender_modules/contexts/calendar-context";
import { DndProvider } from "@/calender_modules/contexts/dnd-context";
import { CalendarHeader } from "@/calender_modules/header/calendar-header";
import { getEvents, getUsers } from "@/calender_modules/requests";

async function getCalendarData() {
	return {
		events: await getEvents(),
		users: await getUsers(),
	};
}

export async function Calendar() {
	const { events, users } = await getCalendarData();

	return (
		<CalendarProvider events={events} users={users} view="month">
			<DndProvider showConfirmation={false}>
				<div className="w-full border rounded-xl">
					<CalendarHeader />
					<CalendarBody />
				</div>
			</DndProvider>
		</CalendarProvider>
	);
}

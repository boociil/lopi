"use client";

import { isSameDay, parseISO } from "date-fns";
import { motion } from "framer-motion";
import { fadeIn, transition } from "@/components/calender_modules/animations";
import { useCalendar } from "@/components/calender_modules/contexts/calendar-context";
import { AgendaEvents } from "@/components/calender_modules/views/agenda-view/agenda-events";
import { CalendarMonthView } from "@/components/calender_modules/views/month-view/calendar-month-view";
import { CalendarDayView } from "@/components/calender_modules/views/week-and-day-view/calendar-day-view";
import { CalendarWeekView } from "@/components/calender_modules/views/week-and-day-view/calendar-week-view";
import { CalendarYearView } from "@/components/calender_modules/views/year-view/calendar-year-view";

export function CalendarBody() {
	const { view, getSingleDaysEvent, getMultipleDaysEvent } = useCalendar();

	const singleDayEvents = getSingleDaysEvent();

	const multiDayEvents = getMultipleDaysEvent();

	return (
		<div className="w-full h-full overflow-scroll relative">
			<motion.div
				key={view}
				initial="initial"
				animate="animate"
				exit="exit"
				variants={fadeIn}
				transition={transition}
			>
				{view === "month" && (
					<CalendarMonthView
						singleDayEvents={singleDayEvents}
						multiDayEvents={multiDayEvents}
					/>
				)}
				{view === "week" && (
					<CalendarWeekView
						singleDayEvents={singleDayEvents}
						multiDayEvents={multiDayEvents}
					/>
				)}
				{view === "day" && (
					<CalendarDayView
						singleDayEvents={singleDayEvents}
						multiDayEvents={multiDayEvents}
					/>
				)}
				{view === "year" && (
					<CalendarYearView
						singleDayEvents={singleDayEvents}
						multiDayEvents={multiDayEvents}
					/>
				)}
				{view === "agenda" && (
					<motion.div
						key="agenda"
						initial="initial"
						animate="animate"
						exit="exit"
						variants={fadeIn}
						transition={transition}
					>
						<AgendaEvents />
					</motion.div>
				)}
			</motion.div>
		</div>
	);
}

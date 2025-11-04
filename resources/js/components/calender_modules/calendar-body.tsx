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
import { IEvent } from "./interfaces";
import { formatDate } from "./helpers";

export function CalendarBody() {
	const { view, events } = useCalendar();

	const singleDayEvents : IEvent[] = [];
    const multiDayEvents : IEvent[] = []
    const repeat_id_s : string[] = []

    if(view == 'week'){
         multiDayEvents.push(...events.filter((event) => {
            const startDate = parseISO(event.startDate);
            const endDate = parseISO(event.endDate);
            return !isSameDay(startDate, endDate);
        }));

        singleDayEvents.push(...events.filter((event) => {
            const startDate = parseISO(event.startDate);
            const endDate = parseISO(event.endDate);
            return isSameDay(startDate, endDate);
        }));

        multiDayEvents.forEach(element => {
            const currentDate = new Date(element._startDate);
            while (currentDate <= new Date(element._endDate)) {
                const item = {...element}
                item.startDate =  `${formatDate(currentDate)}T${element._startTime}`
                item.endDate =  `${formatDate(currentDate)}T${element._endTime}`
                // item._startDate =  formatDate(currentDate)
                // item._endDate =  formatDate(currentDate)
                // item.date =  formatDate(currentDate)
                singleDayEvents.push(item)

                currentDate.setDate(currentDate.getDate() + 1);
            }
        });
    }else{
        multiDayEvents.push(...events.filter((event) => {
            const startDate = parseISO(event.startDate);
            const endDate = parseISO(event.endDate);
            return !isSameDay(startDate, endDate);
        }));

        singleDayEvents.push(...events.filter((event) => {
            const startDate = parseISO(event.startDate);
            const endDate = parseISO(event.endDate);
            return isSameDay(startDate, endDate);
        }));
    }


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

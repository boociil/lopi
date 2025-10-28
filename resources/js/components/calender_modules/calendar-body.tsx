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

export function CalendarBody() {
	const { view, events } = useCalendar();

	const singleDayEvents = events;

    const multiDayEvents : IEvent[] = []
    const repeat_id_s : string[] = []
    events.forEach(element => {
        if(element.repeat_id != null){
            if(!repeat_id_s.includes(element.repeat_id)){
                multiDayEvents.push(element)
                repeat_id_s.push(element.repeat_id)
            }else{
                if(element.startDate < multiDayEvents[repeat_id_s.findIndex((item) => item == element.repeat_id)].startDate){
                    multiDayEvents[repeat_id_s.findIndex((item) => item == element.repeat_id)].startDate = element.startDate
                }
                if(element.endDate > multiDayEvents[repeat_id_s.findIndex((item) => item == element.repeat_id)].endDate){
                    multiDayEvents[repeat_id_s.findIndex((item) => item == element.repeat_id)].endDate = element.endDate
                }
            }
        }
    });

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

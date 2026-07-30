"use client";

import { motion } from "framer-motion";
import {
	CalendarRange,
	Columns,
	Grid2X2,
	Grid3X3,
	LayoutList,
	List,
	Plus,
} from "lucide-react";

import { Button } from "@/components/ui/button";
import {
	slideFromLeft,
	slideFromRight,
	transition,
} from "@/components/calender_modules/animations";
import { useCalendar } from "@/components/calender_modules/contexts/calendar-context";
import { DateNavigator } from "@/components/calender_modules/header/date-navigator";
import FilterEvents from "@/components/calender_modules/header/filter";
import { TodayButton } from "@/components/calender_modules/header/today-button";
import { UserSelect } from "@/components/calender_modules/header/user-select";
import Views from "./view-tabs";
import { capitalizeWords } from "../helpers";

export function CalendarHeader() {
	const { view, events, settingCalender, AddEditEventDialog, canAdd } = useCalendar();

	return (
		<div className="flex flex-col gap-4 border-b border-accent-foreground p-4 lg:flex-row lg:items-center lg:justify-between">
			<motion.div
				className="flex items-center gap-3 grow"
				variants={slideFromLeft}
				initial="initial"
				animate="animate"
				transition={transition}
			>
				<TodayButton />
				<DateNavigator view={view} events={events} />
			</motion.div>

			<motion.div
				className="flex flex-col gap-4 lg:flex-row lg:items-end lg:gap-1.5"
				variants={slideFromRight}
				initial="initial"
				animate="animate"
				transition={transition}
			>
				<div className="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-2 w-full">
                    {/* Bagian Kiri: FilterEvents & Views */}
                    <div className="flex flex-row items-center gap-1 md:gap-2">
                        {settingCalender.show_filter && (
                            <div>
                                <FilterEvents />
                            </div>
                        )}
                        <div className="grow">
                            <Views />
                        </div>
                    </div>

                    <div className="flex flex-row items-center gap-1 md:gap-2 justify-end lg:w-96">
                        {settingCalender.show_filter_user && (
                            <div className="grow min-w-0">
                                <UserSelect />
                            </div>
                        )}

                        {canAdd() && (
                            <div className="">
                                <AddEditEventDialog>
                                    <Button>
                                        <Plus className="h-4 w-4" />
                                        <span className="hidden md:block">
                                            Tambah {capitalizeWords(settingCalender.name_event)}
                                        </span>
                                    </Button>
                                </AddEditEventDialog>
                            </div>
                        )}
                    </div>
                </div>
				{/* <Settings /> */}
			</motion.div>
		</div>
	);
}

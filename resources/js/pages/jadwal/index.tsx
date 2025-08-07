import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

import { useState } from 'react'
import Calendar from '@/calender_component/calendar/calendar'
import { CalendarEvent, Mode } from '@/calender_component/calendar/calendar-types'
import { generateMockEvents } from '@/calender_component/lib/mock-calendar-events'

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Beranda',
        href: '/beranda',
    },
];

export default function Dashboard() {
    const [events, setEvents] = useState<CalendarEvent[]>(generateMockEvents())
    const [mode, setMode] = useState<Mode>('month')
    const [date, setDate] = useState<Date>(new Date())

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <Calendar
                      events={events}
                      setEvents={setEvents}
                      mode={mode}
                      setMode={setMode}
                      date={date}
                      setDate={setDate}
                    />
            </div>
        </AppLayout>
    );
}

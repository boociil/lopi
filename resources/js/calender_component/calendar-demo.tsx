import { useState } from 'react'
import Calendar from '@/calender_component/calendar/calendar'
import { CalendarEvent, Mode } from '@/calender_component/calendar/calendar-types'
import { generateMockEvents } from '@/calender_component/lib/mock-calendar-events'

export default function CalendarDemo() {
  const [events, setEvents] = useState<CalendarEvent[]>(generateMockEvents())
  const [mode, setMode] = useState<Mode>('month')
  const [date, setDate] = useState<Date>(new Date())

  return (
    <Calendar
      events={events}
      setEvents={setEvents}
      mode={mode}
      setMode={setMode}
      date={date}
      setDate={setDate}
    />
  )
}

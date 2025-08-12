import { createContext, useContext } from 'react'
import type { ZoomContextType } from './types'

export const ZoomContext = createContext<ZoomContextType | undefined>(
  undefined
)

export function useZoomContext() {
  const context = useContext(ZoomContext)
  if (!context) {
    throw new Error('useCalendarContext must be used within a CalendarProvider')
  }
  return context
}

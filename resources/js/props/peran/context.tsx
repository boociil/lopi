import { createContext, useContext } from 'react'
import type { PeranContextType } from './types'

export const PeranContext = createContext<PeranContextType | undefined>(
  undefined
)

export function usePeranContext() {
  const context = useContext(PeranContext)
  if (!context) {
    throw new Error('useCalendarContext must be used within a CalendarProvider')
  }
  return context
}

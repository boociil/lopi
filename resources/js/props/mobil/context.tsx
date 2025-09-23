import { createContext, useContext } from 'react'
import type { PinjamMobilContextType } from './types'

export const PinjamMobilContext = createContext<PinjamMobilContextType | undefined>(
  undefined
)

export function usePinjamContext() {
  const context = useContext(PinjamMobilContext)
  if (!context) {
    throw new Error('useCalendarContext must be used within a CalendarProvider')
  }
  return context
}

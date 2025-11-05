import { createContext, useContext } from 'react'
import type { AppreciateDataContextType } from '../types'

export const AppreciateDataContext = createContext<AppreciateDataContextType | undefined>(
  undefined
)

export function useAppreciateDataContext() {
  const context = useContext(AppreciateDataContext)
  if (!context) {
    throw new Error('useAppreciateDataContext must be used within a CalendarProvider')
  }
  return context
}

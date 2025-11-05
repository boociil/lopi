import { createContext, useContext } from 'react'
import type { AppreciateMaterContextType } from '../types'

export const AppreciateMaterContext = createContext<AppreciateMaterContextType | undefined>(
  undefined
)

export function useAppreciateMaterContext() {
  const context = useContext(AppreciateMaterContext)
  if (!context) {
    throw new Error('useAppreciateMaterContext must be used within a CalendarProvider')
  }
  return context
}

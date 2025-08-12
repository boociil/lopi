import { ZoomContext } from './context'
import { zoom } from './types'
import { useState } from 'react'
import DialogFormAdd from './dialog/form-add'

export default function ZommProvider({
  zoom,
  setZoom,
  children,
  newEventDialogOpen,
  setNewEventDialogOpen,
  manageEventDialogOpen,
  setManageEventDialogOpen,
}: {
    zoom: zoom[]
    setZoom: (zoom: zoom[]) => void,
    newEventDialogOpen: boolean,
    setNewEventDialogOpen: (newEventDialogOpen: boolean) => void,
    manageEventDialogOpen: boolean,
    setManageEventDialogOpen: (manageEventDialogOpen: boolean) => void
    children: React.ReactNode
}) {

  return (
    <ZoomContext.Provider
      value={{
        zoom,
        setZoom,
        newEventDialogOpen,
        setNewEventDialogOpen,
        manageEventDialogOpen,
        setManageEventDialogOpen,
      }}
    >
      <DialogFormAdd />
      {children}
    </ZoomContext.Provider>
  )
}

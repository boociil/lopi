import { ZoomContext } from './context'
import { zoom } from './types'
import { useState } from 'react'
import DialogFormAdd from './dialog/form-add'
import DialogFormEdit from './dialog/form-edit'
import DialogFormDelete from './dialog/form-delete'
import DialogFormApprove from './dialog/form-approve'

export default function ZommProvider({
  zoom,
  setZoom,
  children,
  newEventDialogOpen,
  setNewEventDialogOpen,
  editEventDialogOpen,
  setEditEventDialogOpen,
  seletedZoom,
  setSeletedZoom,
}: {
    zoom: zoom[]
    setZoom: (zoom: zoom[]) => void,
    newEventDialogOpen: boolean,
    setNewEventDialogOpen: (newEventDialogOpen: boolean) => void,
    editEventDialogOpen: boolean,
    setEditEventDialogOpen: (editEventDialogOpen: boolean) => void,
    seletedZoom: zoom | null
    setSeletedZoom: (seletedZoom: zoom) => void
    children: React.ReactNode
}) {

  return (
    <ZoomContext.Provider
      value={{
        zoom,
        setZoom,
        newEventDialogOpen,
        setNewEventDialogOpen,
        editEventDialogOpen,
        setEditEventDialogOpen,
        seletedZoom,
        setSeletedZoom
      }}
    >
        <DialogFormAdd></DialogFormAdd>
        <DialogFormEdit></DialogFormEdit>
        <DialogFormDelete></DialogFormDelete>
        <DialogFormApprove></DialogFormApprove>
      {children}
    </ZoomContext.Provider>
  )
}

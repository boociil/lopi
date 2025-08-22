import { ZoomContext } from './context'
import { type zoom, type zoom_dialog_event } from './types'
import DialogFormManage from './dialog/form-manage'

export default function ZommProvider({
  zoom,
  setZoom,
  eventDialog,
  setEventDialog,
  haveZoomRole,
  setHaveZoomRole,
  children
}: {
    zoom: zoom[]
    setZoom: (zoom: zoom[]) => void,
    eventDialog: zoom_dialog_event,
    setEventDialog: (eventDialog: zoom_dialog_event) => void,
    haveZoomRole: boolean
    setHaveZoomRole: (haveZoomRole: boolean) => void
    children: React.ReactNode
}) {

  return (
    <ZoomContext.Provider
      value={{
        zoom,
        setZoom,
        eventDialog,
        setEventDialog,
        haveZoomRole,
        setHaveZoomRole
      }}
    >
        <DialogFormManage></DialogFormManage>
      {children}
    </ZoomContext.Provider>
  )
}

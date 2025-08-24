import { ZoomContext } from './context'
import { type zoom, type zoom_dialog_event } from './types'
import DialogFormManage from './dialog/form-manage'

export default function ZommProvider({
  zoom,
  haveZoomRole,
  eventDialog,
  setEventDialog,
  children
}: {
    zoom: zoom[]
    haveZoomRole: boolean
    eventDialog: zoom_dialog_event,
    setEventDialog: (eventDialog: zoom_dialog_event) => void,
    children: React.ReactNode
}) {

  return (
    <ZoomContext.Provider
      value={{
        zoom,
        haveZoomRole,
        eventDialog,
        setEventDialog,
      }}
    >
        <DialogFormManage></DialogFormManage>
      {children}
    </ZoomContext.Provider>
  )
}

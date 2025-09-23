import { PinjamMobilContext } from './context'
import { type pinjam_mobil, type pinjam_mobil_dialog_event } from './types'
import DialogFormManage from './dialog/form-manage'
import { User } from '@/types'

export default function PinjamMobilProvider({
  pinjam_mobil,
  user,
  havePinjamMovilRole,
  eventDialog,
  setEventDialog,
  children
}: {
    pinjam_mobil: pinjam_mobil[]
    user: User[]
    havePinjamMovilRole: boolean
    eventDialog: pinjam_mobil_dialog_event,
    setEventDialog: (eventDialog: pinjam_mobil_dialog_event) => void,
    children: React.ReactNode
}) {

  return (
    <PinjamMobilContext.Provider
      value={{
        pinjam_mobil,
        user,
        havePinjamMovilRole,
        eventDialog,
        setEventDialog,
      }}
    >
        <DialogFormManage></DialogFormManage>
      {children}
    </PinjamMobilContext.Provider>
  )
}

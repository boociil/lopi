import { PeranContext } from "./context";
import {
    type satuan_kerja_tahun,
    type peran_dialog_event,
    type peran_lopi,
} from "./types";
import DialogFormManage from "./dialog/form-manage";
import {type User } from "@/types";

export default function PeranProvider({
    peranZoom,
    setPeranZoom,
    user,
    setUser,
    satuanKerjaTahun,
    setSatuanKerjaTahun,
    eventDialog,
    setEventDialog,
    userDict,
    setUserDict,
    satuanKerjaTahunDict,
    setSatuanKerjaTahunDict,
    children,
}: {
    peranZoom: peran_lopi[];
    setPeranZoom: (peranZoom: peran_lopi[]) => void;
    user: User[];
    setUser: (user: User[]) => void;
    satuanKerjaTahun: satuan_kerja_tahun[];
    setSatuanKerjaTahun: (satuanKerjaTahun: satuan_kerja_tahun[]) => void;
    eventDialog: peran_dialog_event;
    setEventDialog: (eventDialog: peran_dialog_event) => void;
    userDict: Map<string, string>,
    setUserDict: (userDict: Map<string, string>) => void;
    satuanKerjaTahunDict: Map<string, string>,
    setSatuanKerjaTahunDict: (satuanKerjaTahunDict: Map<string, string>) => void;
    children: React.ReactNode;
}) {
    return (
        <PeranContext.Provider
            value={{
                peranZoom,
                setPeranZoom,
                user,
                setUser,
                satuanKerjaTahun,
                setSatuanKerjaTahun,
                eventDialog,
                setEventDialog,
                userDict,
                setUserDict,
                satuanKerjaTahunDict,
                setSatuanKerjaTahunDict,
            }}
        >
            <DialogFormManage></DialogFormManage>
            {children}
        </PeranContext.Provider>
    );
}

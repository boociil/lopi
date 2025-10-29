import { PeranContext } from "./context";
import {
    type satuan_kerja_tahun,
    type peran_dialog_event,
    type peran_lopi,
} from "./types";
import DialogFormManage from "./dialog/form-manage";
import {type User } from "@/types";
import { useApi } from "@/lib/API/useApi";

export default function PeranProvider({
    peranLopi,
    user,
    satuanKerjaTahun,
    userDict,
    satuanKerjaTahunDict,
    eventDialog,
    setEventDialog,
    children,
    addPeran,

}: {
    peranLopi: peran_lopi[];
    user: User[];
    satuanKerjaTahun: satuan_kerja_tahun[];
    userDict: Map<string, string>,
    satuanKerjaTahunDict: Map<string, string>,
    eventDialog: peran_dialog_event;
    setEventDialog: (eventDialog: peran_dialog_event) => void;
    addPeran: (peran_lopi_: peran_lopi) => void;
    children: React.ReactNode;
}) {

    const axiosClient = useApi();

    return (
        <PeranContext.Provider
            value={{
                peranLopi,
                user,
                satuanKerjaTahun,
                userDict,
                satuanKerjaTahunDict,
                eventDialog,
                addPeran,
                setEventDialog,
                axiosClient,
            }}
        >
            <DialogFormManage></DialogFormManage>
            {children}
        </PeranContext.Provider>
    );
}

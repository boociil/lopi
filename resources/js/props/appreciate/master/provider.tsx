import { AppreciateMaterContext } from "./context";
import {
    type appreciate_master,
    type appreciate_master_dialog_event,
} from "../types";
import DialogFormManage from "./dialog/form-manage";
import {type User } from "@/types";
import { useApi } from "@/lib/API/useApi";

export default function AppreciateMaterProvider({
    appreciateMaster,
    user,
    userDict,
    eventDialog,
    setEventDialog,
    children,
    addAppreciateMaster,
    updateAppreciateMaster,
}: {
    appreciateMaster: appreciate_master[];
    user: User[];
    userDict: Map<string, string>,
    eventDialog: appreciate_master_dialog_event;
    setEventDialog: (eventDialog: appreciate_master_dialog_event) => void;
    addAppreciateMaster: (appreciate_master_: appreciate_master) => void;
    updateAppreciateMaster: (appreciate_master_: appreciate_master) => void;
    children: React.ReactNode;
}) {

    const axiosClient = useApi();

    return (
        <AppreciateMaterContext.Provider
            value={{
                appreciateMaster,
                user,
                userDict,
                eventDialog,
                setEventDialog,
                axiosClient,
                addAppreciateMaster,
                updateAppreciateMaster,
            }}
        >
            <DialogFormManage></DialogFormManage>
            {children}
        </AppreciateMaterContext.Provider>
    );
}

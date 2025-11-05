import { AppreciateDataContext } from "./context";
import {
    type appreciate_data_dialog_event,
    type appreciate_data,
    type appreciate_master_dialog_event,
    TargetData,
} from "../types";
import DialogFormManage from "./dialog/form-manage";
import {type User } from "@/types";
import { useApi } from "@/lib/API/useApi";

export default function AppreciateDataProvider({
    user,
    userDict,
    eventDialog,
    setEventDialog,
    children,
    ubahData,
}: {
    user: User[];
    userDict: Map<string, string>,
    eventDialog: appreciate_data_dialog_event;
    setEventDialog: (eventDialog: appreciate_data_dialog_event) => void;
    ubahData: (target: TargetData) => void;
    children: React.ReactNode;
}) {

    const axiosClient = useApi();

    return (
        <AppreciateDataContext.Provider
            value={{
                user,
                userDict,
                eventDialog,
                setEventDialog,
                axiosClient,
                ubahData
            }}
        >
            <DialogFormManage></DialogFormManage>
            {children}
        </AppreciateDataContext.Provider>
    );
}

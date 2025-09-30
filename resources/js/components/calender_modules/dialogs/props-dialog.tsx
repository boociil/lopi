import { ReactNode } from "react";
import { IEvent } from "../interfaces";
import { PinjamMobilIEvent } from "@/props/mobil/types";

export interface Delete_Event_Dialog_IProps {
	eventId: string;
}

export interface Event_Details_Dialog_IProps {
	event: IEvent;
	children: ReactNode;
}

export interface Event_Drop_Confirmation_Dialog_IProps {
	open: boolean;
    onOpenChange: (open: boolean) => void;
    event: IEvent | null;
    newStartDate: Date | null;
    newEndDate: Date | null;
    onConfirm: () => void;
    onCancel: () => void;
}

export interface Event_List_Dialog_IProps {
    date: Date;
    events: IEvent[];
    maxVisibleEvents?: number;
    children?: ReactNode;
}

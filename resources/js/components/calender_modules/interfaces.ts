import type { TCalendarView, TEventColor } from "@/components/calender_modules/types";
import { Satuan_Kerja_Tahun, Tim, User } from "@/types";

export interface IUser {
	id: string; // username , id
    id_ori: string;
	name: string;
    type: "user" | "tim" | "satuan_kerja_tahun";
	picturePath: string | null;
    ori: any;
}

export interface IEvent {
	id: string;
	startDate: string;
	endDate: string;
    date: string;
    _startDate: string;
	_endDate: string;
    _startTime: string;
	_endTime: string;
	title: string;
	color: TEventColor;
	description: string;
	user: IUser[];
    oriKey: string | null;
}

export interface ICalendarCell {
	day: number;
	currentMonth: boolean;
	date: Date;
}

export interface SettingCalender {
    showTime: boolean;
	name_event: string;
	calender_show: string[];
	show_filter: boolean;
    filter_name: [TEventColor, string][];
    show_filter_user: boolean;
}

export function produceCalenderUser(user_: User[], satuan_kerja_tahun_: Satuan_Kerja_Tahun[], tim_: Tim[]): IUser[]{
    let retur_data: IUser[] = []
    satuan_kerja_tahun_.forEach(element => {
        retur_data.push({
            id: "satuan_kerja_tahun-+-"+element.id,
            id_ori: element.id,
            name: element.nama,
            type: "satuan_kerja_tahun",
            ori: element,
            picturePath: null
        })
    });

    tim_.forEach(element => {
        retur_data.push({
            id: "tim-+-"+element.id,
            id_ori: element.id,
            name: element.nama,
            type: "tim",
            ori: element,
            picturePath: null
        })
    });
    user_.forEach(element => {
        retur_data.push({
            id: "user-+-"+element.username,
            id_ori: element.username,
            name: element.nama,
            type: "user",
            ori: element,
            picturePath: '/lopi/proxy_image/'+element.username
        })
    });

    return retur_data
}

export interface CalendarSettings {
	badgeVariant: "dot" | "colored";
	view: TCalendarView;
	use24HourFormat: boolean;
	agendaModeGroupBy: "date" | "color";
}

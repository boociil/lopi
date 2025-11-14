import type { TCalendarView, TEventColor } from "@/components/calender_modules/types";
import { User } from "@/types";

export interface IUser {
	id: number;
	name: string;
	picturePath: string | null;
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
	user: IUser | null;
}

export interface ICalendarCell {
	day: number;
	currentMonth: boolean;
	date: Date;
}

export interface SettingCalender {
	name_event: string;
	calender_show: string[];
	show_filter: boolean;
    filter_name: [TEventColor, string][];
    show_filter_user: boolean;
}

export interface CalenderUser extends IUser, User{
}

export function produceCalenderUser(user_: User[]): CalenderUser[]{
    let retur_data: CalenderUser[] = []
    user_.forEach(element => {
        retur_data.push({
            id: element.id,
            username: element.username,
            email: element.email,
            nip_baru: element.nip_baru,
            nip_lama: element.nip_lama,
            nama: element.nama,
            jabatan: element.jabatan,
            golonagan: element.golonagan,
            name: element.nama,
            picturePath: 'https://simpeg.bps.go.id/apis/pegawai/avatar/'+element.nip_lama,
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

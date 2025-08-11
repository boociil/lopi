export function date_format (tanggal: string, penyesuian = 0) : string{
    if(tanggal == null || tanggal == ''){
        return '-'
    }
    let tanggal_date = new Date(tanggal)

    if(penyesuian != 0){
        tanggal_date = addHoursToDate(tanggal_date, penyesuian)
    }

    let month = '' + (tanggal_date.getMonth() + 1),
        day = '' + tanggal_date.getDate(),
        year =  '' + tanggal_date.getFullYear();

    if (month.length < 2)
        month = '0' + month;
    if (day.length < 2)
        day = '0' + day;

    // if(reverse){
    //     return [year, month, day].join('-');
    // }else{
    //     return [day, month, year].join('-');
    // }

    switch(parseInt(month)) {
        case 1:
            return parseInt(day)+' Januari '+parseInt(year);
        case 2:
            return parseInt(day)+' Februari '+parseInt(year);
        case 3:
            return parseInt(day)+' Maret '+parseInt(year);
        case 4:
            return parseInt(day)+' April '+parseInt(year);
        case 5:
            return parseInt(day)+' Mei '+parseInt(year);
        case 6:
            return parseInt(day)+' Juni '+parseInt(year);
        case 7:
            return parseInt(day)+' Juli '+parseInt(year);
        case 8:
            return parseInt(day)+' Agustus '+parseInt(year);
        case 9:
            return parseInt(day)+' September '+parseInt(year);
        case 10:
            return parseInt(day)+' Oktober '+parseInt(year);
        case 11:
            return parseInt(day)+' November '+parseInt(year);
        case 12:
            return parseInt(day)+' Desember '+parseInt(year);
        default:
            return [day, month, year].join('-');
    }

}

export function addHoursToDate(date: Date, hours: number): Date {
  return new Date(new Date(date).setHours(date.getHours() + hours));
}


export function get_time_from_date_time (tanggal: string, penyesuian = 0) : string{
    if(tanggal == null || tanggal == ''){
        return '-'
    }
    let tanggal_date = new Date(tanggal)

    if(penyesuian != 0){
        tanggal_date = addHoursToDate(tanggal_date, penyesuian)
    }

    let hour = '' + tanggal_date.getHours()
    let minute = '' + tanggal_date.getMinutes()

    if (hour.length < 2)
        hour = '0' + hour;
    if (minute.length < 2)
        minute = '0' + minute;
    return hour+':'+minute;

}

export function time_format (time: string) {
    let arr_time = time.split(":");
    if(arr_time.length <= 2){
        return time
    }else{
        return arr_time[0]+':'+arr_time[1]
    }
}



import AppLayout from '@/layouts/app-layout';
import { type Peran, type BreadcrumbItem, type zoom } from '@/types';
import { Head } from '@inertiajs/react';

import * as React from "react"
import { ChevronDown } from "lucide-react"
import { Button } from "@/components/ui/button"
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { Input } from "@/components/ui/input"
import {
    Table,
    TableBody,
    TableCell,
    TableCellWrap,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table"
import HeadingSmall from '@/components/heading-small';
import { date_format, get_time_from_date_time } from '@/lib/date';
import { Badge } from '@/components/ui/badge';

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog"

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Zoom',
        href: '/zoom',
    },
];


export default function Index({peran,  zoom_bulan_daftar, zoom_statsulbar, zoom_sp2020}) {

    const data: zoom[] = []
    zoom_bulan_daftar.forEach(element => {
        const item : zoom =  {
            topic: element.topic,
            start_time: element.start_time,
            start_time_penyesuaian: 0,
            pembuat_username: element.pembuat_username,
            zoom_status: element.zoom_status,
            zoom_akun: element.zoom_akun ? element.zoom_akun : '-',
            zoom_meeting_id: element.zoom_meeting_id,
            zoom_start_url: element.zoom_start_url,
            zoom_join_url: element.zoom_join_url,
            zoom_app_prop: null,
            zoom_int_prop: element,
        }
        data.push(item)
    });

    if( zoom_statsulbar.status){
        zoom_statsulbar.data.meetings.forEach(element => {
            let dapat = false;
            for(let i=0; i< data.length; i++){
                if(data[i].zoom_meeting_id == element.id){
                    dapat = true
                    data[i].zoom_app_prop = element
                }
            }
            if(!dapat){
                const item : zoom =  {
                    topic: element.topic,
                    start_time: element.start_time,
                    start_time_penyesuaian: 8,
                    pembuat_username: null,
                    zoom_status: null,
                    zoom_akun: 'statsulbar',
                    zoom_meeting_id: element.id,
                    zoom_start_url: element.zoom_start_url,
                    zoom_join_url: element.zoom_join_url,
                    zoom_app_prop: element,
                    zoom_int_prop: null,
                }
                data.push(item)
            }
        });
    }

    if( zoom_sp2020.status){
        zoom_sp2020.data.meetings.forEach(element => {
            let dapat = false;
            for(let i=0; i< data.length; i++){
                if(data[i].zoom_meeting_id == element.id){
                    dapat = true
                    data[i].zoom_app_prop = element
                }
            }
            if(!dapat){
                const item : zoom =  {
                    topic: element.topic,
                    start_time: element.start_time,
                    start_time_penyesuaian: 8,
                    pembuat_username: null,
                    zoom_status: null,
                    zoom_akun: 'sp2020',
                    zoom_meeting_id: element.id,
                    zoom_start_url: element.zoom_start_url,
                    zoom_join_url: element.zoom_join_url,
                    zoom_app_prop: element,
                    zoom_int_prop: null,
                }
                data.push(item)
            }
        });
    }

    data.sort((a, b) => {
        if(a.start_time < b.start_time){
            return -1
        }else if(a.start_time > b.start_time){
            return 1
        }
        return 0
    })

    const [filteredData, setFilterData] = React.useState(data);
    const [filterTopic, setFilterTopic] = React.useState("");
    React.useEffect(() => {
        let _filterData = data.filter(item => {
            if(item.topic.toLowerCase().includes(filterTopic.toLowerCase())){
                return true
            }else{
                return false
            }
        })
        setFilterData(_filterData)
    }, [filterTopic]);


    return (
        <AppLayout breadcrumbs={breadcrumbs} peran={peran}>
            <Head title="Zoom" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="w-full">
                    <HeadingSmall title="Pengaturan Tampilam" description="Perbarui pengaturan tampilan akun Anda" />
                    <div className="flex items-center py-4">
                        <Input
                            placeholder="Filter topic..."
                            onChange={(event) =>
                                setFilterTopic(event.target.value)
                            }
                            className="max-w-sm"
                        />
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline" className="ml-auto">
                                Bulan <ChevronDown />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">

                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                    <div className="overflow-hidden rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Topic dan Waktu</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Akun</TableHead>
                                    <TableHead>Pembuat</TableHead>
                                    <TableHead>Approval</TableHead>
                                    <TableHead>Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {filteredData.map((item) => {

                                    let status = (<Badge variant="outline">Belum</Badge>)
                                    if(item.zoom_app_prop != null && item.zoom_int_prop != null){
                                        if(item.zoom_status == 0){
                                            status = (<Badge variant="outline">Belum</Badge>)
                                        }else if(item.zoom_status == 1){
                                            status = (<Badge variant="destructive">Ditolak</Badge>)
                                        }else if(item.zoom_status == 2){
                                            status = (<Badge variant="default">Diterima</Badge>)
                                        }
                                    }else if(item.zoom_app_prop != null && item.zoom_int_prop == null){
                                        status = (<Badge variant="outline">Belum</Badge>)
                                    }else if(item.zoom_app_prop == null && item.zoom_int_prop != null){
                                        status = (<Badge variant="outline">Langsung Di App</Badge>)
                                    }

                                    let pembuat_zoom = ""
                                    if(item.zoom_int_prop != null){
                                        pembuat_zoom = item.zoom_int_prop.pembuat.nama
                                    }else{
                                        pembuat_zoom = '-'
                                    }

                                    let approval_button = (<div></div>)
                                    if(item.zoom_app_prop == null){
                                        let approval_button = (<Button>Approval</Button>)
                                    }

                                    return (
                                        <TableRow>
                                            <TableCellWrap className="text-wrap whitespace-wrap w-80">
                                                {item.topic}<br></br>
                                                {date_format(item.start_time, item.start_time_penyesuaian)} - {get_time_from_date_time(item.start_time, item.start_time_penyesuaian)}
                                            </TableCellWrap>
                                            <TableCell>{status}</TableCell>
                                            <TableCell>{item.zoom_akun}</TableCell>
                                            <TableCell>{pembuat_zoom}</TableCell>
                                            <TableCell>Approval</TableCell>
                                            <TableCell>Aksi</TableCell>
                                        </TableRow>
                                    )
                                })}
                            </TableBody>
                        </Table>
                    </div>
                    </div>
            </div>
        </AppLayout>
    );
}

export function Modal_Approve(props : boolean) {
    const { open, onClose } = props;
    if (!open) {
        return <></>;
    }
    return (
        <Dialog>
  <DialogTrigger>Open</DialogTrigger>
  <DialogContent>
    <DialogHeader>
      <DialogTitle>Are you absolutely sure?</DialogTitle>
      <DialogDescription>
        This action cannot be undone. This will permanently delete your account
        and remove your data from our servers.
      </DialogDescription>
    </DialogHeader>
  </DialogContent>
</Dialog>
    );
}

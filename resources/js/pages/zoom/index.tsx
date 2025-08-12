import AppLayout from '@/layouts/app-layout';
import { type Peran, type BreadcrumbItem, type SharedData } from '@/types';
import { type zoom, type zoom_app, zoom_app_with_status } from '@/props/zoom/types'
import { Head, useForm, usePage } from '@inertiajs/react';

import * as React from "react"
import { ChevronDown, Plus } from "lucide-react"
import { Button } from "@/components/ui/button"
import { Link } from '@inertiajs/react';
import {
    DropdownMenu,
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
import { addHoursToDate, addMinutesToDate, date_format, get_time_from_date_time } from '@/lib/date';
import { Badge } from '@/components/ui/badge';
import ZommProvider from '@/props/zoom/provider';
import DialogFormAdd from '@/props/zoom/dialog/form-add';
import DialogFormEdit from '@/props/zoom/dialog/form-edit';
import DialogFormDelete from '@/props/zoom/dialog/form-delete';
import DialogFormApprove from '@/props/zoom/dialog/form-approve';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Zoom',
        href: '/zoom',
    },
];

export default function Index({bulan, peran,  zoom_bulan_daftar, zoom_statsulbar, zoom_sp2020}: React.PropsWithChildren<{bulan: number,  peran?: Peran[], zoom_bulan_daftar: zoom[], zoom_statsulbar: zoom_app_with_status, zoom_sp2020: zoom_app_with_status}>) {

    const data: zoom[] = []
    zoom_bulan_daftar.forEach(element => {
        const item : zoom =  {
            topic: element.topic,
            password: element.password,
            start_time: new Date(element.start_time).toISOString(),
            duration: element.duration,
            end_time: addMinutesToDate(new Date(element.start_time), element.duration).toISOString(),
            pembuat_username: element.pembuat_username,
            pembuat_nama: element.pembuat_nama,
            zoom_status: element.zoom_status,
            zoom_akun: element.zoom_akun ? element.zoom_akun : '-',
            zoom_meeting_id: element.zoom_meeting_id,
            zoom_start_url: element.zoom_start_url,
            zoom_join_url: element.zoom_join_url,
            zoom_app_prop: null,
            id: element.id,
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
                    password: null,
                    start_time: (new Date(element.start_time)).toISOString(),
                    duration: element.duration,
                    end_time: addMinutesToDate(new Date(element.start_time), element.duration).toISOString(),
                    pembuat_username: null,
                    pembuat_nama: null,
                    zoom_status: null,
                    zoom_akun: 'statsulbar',
                    zoom_meeting_id: element.id,
                    zoom_start_url: null,
                    zoom_join_url: element.zoom_join_url,
                    zoom_app_prop: element,
                    id: null,
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
                    password: null,
                    start_time: (new Date(element.start_time)).toISOString(),
                    duration: element.duration,
                    end_time: addMinutesToDate(new Date(element.start_time), element.duration).toISOString(),
                    pembuat_username: null,
                    pembuat_nama: null,
                    zoom_status: null,
                    zoom_akun: 'sp2020',
                    zoom_meeting_id: element.id,
                    zoom_start_url: null,
                    zoom_join_url: element.zoom_join_url,
                    zoom_app_prop: element,
                    id: null,
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

    const [zoom, setZoom] = React.useState(data);
    const [filterTopic, setFilterTopic] = React.useState("");
    const [filteredData, setFilterData] = React.useState(data);

    const [newEventDialogOpen, setNewEventDialogOpen] = React.useState(false)
    const [editEventDialogOpen, setEditEventDialogOpen] = React.useState(false)
    const [seletedZoom, setSeletedZoom] = React.useState<zoom | null>(null)

    const tahun_active = usePage<SharedData>().props.tahun_active;
    const nama_bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    let nama_bulan_pilihan = []
    let nama_bulan_sekarang = '';
    for(let i=1; i<=12; i++){
        nama_bulan_pilihan.push({'index': i, 'nama': nama_bulan[i-1]+' '+tahun_active})
        if(bulan == i){
            nama_bulan_sekarang = nama_bulan[i-1]+' '+tahun_active
        }
    }


    React.useEffect(() => {
        let _filterData = data.filter(item => {
            if(item.topic.toLowerCase().includes(filterTopic.toLowerCase())){
                return true
            }else{
                return false
            }
        })
        setFilterData(_filterData)
    }, [filterTopic, newEventDialogOpen]);

    return (
        <AppLayout breadcrumbs={breadcrumbs} peran={peran}>
            <Head title="Zoom" />
            <ZommProvider
                zoom={zoom}
                setZoom={setZoom}
                newEventDialogOpen={newEventDialogOpen}
                setNewEventDialogOpen={setNewEventDialogOpen}
                editEventDialogOpen={editEventDialogOpen}
                setEditEventDialogOpen={setEditEventDialogOpen}
                seletedZoom={seletedZoom}
                setSeletedZoom={setSeletedZoom}>

                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                    <div className="w-full">
                        <HeadingSmall title="Pengaturan Tampilam" description="Perbarui pengaturan tampilan akun Anda" />
                        <div className="flex items-center py-4 mt-1 gap-x-1.5">
                            <Input
                                placeholder="Filter topic..."
                                onChange={(event) =>
                                    setFilterTopic(event.target.value)
                                }
                                className="max-w-sm text-secondary"
                            />
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="outline" className="ml-auto">
                                    {nama_bulan_sekarang} <ChevronDown />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    {nama_bulan_pilihan.map((item) => (
                                        <DropdownMenuItem asChild>
                                            <Link className="block w-full" href={route('zoom-index')} data={{'bulan':item.index}}>
                                                {item.nama}
                                            </Link>
                                        </DropdownMenuItem>
                                    ))}

                                </DropdownMenuContent>
                            </DropdownMenu>
                            <Button
                                className="flex items-center gap-1 bg-primary text-primary-foreground"
                                    onClick={() => setNewEventDialogOpen(true)}
                                >
                                <Plus />
                                Add Event
                            </Button>
                        </div>
                        <div className="overflow-hidden rounded-md border">
                            <Table>
                                <TableHeader className="bg-accent">
                                    <TableRow>
                                        <TableHead>Topic dan Waktu</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Akun</TableHead>
                                        <TableHead>Pembuat</TableHead>
                                        <TableHead>Approval</TableHead>
                                        <TableHead>Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody className='text-accent-foreground'>
                                    {filteredData.map((item) => {

                                        let status = (<Badge variant="outline">Belum</Badge>)
                                        if(item.id != null){
                                            if(item.zoom_status == 0){
                                                status = (<Badge variant="outline">Belum</Badge>)
                                            }else if(item.zoom_status == 1){
                                                status = (<Badge variant="destructive">Ditolak</Badge>)
                                            }else if(item.zoom_status == 2){
                                                status = (<Badge variant="default">Diterima</Badge>)
                                            }
                                        }else if(item.zoom_app_prop != null && item.id == null){
                                            status = (<Badge variant="outline">Langsung Di App</Badge>)
                                        }

                                        let pembuat_zoom = ""
                                        if(item.pembuat_nama != null){
                                            pembuat_zoom = item.pembuat_nama
                                        }else{
                                            pembuat_zoom = '-'
                                        }

                                        let approval_button = (<div></div>)
                                        if(item.zoom_app_prop == null && item.zoom_status == 0){
                                            approval_button = (
                                                <Button className='bg-secondary text-secondary-foreground hover:bg-secondary/80' size='sm'
                                                 onClick={(event) => {
                                                    setSeletedZoom(item);
                                                    setEditEventDialogOpen(true);
                                                }}>Approval
                                                </Button>
                                            )
                                        }

                                        return (
                                            <TableRow key={"-"+item.id+"-"+item.zoom_meeting_id}>
                                                <TableCellWrap className="text-wrap whitespace-wrap w-80">
                                                    {item.topic}<br/>
                                                    {date_format(item.start_time)}<br/>
                                                    {get_time_from_date_time(item.start_time)} - {get_time_from_date_time(item.end_time)}
                                                </TableCellWrap>
                                                <TableCell>{status}</TableCell>
                                                <TableCell>{item.zoom_akun}</TableCell>
                                                <TableCell>{pembuat_zoom}</TableCell>
                                                <TableCell>{approval_button}</TableCell>
                                                <TableCell>Aksi</TableCell>
                                            </TableRow>
                                        )
                                    })}
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                </div>
            </ZommProvider>

        </AppLayout>
    );
}



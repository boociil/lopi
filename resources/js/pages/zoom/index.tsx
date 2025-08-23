import AppLayout from "@/layouts/app-layout";
import { Auth, type BreadcrumbItem, type SharedData } from "@/types";
import {
    type zoom,
    type zoom_app,
    zoom_app_with_status,
    zoom_dialog_event,
} from "@/props/zoom/types";
import { Head, usePage } from "@inertiajs/react";

import * as React from "react";
import { ChevronDown, MoreHorizontal, Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Link } from "@inertiajs/react";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Input } from "@/components/ui/input";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
    Table,
    TableBody,
    TableCell,
    TableCellWrap,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import {
    addMinutesToDate,
    date_format,
    get_time_from_date_time,
} from "@/lib/date";
import { Badge } from "@/components/ui/badge";
import ZommProvider from "@/props/zoom/provider";
import Heading from "@/components/heading";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Zoom",
        href: "/zoom",
    },
];

export default function Index({
    bulan,
    zoom_bulan_daftar,
    zoom_statsulbar,
    zoom_sp2020,
}: React.PropsWithChildren<{
    bulan: number;
    zoom_bulan_daftar: zoom[];
    zoom_statsulbar: zoom_app_with_status;
    zoom_sp2020: zoom_app_with_status;
}>) {
    const data: zoom[] = [];
    zoom_bulan_daftar.forEach((element) => {
        const item: zoom = {
            topic: element.topic,
            password: element.password,
            start_time: new Date(element.start_time).toISOString(),
            duration: element.duration,
            end_time: addMinutesToDate(
                new Date(element.start_time),
                element.duration,
            ).toISOString(),
            pembuat_username: element.pembuat_username,
            pembuat_nama: element.pembuat_nama,
            zoom_status: element.zoom_status,
            zoom_akun: element.zoom_akun ? element.zoom_akun : "-",
            zoom_meeting_id: element.zoom_meeting_id,
            zoom_start_url: element.zoom_start_url,
            zoom_join_url: element.zoom_join_url,
            zoom_app_prop: null,
            id: element.id,
        };
        data.push(item);
    });

    if (zoom_statsulbar.status) {
        zoom_statsulbar.data.meetings.forEach((element) => {
            let dapat = false;
            for (let i = 0; i < data.length; i++) {
                if (data[i].zoom_meeting_id == element.id) {
                    dapat = true;
                    data[i].zoom_app_prop = element;
                }
            }
            if (!dapat) {
                const item: zoom = {
                    topic: element.topic,
                    password: "",
                    start_time: new Date(element.start_time).toISOString(),
                    duration: element.duration,
                    end_time: addMinutesToDate(
                        new Date(element.start_time),
                        element.duration,
                    ).toISOString(),
                    pembuat_username: null,
                    pembuat_nama: null,
                    zoom_status: -1,
                    zoom_akun: "statsulbar",
                    zoom_meeting_id: element.id,
                    zoom_start_url: null,
                    zoom_join_url: element.zoom_join_url,
                    zoom_app_prop: element,
                    id: null,
                };
                data.push(item);
            }
        });
    }

    if (zoom_sp2020.status) {
        zoom_sp2020.data.meetings.forEach((element) => {
            let dapat = false;
            for (let i = 0; i < data.length; i++) {
                if (data[i].zoom_meeting_id == element.id) {
                    dapat = true;
                    data[i].zoom_app_prop = element;
                }
            }
            if (!dapat) {
                const item: zoom = {
                    topic: element.topic,
                    password: "",
                    start_time: new Date(element.start_time).toISOString(),
                    duration: element.duration,
                    end_time: addMinutesToDate(
                        new Date(element.start_time),
                        element.duration,
                    ).toISOString(),
                    pembuat_username: null,
                    pembuat_nama: null,
                    zoom_status: -1,
                    zoom_akun: "sp2020",
                    zoom_meeting_id: element.id,
                    zoom_start_url: null,
                    zoom_join_url: element.zoom_join_url,
                    zoom_app_prop: element,
                    id: null,
                };
                data.push(item);
            }
        });
    }

    data.sort((a, b) => {
        if (a.start_time < b.start_time) {
            return -1;
        } else if (a.start_time > b.start_time) {
            return 1;
        }
        return 0;
    });

    const [eventDialog, setEventDialog] = React.useState<zoom_dialog_event>({
        status: null,
        data: null,
    });

    const [zoom, setZoom] = React.useState<zoom[]>(data);
    const [zoomHariIni, setZoomHariIni] = React.useState<zoom[]>([]);
    const [zoomKemarin, setZoomKemarin] = React.useState<zoom[]>([]);
    const [zoomBesok, setZoomBesok] = React.useState<zoom[]>([]);
    const [zoomButuhApproval, setZoomButuhApproval] = React.useState<zoom[]>([]);

    React.useEffect(() => {
        let hari_ini_00 = new Date().setHours(0, 0, 0, 0);
        let hari_ini = zoom.filter((item) => {
            if (new Date(item.start_time).setHours(0, 0, 0, 0) == hari_ini_00) {
                return true;
            } else {
                false;
            }
        });
        let hari_sebelum = zoom.filter((item) => {
            if (new Date(item.start_time).setHours(0, 0, 0, 0) < hari_ini_00) {
                return true;
            } else {
                return false;
            }
        });
        let hari_setelah = zoom.filter((item) => {
            if (new Date(item.start_time).setHours(0, 0, 0, 0) > hari_ini_00) {
                return true;
            } else {
                return false;
            }
        });
        let hari_approval = zoom.filter((item) => {
            if (new Date(item.start_time).setHours(0, 0, 0, 0) > hari_ini_00 && item.zoom_status == 0) {
                return true;
            } else {
                return false;
            }
        });
        setZoomHariIni(hari_ini);
        setZoomKemarin(hari_sebelum);
        setZoomBesok(hari_setelah);
        setZoomButuhApproval(hari_approval)
    }, [zoom, eventDialog]);

    const auth = usePage<SharedData>().props.auth;
    const peran = auth.peran;
    const [haveZoomRole, setHaveZoomRole] = React.useState(false);

    React.useEffect(() => {
        let admin_zoom = peran.find((item) => {
            if (item.peran == "zoom") {
                return true;
            }
            return false;
        });

        if (admin_zoom) {
            setHaveZoomRole(true);
        } else {
            setHaveZoomRole(false);
        }
    }, [peran]);

    const tahun_active = usePage<SharedData>().props.tahun_active;
    const nama_bulan = [
        "Januari",
        "Februari",
        "Maret",
        "April",
        "Mei",
        "Juni",
        "Juli",
        "Agustus",
        "September",
        "Oktober",
        "November",
        "Desember",
    ];
    let nama_bulan_pilihan = [];
    let nama_bulan_sekarang = "";
    for (let i = 1; i <= 12; i++) {
        nama_bulan_pilihan.push({
            index: i,
            nama: nama_bulan[i - 1] + " " + tahun_active,
        });
        if (bulan == i) {
            nama_bulan_sekarang = nama_bulan[i - 1] + " " + tahun_active;
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Zoom" />
            <ZommProvider
                zoom={zoom}
                setZoom={setZoom}
                eventDialog={eventDialog}
                setEventDialog={setEventDialog}
                haveZoomRole={haveZoomRole}
                setHaveZoomRole={setHaveZoomRole}
            >
                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                    <div className="w-full">
                        <Heading
                            title="Pengaturan Zoom"
                            description="Membuat dan Mengatur Jadwal Rapat Online"
                        />
                        <div className="flex items-center mt-2 mb-6 gap-x-1.5">
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button
                                        variant="outline"
                                        className="ml-auto text-accent-foreground"
                                    >
                                        {nama_bulan_sekarang}{" "}
                                        <ChevronDown />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    {nama_bulan_pilihan.map((item) => (
                                        <DropdownMenuItem asChild>
                                            <Link
                                                key={item.index}
                                                className="block w-full"
                                                href={route("zoom-index")}
                                                data={{ bulan: item.index }}
                                            >
                                                {item.nama}
                                            </Link>
                                        </DropdownMenuItem>
                                    ))}
                                </DropdownMenuContent>
                            </DropdownMenu>
                            <Button
                                className="flex items-center gap-1 bg-primary text-primary-foreground"
                                onClick={() =>
                                    setEventDialog({
                                        status: "tambah",
                                        data: null,
                                    })
                                }
                            >
                                <Plus />
                                Tambah Zoom
                            </Button>
                        </div>
                        <Tabs
                            defaultValue="hari_ini"
                            className="w-full flex-col justify-start gap-4"
                        >
                            <TabsList>
                                <TabsTrigger value="hari_ini">
                                    Hari Ini <Badge variant="default">{zoomHariIni.length}</Badge>
                                </TabsTrigger>
                                <TabsTrigger value="akan_datang">
                                    Menjelang <Badge variant="default">{zoomBesok.length}</Badge>
                                </TabsTrigger>
                                <TabsTrigger value="selesai">
                                    Selesai <Badge variant="default">{zoomKemarin.length}</Badge>
                                </TabsTrigger>
                                { haveZoomRole && (
                                    <TabsTrigger value="approve">
                                        Approve <Badge variant="default">{zoomButuhApproval.length}</Badge>
                                    </TabsTrigger>
                                )}
                            </TabsList>

                            <TabsContent value="hari_ini">
                                <TableZoom
                                    zoom_list={zoomHariIni}
                                    haveZoomRole={haveZoomRole}
                                    auth={auth}
                                    setEventDialog={setEventDialog}
                                ></TableZoom>
                            </TabsContent>
                            <TabsContent value="akan_datang">
                                <TableZoom
                                    zoom_list={zoomBesok}
                                    haveZoomRole={haveZoomRole}
                                    auth={auth}
                                    setEventDialog={setEventDialog}
                                ></TableZoom>
                            </TabsContent>
                            <TabsContent value="selesai">
                                <TableZoom
                                    zoom_list={zoomKemarin}
                                    haveZoomRole={haveZoomRole}
                                    auth={auth}
                                    setEventDialog={setEventDialog}
                                ></TableZoom>
                            </TabsContent>
                            { haveZoomRole && (
                                <TabsContent value="approve">
                                    <TableZoom
                                        zoom_list={zoomButuhApproval}
                                        haveZoomRole={haveZoomRole}
                                        auth={auth}
                                        setEventDialog={setEventDialog}
                                    ></TableZoom>
                                </TabsContent>
                            )}

                        </Tabs>
                    </div>
                </div>
            </ZommProvider>
        </AppLayout>
    );
}

function TableZoom({
    zoom_list,
    haveZoomRole,
    auth,
    setEventDialog,
}: {
    zoom_list: zoom[];
    haveZoomRole: boolean;
    auth: Auth;
    setEventDialog: (eventDialog: zoom_dialog_event) => void;
}) {

    const hari_ini_00 = new Date().setHours(0, 0, 0, 0);

    return (
        <div className="overflow-hidden rounded-md border">
            <Table>
                <TableHeader className="bg-accent">
                    <TableRow>
                        <TableHead>Topic</TableHead>
                        <TableHead className="text-center">Waktu</TableHead>
                        <TableHead className="text-center">Status</TableHead>
                        <TableHead className="text-center">Akun</TableHead>
                        <TableHead className="text-center">Pembuat</TableHead>
                        <TableHead className="text-center">Aksi</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody className="text-accent-foreground">
                       {zoom_list.length > 0 ? (
                             zoom_list.map((item) => {
                                let status = <Badge variant="outline">Belum</Badge>;
                                if (item.id != null) {
                                    if (item.zoom_status == 0) {
                                        status = <Badge variant="outline">Belum</Badge>;
                                    } else if (item.zoom_status == 1) {
                                        status = (
                                            <Badge variant="destructive">Ditolak</Badge>
                                        );
                                    } else if (item.zoom_status == 2) {
                                        status = (
                                            <Badge variant="default">Diterima</Badge>
                                        );
                                    }
                                } else if (
                                    item.zoom_app_prop != null &&
                                    item.id == null
                                ) {
                                    status = (
                                        <Badge variant="outline">Langsung Di App</Badge>
                                    );
                                }

                                let pembuat_zoom = "";
                                if (item.pembuat_nama != null) {
                                    pembuat_zoom = item.pembuat_nama;
                                } else {
                                    pembuat_zoom = "-";
                                }

                                let aksi_group = <div></div>;
                                if (
                                    item.zoom_app_prop == null &&
                                    item.zoom_status == 0
                                ) {
                                    aksi_group = (
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button
                                                    variant="ghost"
                                                    className="h-8 w-8 p-0 border"
                                                >
                                                    <span className="sr-only">
                                                        Buka Aksi
                                                    </span>
                                                    <MoreHorizontal />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent className="w-56">
                                                { ( (haveZoomRole || item.pembuat_username == auth.user.username) &&
                                                    new Date(item.start_time).setHours(0,0,0,0) >= hari_ini_00 ) &&(
                                                        <>
                                                            <DropdownMenuItem
                                                                onClick={() =>
                                                                    setEventDialog({
                                                                        status: "ubah",
                                                                        data: item,
                                                                    })
                                                                }
                                                            >
                                                                Edit
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem
                                                                onClick={() =>
                                                                    setEventDialog({
                                                                        status: "hapus",
                                                                        data: item,
                                                                    })
                                                                }
                                                            >
                                                                Hapus
                                                            </DropdownMenuItem>
                                                        </>
                                                )}

                                                { (haveZoomRole && new Date(item.start_time).setHours(0,0,0,0) >= hari_ini_00) && (
                                                        <DropdownMenuItem
                                                            onClick={() =>
                                                                setEventDialog({
                                                                    status: "approval",
                                                                    data: item,
                                                                })
                                                            }
                                                        >
                                                            Approval
                                                        </DropdownMenuItem>
                                                    )}

                                                { ( (haveZoomRole || item.pembuat_username == auth.user.username) &&
                                                    new Date(item.start_time).setHours(0,0,0,0) == hari_ini_00 &&
                                                    item.zoom_start_url != null) && (
                                                        <DropdownMenuItem>
                                                            <a
                                                                className="w-full"
                                                                href={item.zoom_start_url}
                                                                target="_blank"
                                                            >
                                                                Start Zoom
                                                            </a>
                                                        </DropdownMenuItem>
                                                    )}

                                                { (new Date(item.start_time).setHours(0,0,0,0) >= hari_ini_00
                                                    && item.zoom_join_url != null) && (
                                                    <DropdownMenuItem>
                                                        <a
                                                            className="w-full"
                                                            href="/venues/"
                                                            target="_blank"
                                                        >
                                                            Join Zoom
                                                        </a>
                                                    </DropdownMenuItem>
                                                )}
                                                { (new Date(item.start_time).setHours(0,0,0,0) >= hari_ini_00
                                                    && item.zoom_join_url != null) && (
                                                    <DropdownMenuItem>
                                                        <button
                                                            className="w-full"
                                                            onClick={() => {
                                                                if(item.zoom_join_url){
                                                                    navigator.clipboard.writeText(item.zoom_join_url)}
                                                                }
                                                            }
                                                        >
                                                            Copy URL Join
                                                        </button>
                                                    </DropdownMenuItem>
                                                )}
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    );
                                }

                                return (
                                    <TableRow
                                        key={"-" + item.id + "-" + item.zoom_meeting_id}
                                    >
                                        <TableCellWrap className="text-wrap whitespace-wrap min-w-45">
                                            {item.topic}
                                            <br />
                                        </TableCellWrap>
                                        <TableCellWrap className="text-center min-w-45">
                                            {date_format(item.start_time)}
                                            <br />
                                            {get_time_from_date_time(
                                                item.start_time,
                                            )} -{" "}
                                            {get_time_from_date_time(item.end_time)}
                                        </TableCellWrap>
                                        <TableCell className="text-center">{status}</TableCell>
                                        <TableCell className="text-center">{item.zoom_akun}</TableCell>
                                        <TableCellWrap className="text-center min-w-45">{pembuat_zoom}</TableCellWrap>
                                        <TableCell className="text-center">{aksi_group}</TableCell>
                                    </TableRow>
                                );
                            })
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={6}
                                    className="h-12 text-center"
                                >
                                    Tidak ada jadwal.
                                </TableCell>
                            </TableRow>
                        )}

                </TableBody>
            </Table>
        </div>
    );
}

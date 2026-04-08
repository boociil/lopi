import AppLayout from "@/layouts/app-layout";
import {
    type User,
    type BreadcrumbItem,
} from "@/types";
import { Head } from "@inertiajs/react";

import * as React from "react";
import Heading from "@/components/heading";
import { Table, TableBody, TableCell, TableCellWrap, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Field, FieldLabel } from "@/components/ui/field"
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { ChartLine, ChartPie } from "lucide-react";
import { Badge } from "@/components/ui/badge";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Toples Apresiasi",
        href: '/lopi/appreciate',
    },
     {
        title: "Rekap",
        href: '/lopi/appreciate/rekap',
    },
];

interface data_rekap{
    dari: string,
    master_id: number,
    jumlah: number,
}

interface target{
    id: number;
    master_id: number,
    username: string,
}

interface master{
    id: number,
    nama: string,
    target: target[],
}


export default function Rekap({
    data,
    master,
    user,
}: React.PropsWithChildren<{
    data: data_rekap[];
    master: master[];
    user: User[];
}>) {

    const [userDict, setUserDict] = React.useState<Map<string, string>>(new Map<string, string>);

    React.useEffect(() => {
        let userDict_ = new Map<string, string>()
        user.forEach(element => {
            userDict_.set(element.username, element.nama)
        });
        setUserDict(userDict_)
    }, [user]);

    const [selectedMaster, setSelectedMaster] = React.useState<master | undefined>(master.length == 0 ? undefined : master[master.length - 1]);

    const [dataRekap, setDataRekap] = React.useState<Map<string, number>>(new Map<string, number>);

    React.useEffect(() => {
        let dataDict_ = new Map<string, number>()
        data.forEach(element => {
            dataDict_.set(element.master_id+'-'+element.dari, element.jumlah)
        });
        setDataRekap(dataDict_)
    }, [data]);

    const [persentase, setPersentase] = React.useState<number>(0.00)
    const [persentasePegawai, setPersentasePegawai] = React.useState<number>(0.00)
    React.useEffect(() => {
        let dataDict_ = new Map<string, number>()
        let dariAdaIsi : string[] = []
        let jumlah = 0
        data.forEach(element => {
            dataDict_.set(element.master_id+'-'+element.dari, element.jumlah)
            if(selectedMaster){
                if(userDict.has(element.dari) && selectedMaster!.id == element.master_id){
                    jumlah += Number(element.jumlah)

                    if(!dariAdaIsi.includes(element.dari)){
                        dariAdaIsi.push(element.dari)
                    }
                }
            }

        });
        setDataRekap(dataDict_)
        if(selectedMaster){
            setPersentase(Math.floor(jumlah * 100 * 100 / (user.length * selectedMaster!.target.length)) /100)
            setPersentasePegawai(Math.floor(dariAdaIsi.length * 100 * 100 / (user.length)) /100)
        }
    }, [selectedMaster, userDict]);


    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Apresiasi" />
                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                    <div className="w-full">
                        <Heading
                            title="Rekap Apresiasi"
                            description="Ringkasan data apresiasi yang diberikan Pegawai."
                        />
                        <div className="flex w-full justify-end items-end">
                            <Field className="w-lg">
                                <FieldLabel>Pilih Mater</FieldLabel>
                                <Select
                                    defaultValue={selectedMaster? ""+selectedMaster.id : undefined}
                                        onValueChange={(value) => {setSelectedMaster(master.find(item => item.id == Number(value)))}}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Pili Master" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            {master.map((item, index) => { return (<SelectItem key={index} value={""+item.id}>{item.nama}</SelectItem>) })}
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                {/* <FieldError>Please select a fruit.</FieldError> */}
                            </Field>
                        </div>
                         <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-5 my-5">

                            <Card className="border gap-3 py-4 my-0">
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-1 text-muted-foreground">
                                    <CardTitle className="text-sm font-normal">Pers. Apresiasi Terkirim</CardTitle>
                                    <ChartLine className="h-4 w-4 " />
                                </CardHeader>
                                <CardContent>
                                    <div className="flex items-end gap-2 mt-2">
                                        <span className="text-4xl font-bold ">{persentase}</span>
                                        <span className="text-xs  mb-1">persen</span>
                                    </div>
                                    <span className="inline-block px-0 py-1 text-xs font-semibold rounded-full uppercase mt-4 mb-0">
                                        {persentase >= 70 && <Badge variant="success_">Sudah Memenuhi</Badge>}
                                        {persentase < 70 && <Badge variant="destructive_">Belum Memenuhi</Badge>}
                                    </span>
                                </CardContent>
                            </Card>
                            <Card className="border gap-3 py-4 my-0">
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-1 text-muted-foreground">
                                    <CardTitle className="text-sm font-normal">Pers. Yang Telah Memberikan Apresiasi</CardTitle>
                                    <ChartPie  className="h-4 w-4 " />
                                </CardHeader>
                                <CardContent>
                                    <div className="flex items-end gap-2 mt-2">
                                        <span className="text-4xl font-bold ">{persentasePegawai}</span>
                                        <span className="text-xs  mb-1">persen</span>
                                    </div>
                                    <span className="inline-block px-0 py-1 text-xs font-semibold rounded-full uppercase mt-4 mb-0">
                                        {persentasePegawai >= 70 && <Badge variant="success_">Sudah Memenuhi</Badge>}
                                        {persentasePegawai < 70 && <Badge variant="destructive_">Belum Memenuhi</Badge>}
                                    </span>
                                </CardContent>
                            </Card>
                        </div>
                         <div className="overflow-hidden rounded-md border">

                            <Table>
                                <TableHeader className="bg-accent">
                                    <TableRow>
                                        <TableHead className="text-center">
                                            No
                                        </TableHead>
                                        <TableHead>Nama</TableHead>
                                        <TableHead className="text-left">
                                            Target
                                        </TableHead>
                                        <TableHead className="text-left">
                                            Realisasi
                                        </TableHead>
                                        <TableHead>
                                            Capaian
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody className="text-accent-foreground">
                                    {selectedMaster != undefined ? (
                                        user.map((item, index) => {
                                            return (
                                                <TableRow key={"-" + item.id}>
                                                    <TableCell className="text-center">
                                                        {index+1}
                                                    </TableCell>
                                                    <TableCellWrap>
                                                        {userDict.has(item.username) ? userDict.get(item.username) : item.username}
                                                    </TableCellWrap>
                                                    <TableCellWrap>
                                                        {selectedMaster.target.length}
                                                    </TableCellWrap>
                                                    <TableCellWrap>
                                                        {dataRekap.has(selectedMaster.id+'-'+item.username) ? dataRekap.get(selectedMaster.id+'-'+item.username) : 0}
                                                    </TableCellWrap>
                                                     <TableCellWrap>
                                                        {(selectedMaster.target.length > 0 && dataRekap.has(selectedMaster.id+'-'+item.username)) ? Math.floor(dataRekap.get(selectedMaster?.id+'-'+item.username)/selectedMaster.target.length  * 100) +'%' : '-'}
                                                    </TableCellWrap>

                                                </TableRow>
                                            );
                                        })
                                    ) : (
                                        <TableRow>
                                            <TableCell
                                                colSpan={4}
                                                className="h-12 text-center"
                                            >
                                                Tidak ada data.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>

                        </div>
                    </div>
                </div>
        </AppLayout>
    )
}

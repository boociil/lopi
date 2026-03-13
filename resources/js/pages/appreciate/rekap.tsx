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
    master_id: string,
    jumlah: number,
}

interface target{
    id: number;
    master_id: string,
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

    const [selectedMaster, setSelectedMaster] = React.useState<master | undefined>(master.length == 0 ? undefined : master[master.length - 1]);


    const [userDict, setUserDict] = React.useState<Map<string, string>>(new Map<string, string>);

    React.useEffect(() => {
        let userDict_ = new Map<string, string>()
        user.forEach(element => {
            userDict_.set(element.username, element.nama)
        });
        setUserDict(userDict_)
    }, [user]);

    const [dataRekap, setDataRekap] = React.useState<Map<string, number>>(new Map<string, number>);

    React.useEffect(() => {
        let dataDict_ = new Map<string, number>()
        data.forEach(element => {
            dataDict_.set(element.master_id+'-'+element.dari, element.jumlah)
        });
        setDataRekap(dataDict_)
    }, [data]);


    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Apresiasi" />
                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                    <div className="w-full">
                        <Heading
                            title="Rekap Apresiasi"
                            description="Ringkasan data apresiasi yang diberikan Pegawai."
                        />
                        <div className="flex w-full justify-end items-end mb-4">
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

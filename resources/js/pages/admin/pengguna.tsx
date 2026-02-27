import AppLayout from "@/layouts/app-layout";
import {
    User,
    type BreadcrumbItem,
} from "@/types";
import React, { useEffect, useRef, useState } from 'react';

import {
  Trash2,
  Plus,
  ChevronLeft,
  ChevronRight,
  Download,
  Upload,
  Search,

} from 'lucide-react';

import { Button } from "@/components/ui/button";

import Heading from "@/components/heading";
import { useApi } from "@/lib/API/useApi";
import { toast } from "sonner";
import { Card } from "@/components/ui/card";
import { Head } from "@inertiajs/react";
import { Input } from "@/components/ui/input";
import { useLoading } from "@/props/LoadingContext";
import { useDebouncedCallback } from 'use-debounce';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Pengguna",
        href: '/admin/pengguna',
    }
];


export default function Index({
    daftar_,
    total_,
}: React.PropsWithChildren<{
    daftar_: User[],
    total_: number,
}>) {

    const [limit, setLimit] = useState<number>(25);
    const [page, setPage] = useState<number>(1);
    const [total, setTotal] = useState<number>(total_);
    const [userAll, setUserAll] = useState<User[]>(daftar_);
    const [searchTerm, setSearchTerm] = useState<string>("");
    const debouncedSearchTerm = useDebouncedCallback(
        // function
        (value) => {
            setSearchTerm(value);
        },
        // delay in ms
        700
    );

    const {setIsLoading} = useLoading();
    const isMounted = useRef(false);

    const axiosClient = useApi();


    const handleSubmit = (event:  React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        setIsLoading(true)
        const formData_ = new FormData(event.currentTarget)

        axiosClient.post(route('admin-pengguna-format-unggah'), formData_)
            .then(function (response) {
                toast.success("Berhasil menambah data.");
                setIsLoading(false)
            })
            .catch(function (error) {
                toast.success("Gagal menambah data.");
                setIsLoading(false)
            });


    };

    function hapusUser(target: User){
        axiosClient.post(route('arsip-hapus', {'arsip': target.id}))
            .then(function (response) {
                setUserAll(userAll.filter(h => h.username !== target.username))
                toast.success("Berhasil hapus data.");
            })
            .catch(function (error) {
                toast.success("Gagal hapus data.");
            });
    };

    useEffect(() => {
        if (isMounted.current) {
            setIsLoading(true)
            axiosClient.post(route('admin-pengguna-index'), {'page': page, 'limit': limit, 'search_term': searchTerm})
                .then(function (response) {

                    setUserAll(response.data.daftar_ as User[])
                    setTotal(response.data.total_ as number)
                    setPage(page)
                    toast.success("Berhasil meload data.");
                    setIsLoading(false)
                })
                .catch(function (error) {
                    toast.success("Gagal meload data.");
                    setIsLoading(false)
                });
        } else {
            isMounted.current = true;
        }

    }, [page, limit, searchTerm]);

    const handleDownload = async () => {
        try {
            setIsLoading(true)
            const response = await axiosClient.get(
                route('admin-pengguna-format-unduh'),
                {
                    responseType: "blob", // WAJIB untuk file
                }
            );

            const blob = new Blob([response.data]);
            const url = window.URL.createObjectURL(blob);

            const link = document.createElement("a");
            link.href = url;

            // ambil filename dari header kalau ada
            const contentDisposition = response.headers["content-disposition"];
            let fileName = "download";

            if (contentDisposition) {
                const match = contentDisposition.match(/filename="(.+)"/);
                if (match?.[1]) fileName = match[1];
            }

            link.setAttribute("download", 'format-tambah-pengguna');
            document.body.appendChild(link);
            link.click();

            link.remove();
            window.URL.revokeObjectURL(url);
            setIsLoading(false)
        } catch (error) {
            console.error("Download gagal:", error);
            setIsLoading(false)
        }
    };


      return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pengguna" />

                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                    <div className="w-full">
                        <Heading
                            title="Pengguna"
                            description="Kelola Pengguna."
                        />

                        <div className="flex flex-col md:flex-row md:items-center justify-end space-y-0 gap-4">

                            <div className="flex items-center gap-2">
                                <Button className="bg-[#6366f1]">
                                    <Download size={16} />
                                    Format
                                </Button>
                                <Button className="bg-[#f59e0b]">
                                    <Upload size={16} />
                                    Unggah
                                </Button>
                            </div>
                        </div>

                        {/* Table Controls */}
                        <div className="p-2 my-3 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div className="flex items-center gap-2 text-sm">
                            <span>Lihat :</span>
                            <select className="border border-gray-300 rounded px-2 py-1 bg-white focus:outline-none focus:ring-1 focus:ring-blue-500"
                                defaultValue={limit} onChange={(e) => {setLimit(Number(e.target.value))}}>
                                <option value={10}>10</option>
                                <option value={25}>25</option>
                                <option value={50}>50</option>
                            </select>
                            </div>

                            <div className="relative w-full md:w-64">
                                <Input placeholder="Cari" className="pr-10 rounded-full"
                                    defaultValue={searchTerm} onChange={(e) => {debouncedSearchTerm(e.target.value)
                                    }}/>
                                <Search className="absolute right-3 top-2.5 text-gray-400" size={18} />
                            </div>
                        </div>

                        <Card className="overflow-hidden p-0 mt-4">
                            <div className="w-full overflow-auto">
                                <table className="w-full caption-bottom text-sm">
                                        <thead className="[&_tr]:border-b bg-accent">
                                            <tr className="border-b transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                            <th className="h-12 px-4 text-center align-middle font-medium ">No</th>
                                            <th className="h-12 px-4 text-left align-middle font-medium ">Nama</th>
                                            <th className="h-12 px-4 align-middle font-medium ">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="[&_tr:last-child]:border-0">
                                        {userAll.map((item, index) => (
                                        <tr key={item.id} className="border-b transition-colors hover:bg-slate-50/50">
                                            <td className="p-4 align-middle font-mono font-bold">{(page-1)*limit + index + 1}</td>
                                            <td className="p-4 align-middle font-mono font-bold ">
                                                {item.nama}
                                            </td>

                                            <td className="p-4 align-middle text-center">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => hapusUser(item)}
                                                    className="h-8 w-8 p-0"
                                                    >
                                                    <Trash2 className="h-4 w-4  hover:text-red-500" />
                                                </Button>
                                            </td>
                                        </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            <PaginationFooter
                                currentPage={page}
                                itemsPerPage={20}
                                totalItems={total}
                                totalPages={Math.max(Math.ceil(total / limit), 1)}
                                setCurrentPage={(page: number): void => {setPage(page)}}
                            />
                        </Card>
                    </div>
                </div>

        </AppLayout>
      );
}

const PaginationFooter = ({ currentPage, itemsPerPage, totalItems, totalPages, setCurrentPage } : { currentPage: number, itemsPerPage: number, totalItems: number, totalPages: number, setCurrentPage(page: number): void}) => (
  <div className="flex items-center justify-between px-4 py-4 border-t border-slate-100">
    <p className="text-xs ">
      Menampilkan <span className="font-medium ">{(currentPage - 1) * itemsPerPage + 1}</span> hingga <span className="font-medium ">{Math.min(currentPage * itemsPerPage, totalItems)}</span> dari <span className="font-medium ">{totalItems}</span> data
    </p>
    <div className="flex items-center space-x-2">
      <Button
        variant="outline"
        className="h-8 w-8 p-0"
        onClick={() => setCurrentPage(Math.max(1, currentPage - 1))}
        disabled={currentPage === 1}
      >
        <ChevronLeft size={16} />
      </Button>

      <div className="flex items-center gap-1">
        {[...Array(totalPages)].map((_, i) => (
          <Button
            key={i}
            variant={currentPage === i + 1 ? 'outline' : 'ghost'}
            className={`h-8 w-8 p-0 text-xs ${currentPage === i + 1 ? 'border-slate-900 bg-accent' : ''}`}
            onClick={() => setCurrentPage(i + 1)}
          >
            {i + 1}
          </Button>
        ))}
      </div>

      <Button
        variant="outline"
        className="h-8 w-8 p-0"
        onClick={() => setCurrentPage(Math.min(totalPages, currentPage+1)) }
        disabled={currentPage === totalPages}
      >
        <ChevronRight size={16} />
      </Button>
    </div>
  </div>
);


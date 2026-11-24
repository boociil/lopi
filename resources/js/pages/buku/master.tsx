import AppLayout from "@/layouts/app-layout";
import Heading from "@/components/heading";
import { Head } from "@inertiajs/react";
import { type BukuPinjam, type BukuPinjamStatus, type BreadcrumbItem } from "@/types";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from "@/components/ui/alert-dialog";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableCellWrap, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { useApi } from "@/lib/API/useApi";
import { refreshBukuNotif } from "@/lib/buku-notif";
import { date_format, date_short } from "@/lib/date";
import { AlertTriangle, BookOpen, CheckCircle2, ChevronLeft, ChevronRight, Clock3, Loader2, Search } from "lucide-react";
import { useMemo, useEffect, useState } from "react";
import { toast } from "sonner";

const PER_PAGE = 10;

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Buku", href: "/lopi/buku" },
    { title: "Pinjaman Buku", href: "/lopi/buku/master" },
];

type FilterStatus = "semua" | BukuPinjamStatus | "terlambat" | "tepat_waktu" | "selesai_terlambat";

function StatusBadge({ item }: { item: BukuPinjam }) {
    if (item.is_terlambat) {
        return <Badge variant="destructive_">Terlambat {item.hari_terlambat} hari</Badge>;
    }

    if (item.status === "dipinjam" && item.sisa_hari !== null && item.sisa_hari <= 3) {
        return (
            <Badge variant="warning_">
                {item.sisa_hari === 0
                    ? "Jatuh tempo hari ini"
                    : item.sisa_hari === 1
                      ? "Jatuh tempo besok"
                      : `Jatuh tempo ${item.sisa_hari} hari lagi`}
            </Badge>
        );
    }

    if (item.status === "dikembalikan") {
        return (
            <Badge variant={item.is_kembali_terlambat ? "destructive_" : "success_"}>
                {`Selesai · ${date_short(item.tanggal_kembali)}`}
            </Badge>
        );
    }

    const map: Record<BukuPinjamStatus, { label: string; variant: "warning_" | "default" | "success_" | "destructive_" }> = {
        menunggu: { label: "Menunggu", variant: "warning_" },
        dipinjam: { label: "Dipinjam", variant: "default" },
        ditolak: { label: "Ditolak", variant: "destructive_" },
        dikembalikan: { label: "Selesai", variant: "success_" },
    };

    const config = map[item.status];

    return <Badge variant={config.variant}>{config.label}</Badge>;
}

export default function MasterBuku({ pinjam }: { pinjam: BukuPinjam[] }) {
    const axiosClient = useApi();
    const [list, setList] = useState<BukuPinjam[]>(pinjam);
    const [filter, setFilter] = useState<FilterStatus>("semua");
    const [search, setSearch] = useState("");
    const [loadingId, setLoadingId] = useState<number | null>(null);
    const [page, setPage] = useState(1);

    // Ambil ulang data dari props setiap Inertia mengirim data baru.
    useEffect(() => {
        setList(pinjam);
    }, [pinjam]);

    const ringkasan = useMemo(
        () => ({
            menunggu: list.filter((item) => item.status === "menunggu").length,
            dipinjam: list.filter((item) => item.status === "dipinjam").length,
            terlambat: list.filter((item) => item.is_terlambat).length,
            selesai: list.filter((item) => item.status === "dikembalikan").length,
        }),
        [list],
    );

    const filtered = useMemo(() => {
        const q = search.toLowerCase();
        return list.filter((item) => {
            const matchStatus =
                filter === "semua"
                    ? true
                    : filter === "terlambat"
                      ? item.is_terlambat
                      : filter === "tepat_waktu"
                        ? item.status === "dikembalikan" && !item.is_kembali_terlambat
                        : filter === "selesai_terlambat"
                          ? item.status === "dikembalikan" && item.is_kembali_terlambat
                          : item.status === filter;
            const matchSearch =
                !q ||
                (item.peminjam?.nama ?? item.peminjam_username).toLowerCase().includes(q) ||
                (item.buku?.judul ?? "").toLowerCase().includes(q);
            return matchStatus && matchSearch;
        });
    }, [list, filter, search]);

    const totalPages = Math.max(1, Math.ceil(filtered.length / PER_PAGE));
    const currentPage = Math.min(page, totalPages);
    const pageItems = useMemo(
        () => filtered.slice((currentPage - 1) * PER_PAGE, currentPage * PER_PAGE),
        [filtered, currentPage],
    );

    // Kembali ke halaman pertama setiap filter/pencarian berubah.
    useEffect(() => {
        setPage(1);
    }, [filter, search]);

    function replaceItem(updated: BukuPinjam) {
        setList((prev) => prev.map((item) => (item.id === updated.id ? updated : item)));
    }

    function setujui(item: BukuPinjam) {
        setLoadingId(item.id);
        axiosClient
            .post(route("buku-setujui", { pinjam: item.id }))
            .then((response) => {
                replaceItem(response.data?.data as BukuPinjam);
                toast.success(response.data?._status ?? "Peminjaman disetujui.");
                refreshBukuNotif(-1);
            })
            .catch((error) => {
                const message =
                    error?.response?.data?.errors?._status?.[0] ??
                    error?.response?.data?.message ??
                    "Gagal menyetujui peminjaman.";
                toast.error(message);
            })
            .finally(() => setLoadingId(null));
    }

    function kembalikan(item: BukuPinjam) {
        setLoadingId(item.id);
        axiosClient
            .post(route("buku-kembali", { pinjam: item.id }))
            .then((response) => {
                replaceItem(response.data?.data as BukuPinjam);
                toast.success(response.data?._status ?? "Buku ditandai kembali.");
                refreshBukuNotif(item.is_terlambat ? -1 : 0);
            })
            .catch((error) => {
                const message =
                    error?.response?.data?.errors?._status?.[0] ??
                    error?.response?.data?.message ??
                    "Gagal menandai buku kembali.";
                toast.error(message);
            })
            .finally(() => setLoadingId(null));
    }

    function tolak(item: BukuPinjam) {
        setLoadingId(item.id);

        axiosClient
            .post(route("buku-tolak", { pinjam: item.id }))
            .then((response) => {
                replaceItem(response.data?.data as BukuPinjam);
                toast.success(response.data?._status ?? "Pengajuan ditolak.");
                refreshBukuNotif(-1);
            })
            .catch((error) => {
                const message =
                    error?.response?.data?.errors?._status?.[0] ??
                    error?.response?.data?.message ??
                    "Gagal menolak pengajuan.";
                toast.error(message);
            })
            .finally(() => setLoadingId(null));
    }

    const cards = [
        { label: "Menunggu", value: ringkasan.menunggu, icon: Clock3, className: "bg-amber-50 text-amber-600" },
        { label: "Dipinjam", value: ringkasan.dipinjam, icon: BookOpen, className: "bg-sky-50 text-sky-600" },
        { label: "Terlambat", value: ringkasan.terlambat, icon: AlertTriangle, className: "bg-red-50 text-red-600" },
        { label: "Selesai", value: ringkasan.selesai, icon: CheckCircle2, className: "bg-emerald-50 text-emerald-600" },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pinjaman Buku" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="w-full">
                    <Heading
                        title="Pinjaman Buku"
                        description="Pantau pengajuan, setujui peminjaman, dan cek buku yang terlambat."
                    />

                    <div className="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
                        {cards.map((card) => (
                            <div key={card.label} className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-xs font-semibold uppercase tracking-widest text-slate-400">
                                            {card.label}
                                        </p>
                                        <p className="mt-2 text-2xl font-bold text-slate-800">{card.value}</p>
                                    </div>
                                    <div className={`rounded-2xl p-3 ${card.className}`}>
                                        <card.icon className="size-5" />
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center">
                        <div className="relative w-full flex-1">
                            <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                            <Input
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Cari nama peminjam atau judul buku..."
                                className="pl-9"
                            />
                        </div>
                        <div className="w-full shrink-0 md:w-56">
                            <Select value={filter} onValueChange={(value) => setFilter(value as FilterStatus)}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Semua status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="semua">Semua status</SelectItem>
                                    <SelectItem value="menunggu">Menunggu</SelectItem>
                                    <SelectItem value="dipinjam">Dipinjam</SelectItem>
                                    <SelectItem value="terlambat">Terlambat</SelectItem>
                                    <SelectItem value="tepat_waktu">Selesai tepat waktu</SelectItem>
                                    <SelectItem value="selesai_terlambat">Selesai terlambat</SelectItem>
                                    <SelectItem value="ditolak">Ditolak</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div className="overflow-hidden rounded-md border bg-white">
                        <Table>
                            <TableHeader className="bg-accent">
                                <TableRow>
                                    <TableHead className="text-center">No</TableHead>
                                    <TableHead>Peminjam</TableHead>
                                    <TableHead>Buku</TableHead>
                                    <TableHead>Tgl Pengajuan</TableHead>
                                    <TableHead>Jatuh Tempo</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-center">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {pageItems.length > 0 ? (
                                    pageItems.map((item, index) => {
                                        const isLoading = loadingId === item.id;

                                        return (
                                            <TableRow key={item.id} className={item.is_terlambat ? "bg-red-50/60" : ""}>
                                                <TableCell className="text-center">
                                                    {(currentPage - 1) * PER_PAGE + index + 1}
                                                </TableCell>
                                                <TableCellWrap>
                                                    <span className="font-medium text-slate-800">
                                                        {item.peminjam?.nama ?? item.peminjam_username}
                                                    </span>
                                                </TableCellWrap>
                                                <TableCellWrap>
                                                    <div className="flex items-center gap-2">
                                                        <div className="flex h-10 w-8 shrink-0 items-center justify-center overflow-hidden rounded-md border border-slate-200 bg-slate-50">
                                                            {item.buku?.cover_url ? (
                                                                <img
                                                                    src={item.buku.cover_url}
                                                                    alt={item.buku.judul}
                                                                    className="h-full w-full object-contain"
                                                                />
                                                            ) : (
                                                                <BookOpen className="size-4 text-slate-300" />
                                                            )}
                                                        </div>
                                                        <span className="font-medium">{item.buku?.judul ?? "-"}</span>
                                                    </div>
                                                </TableCellWrap>
                                                <TableCellWrap>{date_format(item.tanggal_pengajuan)}</TableCellWrap>
                                                <TableCellWrap>{date_format(item.tanggal_jatuh_tempo ?? "")}</TableCellWrap>
                                                <TableCellWrap>
                                                    <StatusBadge item={item} />
                                                </TableCellWrap>
                                                <TableCell className="text-center">
                                                    {item.status === "menunggu" && (
                                                        <div className="flex items-center justify-center gap-2">
                                                            <AlertDialog>
                                                                <AlertDialogTrigger asChild>
                                                                    <Button size="sm" disabled={isLoading}>
                                                                        {isLoading ? (
                                                                            <Loader2 className="size-4 animate-spin" />
                                                                        ) : (
                                                                            "Setujui"
                                                                        )}
                                                                    </Button>
                                                                </AlertDialogTrigger>
                                                                <AlertDialogContent>
                                                                    <AlertDialogHeader>
                                                                        <AlertDialogTitle>
                                                                            Setujui peminjaman ini?
                                                                        </AlertDialogTitle>
                                                                        <AlertDialogDescription>
                                                                            Pengajuan{" "}
                                                                            <span className="font-medium text-slate-700">
                                                                                {item.buku?.judul ?? "buku"}
                                                                            </span>{" "}
                                                                            oleh{" "}
                                                                            <span className="font-medium text-slate-700">
                                                                                {item.peminjam?.nama ??
                                                                                    item.peminjam_username}
                                                                            </span>{" "}
                                                                            akan disetujui. Stok buku berkurang dan
                                                                            jatuh tempo dihitung otomatis sesuai durasi
                                                                            pinjam.
                                                                        </AlertDialogDescription>
                                                                    </AlertDialogHeader>
                                                                    <AlertDialogFooter>
                                                                        <AlertDialogCancel>Batal</AlertDialogCancel>
                                                                        <AlertDialogAction onClick={() => setujui(item)}>
                                                                            Setujui
                                                                        </AlertDialogAction>
                                                                    </AlertDialogFooter>
                                                                </AlertDialogContent>
                                                            </AlertDialog>
                                                            <AlertDialog>
                                                                <AlertDialogTrigger asChild>
                                                                    <Button size="sm" variant="outline" disabled={isLoading}>
                                                                        Tolak
                                                                    </Button>
                                                                </AlertDialogTrigger>
                                                                <AlertDialogContent>
                                                                    <AlertDialogHeader>
                                                                        <AlertDialogTitle>
                                                                            Tolak pengajuan ini?
                                                                        </AlertDialogTitle>
                                                                        <AlertDialogDescription>
                                                                            Pengajuan{" "}
                                                                            <span className="font-medium text-slate-700">
                                                                                {item.buku?.judul ?? "buku"}
                                                                            </span>{" "}
                                                                            oleh{" "}
                                                                            <span className="font-medium text-slate-700">
                                                                                {item.peminjam?.nama ??
                                                                                    item.peminjam_username}
                                                                            </span>{" "}
                                                                            akan ditolak. Tindakan ini tidak dapat
                                                                            dibatalkan.
                                                                        </AlertDialogDescription>
                                                                    </AlertDialogHeader>
                                                                    <AlertDialogFooter>
                                                                        <AlertDialogCancel>Batal</AlertDialogCancel>
                                                                        <AlertDialogAction
                                                                            className="bg-red-600 hover:bg-red-700"
                                                                            onClick={() => tolak(item)}
                                                                        >
                                                                            Tolak Pengajuan
                                                                        </AlertDialogAction>
                                                                    </AlertDialogFooter>
                                                                </AlertDialogContent>
                                                            </AlertDialog>
                                                        </div>
                                                    )}
                                                    {item.status === "dipinjam" && (
                                                        <AlertDialog>
                                                            <AlertDialogTrigger asChild>
                                                                <Button size="sm" variant="outline" disabled={isLoading}>
                                                                    {isLoading ? (
                                                                        <Loader2 className="size-4 animate-spin" />
                                                                    ) : (
                                                                        "Tandai Kembali"
                                                                    )}
                                                                </Button>
                                                            </AlertDialogTrigger>
                                                            <AlertDialogContent>
                                                                <AlertDialogHeader>
                                                                    <AlertDialogTitle>
                                                                        Tandai buku sudah dikembalikan?
                                                                    </AlertDialogTitle>
                                                                    <AlertDialogDescription>
                                                                        Peminjaman{" "}
                                                                        <span className="font-medium text-slate-700">
                                                                            {item.buku?.judul ?? "buku"}
                                                                        </span>{" "}
                                                                        oleh{" "}
                                                                        <span className="font-medium text-slate-700">
                                                                            {item.peminjam?.nama ??
                                                                                item.peminjam_username}
                                                                        </span>{" "}
                                                                        akan ditandai selesai dan stok buku ditambahkan
                                                                        kembali. Pastikan buku benar-benar sudah
                                                                        diterima.
                                                                    </AlertDialogDescription>
                                                                </AlertDialogHeader>
                                                                <AlertDialogFooter>
                                                                    <AlertDialogCancel>Batal</AlertDialogCancel>
                                                                    <AlertDialogAction onClick={() => kembalikan(item)}>
                                                                        Tandai Kembali
                                                                    </AlertDialogAction>
                                                                </AlertDialogFooter>
                                                            </AlertDialogContent>
                                                        </AlertDialog>
                                                    )}
                                                    {item.status !== "menunggu" && item.status !== "dipinjam" && (
                                                        <span className="text-xs text-slate-400">-</span>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={7} className="h-16 text-center text-slate-500">
                                            Tidak ada data peminjaman.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>
                    {filtered.length > PER_PAGE && (
                        <div className="mt-3 flex items-center justify-between gap-3">
                            <p className="text-sm text-slate-500">
                                Halaman {currentPage} dari {totalPages}
                            </p>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="gap-1"
                                    disabled={currentPage <= 1}
                                    onClick={() => setPage(currentPage - 1)}
                                >
                                    <ChevronLeft className="size-4" />
                                    Sebelumnya
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="gap-1"
                                    disabled={currentPage >= totalPages}
                                    onClick={() => setPage(currentPage + 1)}
                                >
                                    Berikutnya
                                    <ChevronRight className="size-4" />
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}

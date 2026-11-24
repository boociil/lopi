import AppLayout from "@/layouts/app-layout";
import Heading from "@/components/heading";
import { Head, Link } from "@inertiajs/react";
import { type BreadcrumbItem, type Buku, type BukuPinjam, type BukuPinjamStatus } from "@/types";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Table, TableBody, TableCell, TableCellWrap, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { useApi } from "@/lib/API/useApi";
import { refreshBukuNotif } from "@/lib/buku-notif";
import { date_format, date_short } from "@/lib/date";
import { BookOpen, ChevronLeft, ChevronRight, Loader2, Search } from "lucide-react";
import { useMemo, useEffect, useState } from "react";
import { toast } from "sonner";

const PER_PAGE = 5;
const BUKU_PER_PAGE = 6;

type RiwayatFilter = "semua" | BukuPinjamStatus | "terlambat" | "tepat_waktu" | "selesai_terlambat";
type RiwayatUrutan = "prioritas" | "tanggal_baru" | "tanggal_lama";

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Buku", href: "/lopi/buku" },
    { title: "List Buku", href: "/lopi/buku" },
];

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
        menunggu: { label: "Menunggu ACC", variant: "warning_" },
        dipinjam: { label: "Dipinjam", variant: "default" },
        ditolak: { label: "Ditolak", variant: "destructive_" },
        dikembalikan: { label: "Dikembalikan", variant: "success_" },
    };

    const config = map[item.status];

    return <Badge variant={config.variant}>{config.label}</Badge>;
}

export default function ListBuku({ buku, riwayat }: { buku: Buku[]; riwayat: BukuPinjam[] }) {
    const axiosClient = useApi();
    const [daftarBuku, setDaftarBuku] = useState<Buku[]>(buku);
    const [riwayatList, setRiwayatList] = useState<BukuPinjam[]>(riwayat);
    const [search, setSearch] = useState("");
    const [kategori, setKategori] = useState("semua");
    const [bukuPage, setBukuPage] = useState(1);
    const [riwayatFilter, setRiwayatFilter] = useState<RiwayatFilter>("semua");
    const [riwayatUrutan, setRiwayatUrutan] = useState<RiwayatUrutan>("prioritas");
    const [riwayatPage, setRiwayatPage] = useState(1);
    const [cancellingId, setCancellingId] = useState<number | null>(null);

    // Sinkronkan lagi saat Inertia mengirim data terbaru dari server,
    // supaya perubahan status pinjaman langsung terlihat.
    useEffect(() => {
        setDaftarBuku(buku);
        setRiwayatList(riwayat);
    }, [buku, riwayat]);

    const kategoris = useMemo(() => {
        const set = new Set<string>();
        daftarBuku.forEach((item) => {
            if (item.kategori) set.add(item.kategori);
        });
        return Array.from(set).sort();
    }, [daftarBuku]);

    const filtered = useMemo(() => {
        const q = search.toLowerCase();
        return daftarBuku.filter((item) => {
            const matchSearch =
                !q ||
                item.judul.toLowerCase().includes(q) ||
                (item.pengarang ?? "").toLowerCase().includes(q) ||
                (item.kategori ?? "").toLowerCase().includes(q);
            const matchKategori = kategori === "semua" || item.kategori === kategori;
            return matchSearch && matchKategori;
        });
    }, [daftarBuku, search, kategori]);

    // Kembali ke halaman pertama setiap kali pencarian/kategori berubah.
    useEffect(() => {
        setBukuPage(1);
    }, [search, kategori]);

    const bukuTotalPages = Math.max(1, Math.ceil(filtered.length / BUKU_PER_PAGE));
    const bukuCurrentPage = Math.min(bukuPage, bukuTotalPages);
    const bukuPageItems = useMemo(
        () => filtered.slice((bukuCurrentPage - 1) * BUKU_PER_PAGE, bukuCurrentPage * BUKU_PER_PAGE),
        [filtered, bukuCurrentPage],
    );

    const riwayatFiltered = useMemo(() => {
        const rows = riwayatList.filter((item) => {
            if (riwayatFilter === "semua") return true;
            if (riwayatFilter === "terlambat") return item.is_terlambat;
            if (riwayatFilter === "tepat_waktu") return item.status === "dikembalikan" && !item.is_kembali_terlambat;
            if (riwayatFilter === "selesai_terlambat") return item.status === "dikembalikan" && item.is_kembali_terlambat;
            return item.status === riwayatFilter;
        });

        const waktuPengajuan = (item: BukuPinjam) => {
            const nilai = new Date(item.tanggal_pengajuan).getTime();
            return Number.isNaN(nilai) ? 0 : nilai;
        };

        // Prioritas: terlambat > hampir jatuh tempo > dipinjam > menunggu > ditolak > selesai.
        const prioritas = (item: BukuPinjam) => {
            if (item.is_terlambat) return 0;
            if (item.status === "dipinjam") {
                return item.sisa_hari !== null && item.sisa_hari <= 3 ? 1 : 2;
            }
            if (item.status === "menunggu") return 3;
            if (item.status === "ditolak") return 4;
            if (item.status === "dikembalikan") return 5;
            return 6;
        };

        return rows.sort((a, b) => {
            if (riwayatUrutan === "tanggal_baru") return waktuPengajuan(b) - waktuPengajuan(a);
            if (riwayatUrutan === "tanggal_lama") return waktuPengajuan(a) - waktuPengajuan(b);

            const selisih = prioritas(a) - prioritas(b);
            if (selisih !== 0) return selisih;

            // Sesama terlambat: yang paling lama terlambat di atas.
            if (a.is_terlambat && b.is_terlambat) {
                return b.hari_terlambat - a.hari_terlambat;
            }

            // Sesama dipinjam: yang paling dekat jatuh tempo di atas.
            if (a.status === "dipinjam" && b.status === "dipinjam") {
                const sisaA = a.sisa_hari ?? Number.MAX_SAFE_INTEGER;
                const sisaB = b.sisa_hari ?? Number.MAX_SAFE_INTEGER;
                if (sisaA !== sisaB) return sisaA - sisaB;
            }

            return waktuPengajuan(b) - waktuPengajuan(a);
        });
    }, [riwayatList, riwayatFilter, riwayatUrutan]);

    const totalPages = Math.max(1, Math.ceil(riwayatFiltered.length / PER_PAGE));
    const currentPage = Math.min(riwayatPage, totalPages);
    const riwayatPageItems = useMemo(
        () => riwayatFiltered.slice((currentPage - 1) * PER_PAGE, currentPage * PER_PAGE),
        [riwayatFiltered, currentPage],
    );

    function ubahFilterRiwayat(value: RiwayatFilter) {
        setRiwayatFilter(value);
        setRiwayatPage(1);
    }

    function pengajuanAktif(bukuId: number) {
        return riwayatList.find(
            (item) => item.buku_id === bukuId && (item.status === "menunggu" || item.status === "dipinjam"),
        );
    }

    function batalkan(item: BukuPinjam) {
        if (cancellingId !== null) return;
        setCancellingId(item.id);
        axiosClient
            .post(route("buku-batal", { pinjam: item.id }))
            .then(() => {
                setRiwayatList((prev) => prev.filter((row) => row.id !== item.id));
                toast.success("Pengajuan peminjaman dibatalkan.");
                refreshBukuNotif(-1);
            })
            .catch((error) => {
                const message =
                    error?.response?.data?.errors?._status?.[0] ??
                    error?.response?.data?.message ??
                    "Gagal membatalkan pengajuan.";
                toast.error(message);
            })
            .finally(() => setCancellingId(null));
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="List Buku" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="w-full">
                    <Heading
                        title="Buku"
                        description="Riwayat peminjaman kamu dan katalog buku yang bisa dipinjam."
                    />

                    <div className="mb-6 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center">
                        <div className="relative w-full flex-1">
                            <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                            <Input
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Cari judul atau penulis..."
                                className="pl-9"
                            />
                        </div>
                        <div className="w-full shrink-0 md:w-56">
                            <Select value={kategori} onValueChange={setKategori}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Semua kategori" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="semua">Semua kategori</SelectItem>
                                    {kategoris.map((item) => (
                                        <SelectItem key={item} value={item}>
                                            {item}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    {filtered.length > 0 ? (
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                            {bukuPageItems.map((item) => {
                                const aktif = pengajuanAktif(item.id);
                                const habis = item.stok <= 0;

                                const statusLabel = aktif
                                    ? aktif.status === "menunggu"
                                        ? "Menunggu ACC"
                                        : "Sedang Dipinjam"
                                    : habis
                                      ? "Stok Habis"
                                      : null;

                                return (
                                    <Link
                                        key={item.id}
                                        href={route("buku-detail", { buku: item.id })}
                                        title={`Lihat detail ${item.judul}`}
                                        className="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white text-left shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg"
                                    >
                                        <div className="relative w-full overflow-hidden bg-slate-100">
                                            {item.cover_url ? (
                                                <img
                                                    src={item.cover_url}
                                                    alt={item.judul}
                                                    className="block h-auto w-full transition-transform duration-300 group-hover:scale-105"
                                                    loading="lazy"
                                                />
                                            ) : (
                                                <div className="flex aspect-[3/4] w-full items-center justify-center">
                                                    <BookOpen className="size-8 text-slate-300" />
                                                </div>
                                            )}

                                            {statusLabel && (
                                                <div className="absolute inset-x-0 bottom-0 bg-slate-900/70 px-2 py-1 text-center text-[10px] font-semibold uppercase tracking-wide text-white">
                                                    {statusLabel}
                                                </div>
                                            )}
                                        </div>

                                        <div className="p-2.5">
                                            <p className="line-clamp-2 text-sm font-semibold leading-snug text-slate-800">
                                                {item.judul}
                                            </p>
                                        </div>
                                    </Link>
                                );
                            })}
                        </div>
                    ) : (
                        <div className="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500 shadow-sm">
                            Tidak ada buku yang sesuai dengan pencarian.
                        </div>
                    )}

                    {filtered.length > BUKU_PER_PAGE && (
                        <div className="mt-4 flex items-center justify-between gap-3">
                            <p className="text-sm text-slate-500">
                                Halaman {bukuCurrentPage} dari {bukuTotalPages}
                            </p>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="gap-1"
                                    disabled={bukuCurrentPage <= 1}
                                    onClick={() => setBukuPage(bukuCurrentPage - 1)}
                                >
                                    <ChevronLeft className="size-4" />
                                    Sebelumnya
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    className="gap-1"
                                    disabled={bukuCurrentPage >= bukuTotalPages}
                                    onClick={() => setBukuPage(bukuCurrentPage + 1)}
                                >
                                    Berikutnya
                                    <ChevronRight className="size-4" />
                                </Button>
                            </div>
                        </div>
                    )}

                    <div className="mb-10 mt-10">
                        <Heading title="Peminjaman Saya" description="Riwayat pengajuan dan peminjaman buku kamu." />
                        <div className="mb-3 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
                            <p className="text-sm text-slate-500">
                                Menampilkan {riwayatPageItems.length} dari {riwayatFiltered.length} peminjaman
                                {riwayatFilter !== "semua" ? " (sesuai filter)" : ""}.
                            </p>
                            <div className="flex w-full flex-col gap-2 sm:flex-row md:w-auto">
                                <div className="w-full sm:w-56">
                                    <Select
                                        value={riwayatFilter}
                                        onValueChange={(value) => ubahFilterRiwayat(value as RiwayatFilter)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Semua status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="semua">Semua status</SelectItem>
                                            <SelectItem value="menunggu">Menunggu ACC</SelectItem>
                                            <SelectItem value="dipinjam">Dipinjam</SelectItem>
                                            <SelectItem value="terlambat">Terlambat</SelectItem>
                                            <SelectItem value="tepat_waktu">Selesai tepat waktu</SelectItem>
                                            <SelectItem value="selesai_terlambat">Selesai terlambat</SelectItem>
                                            <SelectItem value="ditolak">Ditolak</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="w-full sm:w-56">
                                    <Select
                                        value={riwayatUrutan}
                                        onValueChange={(value) => {
                                            setRiwayatUrutan(value as RiwayatUrutan);
                                            setRiwayatPage(1);
                                        }}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Status prioritas" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="prioritas">Status prioritas</SelectItem>
                                            <SelectItem value="tanggal_baru">Tanggal terbaru</SelectItem>
                                            <SelectItem value="tanggal_lama">Tanggal terlama</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>
                        <div className="overflow-hidden rounded-md border bg-white">
                            <Table>
                                <TableHeader className="bg-accent">
                                    <TableRow>
                                        <TableHead className="text-center">No</TableHead>
                                        <TableHead>Buku</TableHead>
                                        <TableHead>Tgl Pengajuan</TableHead>
                                        <TableHead>Jatuh Tempo</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-center">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {riwayatPageItems.length > 0 ? (
                                        riwayatPageItems.map((item, index) => (
                                            <TableRow key={item.id} className={item.is_terlambat ? "bg-red-50/60" : ""}>
                                                <TableCell className="text-center">
                                                    {(currentPage - 1) * PER_PAGE + index + 1}
                                                </TableCell>
                                                <TableCellWrap className="font-medium">
                                                    {item.buku?.judul ?? "-"}
                                                </TableCellWrap>
                                                <TableCellWrap>{date_format(item.tanggal_pengajuan)}</TableCellWrap>
                                                <TableCellWrap>{date_format(item.tanggal_jatuh_tempo ?? "")}</TableCellWrap>
                                                <TableCell>
                                                    <StatusBadge item={item} />
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {item.status === "menunggu" ? (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            className="gap-1"
                                                            onClick={() => batalkan(item)}
                                                            disabled={cancellingId !== null}
                                                        >
                                                            {cancellingId === item.id ? (
                                                                <>
                                                                    <Loader2 className="size-4 animate-spin" />
                                                                    Membatalkan...
                                                                </>
                                                            ) : (
                                                                "Batalkan"
                                                            )}
                                                        </Button>
                                                    ) : (
                                                        <span className="text-xs text-slate-400">-</span>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={6} className="h-16 text-center text-slate-500">
                                                {riwayatFilter === "semua"
                                                    ? "Belum ada peminjaman."
                                                    : "Tidak ada peminjaman dengan status ini."}
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                        {riwayatFiltered.length > PER_PAGE && (
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
                                        onClick={() => setRiwayatPage(currentPage - 1)}
                                    >
                                        <ChevronLeft className="size-4" />
                                        Sebelumnya
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="gap-1"
                                        disabled={currentPage >= totalPages}
                                        onClick={() => setRiwayatPage(currentPage + 1)}
                                    >
                                        Berikutnya
                                        <ChevronRight className="size-4" />
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>

                </div>
            </div>
        </AppLayout>
    );
}

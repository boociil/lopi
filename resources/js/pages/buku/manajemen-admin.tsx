import AppLayout from "@/layouts/app-layout";
import Heading from "@/components/heading";
import { Head, usePage } from "@inertiajs/react";
import { type BukuAdminLog, type BreadcrumbItem, type RoleBuku, type SharedData, type User } from "@/types";
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
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from "@/components/ui/command";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { Table, TableBody, TableCell, TableCellWrap, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { useApi } from "@/lib/API/useApi";
import { date_format } from "@/lib/date";
import { Check, ChevronLeft, ChevronRight, ChevronsUpDown, Loader2, ShieldCheck, UserPlus, Trash2 } from "lucide-react";
import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";

const LOGS_PER_PAGE = 10;

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Buku", href: "/lopi/buku" },
    { title: "Manajemen Admin", href: "/lopi/buku/admin" },
];

function RoleBadge({ role }: { role?: RoleBuku }) {
    if (role === "super_admin") {
        return (
            <Badge variant="default" className="gap-1">
                <ShieldCheck className="size-3" />
                Super Admin
            </Badge>
        );
    }

    if (role === "admin_buku") {
        return <Badge variant="secondary">Admin Buku</Badge>;
    }

    return <Badge variant="outline">Umum</Badge>;
}

export default function ManajemenAdmin({
    admins,
    users,
    logs,
    role_buku,
    super_admin_count,
}: {
    admins: User[];
    users: User[];
    logs: BukuAdminLog[];
    role_buku: RoleBuku;
    super_admin_count: number;
}) {
    const axiosClient = useApi();
    const auth = usePage<SharedData>().props.auth;

    const [adminList, setAdminList] = useState<User[]>(admins);
    const [userList, setUserList] = useState<User[]>(users);
    const [superAdminCount, setSuperAdminCount] = useState(super_admin_count);
    const [myRole, setMyRole] = useState<RoleBuku>(role_buku);
    const [selected, setSelected] = useState("");
    const [userPickerOpen, setUserPickerOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [logPage, setLogPage] = useState(1);
    const [logList, setLogList] = useState<BukuAdminLog[]>(logs);

    const logTotalPages = Math.max(1, Math.ceil(logList.length / LOGS_PER_PAGE));
    const logCurrentPage = Math.min(logPage, logTotalPages);
    const logPageItems = useMemo(
        () => logList.slice((logCurrentPage - 1) * LOGS_PER_PAGE, logCurrentPage * LOGS_PER_PAGE),
        [logList, logCurrentPage],
    );

    // Ambil ulang riwayat tanpa reload halaman.
    function refreshLogs() {
        axiosClient
            .get(route("buku-admin-logs"))
            .then((response) => {
                const data = response.data?.logs as BukuAdminLog[] | undefined;
                if (Array.isArray(data)) {
                    setLogList(data);
                    setLogPage(1);
                }
            })
            .catch(() => {
                // Diamkan saja; riwayat akan ikut segar saat halaman dibuka ulang.
            });
    }

    function hapusLog(id: number) {
        axiosClient
            .post(route("buku-admin-logs-hapus", { id }))
            .then((response) => {
                toast.success(response.data?._status ?? "Riwayat berhasil dihapus.");
                refreshLogs();
            })
            .catch((error) => {
                const message =
                    error?.response?.data?.errors?._status?.[0] ??
                    error?.response?.data?.message ??
                    "Gagal menghapus riwayat.";
                toast.error(message);
            });
    }

    // Sinkronkan daftar admin/pegawai saat server mengirim data terbaru.
    useEffect(() => {
        setAdminList(admins);
        setUserList(users);
        setSuperAdminCount(super_admin_count);
        setLogList(logs);
        setMyRole(role_buku);
    }, [admins, users, super_admin_count, logs, role_buku]);

    const isSuperAdmin = myRole === "super_admin";

    function jadikanAdmin() {
        if (!selected || loading) return;
        setLoading(true);

        axiosClient
            .post(route("buku-admin-jadikan"), { username: selected })
            .then((response) => {
                const saved = response.data?.data as User;
                const target = userList.find((item) => item.username === selected);
                if (target && saved) {
                    setAdminList((prev) =>
                        [...prev, saved].sort((a, b) => a.nama.localeCompare(b.nama)),
                    );
                    setUserList((prev) => prev.filter((item) => item.username !== selected));
                }
                setSelected("");
                toast.success(response.data?._status ?? "Berhasil menjadikan admin buku.");
                refreshLogs();
            })
            .catch((error) => {
                const message =
                    error?.response?.data?.errors?._status?.[0] ??
                    error?.response?.data?.message ??
                    "Gagal menjadikan admin buku.";
                toast.error(message);
            })
            .finally(() => setLoading(false));
    }

    function jadikanSuperAdmin(item: User) {
        if (loading) return;
        setLoading(true);

        axiosClient
            .post(route("buku-admin-jadikan-super"), { username: item.username })
            .then((response) => {
                const saved = response.data?.data as User;
                if (saved) {
                    setAdminList((prev) => prev.map((row) => (row.username === saved.username ? saved : row)));
                    if (item.role_buku !== "super_admin") {
                        setSuperAdminCount((prev) => prev + 1);
                    }
                }
                toast.success(response.data?._status ?? "Berhasil menjadikan super admin buku.");
                refreshLogs();
            })
            .catch((error) => {
                const message =
                    error?.response?.data?.errors?._status?.[0] ??
                    error?.response?.data?.message ??
                    "Gagal menjadikan super admin buku.";
                toast.error(message);
            })
            .finally(() => setLoading(false));
    }

    function cabutAdmin(item: User) {
        if (loading) return;
        setLoading(true);

        axiosClient
            .post(route("buku-admin-cabut"), { username: item.username })
            .then((response) => {
                const saved = response.data?.data as User;
                setAdminList((prev) => prev.filter((row) => row.username !== item.username));
                if (saved) {
                    setUserList((prev) =>
                        [...prev, saved].sort((a, b) => a.nama.localeCompare(b.nama)),
                    );
                }
                toast.success(response.data?._status ?? "Akses admin buku dicabut.");
                refreshLogs();
            })
            .catch((error) => {
                const message =
                    error?.response?.data?.errors?._status?.[0] ??
                    error?.response?.data?.message ??
                    "Gagal mencabut akses admin buku.";
                toast.error(message);
            })
            .finally(() => setLoading(false));
    }

    function turunkanDiri() {
        if (loading) return;
        setLoading(true);

        axiosClient
            .post(route("buku-admin-turunkan-super"))
            .then((response) => {
                const nama = auth.user.username;
                setMyRole("admin_buku");
                setSuperAdminCount((prev) => Math.max(0, prev - 1));
                setAdminList((prev) =>
                    prev.map((row) => (row.username === nama ? { ...row, role_buku: "admin_buku" } : row)),
                );
                toast.success(response.data?._status ?? "Kamu sekarang menjadi admin buku.");
                refreshLogs();
                setLoading(false);
            })
            .catch((error) => {
                const message =
                    error?.response?.data?.errors?._status?.[0] ??
                    error?.response?.data?.message ??
                    "Gagal menurunkan status super admin.";
                toast.error(message);
                setLoading(false);
            });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Manajemen Admin Buku" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="w-full">
                    <Heading
                        title="Manajemen Admin Buku"
                        description="Kelola siapa saja yang menjadi admin pada fitur peminjaman buku."
                    />

                    {isSuperAdmin ? (
                        <div className="mb-6 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-end">
                            <div className="w-full flex-1">
                                <p className="mb-2 text-sm font-medium text-slate-700">Jadikan Admin Buku</p>
                                <Popover open={userPickerOpen} onOpenChange={setUserPickerOpen}>
                                    <PopoverTrigger asChild>
                                        <Button
                                            variant="outline"
                                            role="combobox"
                                            aria-expanded={userPickerOpen}
                                            className="w-full justify-between font-normal"
                                            disabled={userList.length === 0}
                                        >
                                            <span className="truncate">
                                                {selected
                                                    ? (() => {
                                                          const picked = userList.find((item) => item.username === selected);
                                                          return picked
                                                              ? `${picked.nama} (${picked.username})`
                                                              : "Pilih pegawai...";
                                                      })()
                                                    : userList.length > 0
                                                      ? "Pilih pegawai..."
                                                      : "Tidak ada pegawai yang bisa ditambahkan"}
                                            </span>
                                            <ChevronsUpDown className="size-4 shrink-0 opacity-50" />
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent
                                        className="w-[var(--radix-popover-trigger-width)] min-w-72 p-0"
                                        align="start"
                                    >
                                        <Command>
                                            <CommandInput placeholder="Cari nama atau username..." className="h-9" />
                                            <CommandList>
                                                <CommandEmpty>Tidak ditemukan.</CommandEmpty>
                                                <CommandGroup>
                                                    {userList.map((item) => (
                                                        <CommandItem
                                                            key={item.username}
                                                            value={`${item.nama} ${item.username}`}
                                                            onSelect={() => {
                                                                setSelected(item.username);
                                                                setUserPickerOpen(false);
                                                            }}
                                                        >
                                                            <Check
                                                                className={`size-4 ${
                                                                    selected === item.username
                                                                        ? "opacity-100"
                                                                        : "opacity-0"
                                                                }`}
                                                            />
                                                            <div className="flex flex-col">
                                                                <span>{item.nama}</span>
                                                                <span className="text-xs text-slate-500">
                                                                    {item.username}
                                                                    {item.jabatan ? ` • ${item.jabatan}` : ""}
                                                                </span>
                                                            </div>
                                                        </CommandItem>
                                                    ))}
                                                </CommandGroup>
                                            </CommandList>
                                        </Command>
                                    </PopoverContent>
                                </Popover>
                            </div>
                            <Button
                                className="flex items-center gap-1"
                                disabled={!selected || loading}
                                onClick={jadikanAdmin}
                            >
                                {loading ? <Loader2 className="size-4 animate-spin" /> : <UserPlus className="size-4" />}
                                Jadikan Admin
                            </Button>
                        </div>
                    ) : (
                        <div className="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
                            Kamu dapat melihat daftar admin buku. Perubahan admin hanya dapat dilakukan oleh
                            super admin.
                        </div>
                    )}

                    <div className="overflow-hidden rounded-md border bg-white">
                        <Table>
                            <TableHeader className="bg-accent">
                                <TableRow>
                                    <TableHead className="text-center">No</TableHead>
                                    <TableHead>Nama</TableHead>
                                    <TableHead>Username</TableHead>
                                    <TableHead>Jabatan</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead className="text-center">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {adminList.length > 0 ? (
                                    adminList.map((item, index) => {
                                        const isSelf = auth.user.username === item.username;
                                        const canDowngradeSelf =
                                            isSuperAdmin && isSelf && item.role_buku === "super_admin" && superAdminCount > 1;
                                        const canRevoke = isSuperAdmin && item.role_buku === "admin_buku";
                                        const canPromote = isSuperAdmin && item.role_buku === "admin_buku";

                                        return (
                                            <TableRow key={item.username}>
                                                <TableCell className="text-center">{index + 1}</TableCell>
                                                <TableCellWrap className="font-medium">{item.nama}</TableCellWrap>
                                                <TableCellWrap>{item.username}</TableCellWrap>
                                                <TableCellWrap>{item.jabatan || "-"}</TableCellWrap>
                                                <TableCell>
                                                    <RoleBadge role={item.role_buku} />
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <div className="flex flex-wrap items-center justify-center gap-2">
                                                        {canPromote && (
                                                            <AlertDialog>
                                                                <AlertDialogTrigger asChild>
                                                                    <Button
                                                                        variant="default"
                                                                        size="sm"
                                                                        className="gap-1"
                                                                        disabled={loading}
                                                                    >
                                                                        <ShieldCheck className="size-3.5" />
                                                                        Jadikan Super Admin
                                                                    </Button>
                                                                </AlertDialogTrigger>
                                                                <AlertDialogContent>
                                                                    <AlertDialogHeader>
                                                                        <AlertDialogTitle>
                                                                            Jadikan {item.nama} sebagai super admin?
                                                                        </AlertDialogTitle>
                                                                        <AlertDialogDescription>
                                                                            Super admin tidak dapat dicabut oleh siapa
                                                                            pun, hanya bisa menurunkan dirinya sendiri.
                                                                            Lakukan ini hanya untuk mengangkat pengganti
                                                                            sebelum kamu pindah tugas.
                                                                        </AlertDialogDescription>
                                                                    </AlertDialogHeader>
                                                                    <AlertDialogFooter>
                                                                        <AlertDialogCancel>Batal</AlertDialogCancel>
                                                                        <AlertDialogAction onClick={() => jadikanSuperAdmin(item)}>
                                                                            Jadikan Super Admin
                                                                        </AlertDialogAction>
                                                                    </AlertDialogFooter>
                                                                </AlertDialogContent>
                                                            </AlertDialog>
                                                        )}
                                                        {canRevoke && (
                                                            <AlertDialog>
                                                                <AlertDialogTrigger asChild>
                                                                    <Button variant="outline" size="sm" disabled={loading}>
                                                                        Cabut Admin
                                                                    </Button>
                                                                </AlertDialogTrigger>
                                                                <AlertDialogContent>
                                                                    <AlertDialogHeader>
                                                                        <AlertDialogTitle>Cabut akses admin buku?</AlertDialogTitle>
                                                                        <AlertDialogDescription>
                                                                            {item.nama} tidak akan bisa mengakses Pinjaman Buku,
                                                                            Master Buku, dan Manajemen Admin lagi.
                                                                        </AlertDialogDescription>
                                                                    </AlertDialogHeader>
                                                                    <AlertDialogFooter>
                                                                        <AlertDialogCancel>Batal</AlertDialogCancel>
                                                                        <AlertDialogAction onClick={() => cabutAdmin(item)}>
                                                                            Cabut
                                                                        </AlertDialogAction>
                                                                    </AlertDialogFooter>
                                                                </AlertDialogContent>
                                                            </AlertDialog>
                                                        )}
                                                        {canDowngradeSelf && (
                                                            <AlertDialog>
                                                                <AlertDialogTrigger asChild>
                                                                    <Button variant="outline" size="sm" disabled={loading}>
                                                                        Turunkan Diri
                                                                    </Button>
                                                                </AlertDialogTrigger>
                                                                <AlertDialogContent>
                                                                    <AlertDialogHeader>
                                                                        <AlertDialogTitle>Turunkan status super admin?</AlertDialogTitle>
                                                                        <AlertDialogDescription>
                                                                            Kamu akan menjadi admin buku biasa. Pastikan masih ada
                                                                            super admin lain sebelum melanjutkan.
                                                                        </AlertDialogDescription>
                                                                    </AlertDialogHeader>
                                                                    <AlertDialogFooter>
                                                                        <AlertDialogCancel>Batal</AlertDialogCancel>
                                                                        <AlertDialogAction onClick={turunkanDiri}>
                                                                            Turunkan
                                                                        </AlertDialogAction>
                                                                    </AlertDialogFooter>
                                                                </AlertDialogContent>
                                                            </AlertDialog>
                                                        )}
                                                        {!canPromote && !canRevoke && !canDowngradeSelf && (
                                                            <span className="text-xs text-slate-400">-</span>
                                                        )}
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={6} className="h-16 text-center text-slate-500">
                                            Belum ada admin buku.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    <div className="mt-10">
                        <Heading
                            title="Riwayat Perubahan Admin"
                            description="30 perubahan terakhir pada admin buku, 10 per halaman."
                        />
                        <div className="overflow-hidden rounded-md border bg-white">
                            <Table>
                                <TableHeader className="bg-accent">
                                    <TableRow>
                                        <TableHead className="text-center">No</TableHead>
                                        <TableHead>Waktu</TableHead>
                                        <TableHead>Aktor</TableHead>
                                        <TableHead>Target</TableHead>
                                        <TableHead>Keterangan</TableHead>
                                        <TableHead className="text-center">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {logPageItems.length > 0 ? (
                                        logPageItems.map((item, index) => (
                                            <TableRow key={item.id}>
                                                <TableCell className="text-center">
                                                    {(logCurrentPage - 1) * LOGS_PER_PAGE + index + 1}
                                                </TableCell>
                                                <TableCellWrap>{date_format(item.created_at ?? "")}</TableCellWrap>
                                                <TableCellWrap>{item.actor_nama ?? item.actor_username}</TableCellWrap>
                                                <TableCellWrap>{item.target_nama ?? item.target_username}</TableCellWrap>
                                                <TableCellWrap>{item.keterangan ?? item.aksi}</TableCellWrap>
                                                <TableCell className="text-center">
                                                    <AlertDialog>
                                                        <AlertDialogTrigger asChild>
                                                            <Button variant="destructive" size="sm" className="gap-1">
                                                                <Trash2 className="size-4" />
                                                            </Button>
                                                        </AlertDialogTrigger>
                                                        <AlertDialogContent>
                                                            <AlertDialogHeader>
                                                                <AlertDialogTitle>Hapus riwayat?</AlertDialogTitle>
                                                                <AlertDialogDescription>
                                                                    Riwayat "{item.keterangan ? item.keterangan : item.aksi}" akan dihapus. Tindakan ini tidak dapat dibatalkan.
                                                                </AlertDialogDescription>
                                                            </AlertDialogHeader>
                                                            <AlertDialogFooter>
                                                                <AlertDialogCancel>Batal</AlertDialogCancel>
                                                                <AlertDialogAction onClick={() => hapusLog(item.id)}>Hapus</AlertDialogAction>
                                                            </AlertDialogFooter>
                                                        </AlertDialogContent>
                                                    </AlertDialog>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={6} className="h-16 text-center text-slate-500">
                                                Belum ada riwayat perubahan admin.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                        {logs.length > LOGS_PER_PAGE && (
                            <div className="mt-3 flex items-center justify-between gap-3">
                                <p className="text-sm text-slate-500">
                                    Halaman {logCurrentPage} dari {logTotalPages}
                                </p>
                                <div className="flex items-center gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="gap-1"
                                        disabled={logCurrentPage <= 1}
                                        onClick={() => setLogPage(logCurrentPage - 1)}
                                    >
                                        <ChevronLeft className="size-4" />
                                        Sebelumnya
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="gap-1"
                                        disabled={logCurrentPage >= logTotalPages}
                                        onClick={() => setLogPage(logCurrentPage + 1)}
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

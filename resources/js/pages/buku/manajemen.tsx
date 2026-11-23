import AppLayout from "@/layouts/app-layout";
import Heading from "@/components/heading";
import { Head } from "@inertiajs/react";
import { type BreadcrumbItem, type Buku } from "@/types";
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
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Table, TableBody, TableCell, TableCellWrap, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Textarea } from "@/components/ui/textarea";
import { useApi } from "@/lib/API/useApi";
import { BookOpen, Loader2, Pencil, Plus, Search, Trash2 } from "lucide-react";
import { useMemo, useEffect, useState, type ChangeEvent, type FormEvent } from "react";
import { toast } from "sonner";

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Buku", href: "/lopi/buku" },
    { title: "Master Buku", href: "/lopi/buku/manajemen" },
];

interface BukuForm {
    judul: string;
    pengarang: string;
    jumlah_halaman: string;
    tahun_terbit: string;
    bahasa: string;
    kategori: string;
    sinopsis: string;
    stok: string;
}

const emptyForm: BukuForm = {
    judul: "",
    pengarang: "",
    jumlah_halaman: "",
    tahun_terbit: "",
    bahasa: "",
    kategori: "",
    sinopsis: "",
    stok: "1",
};

function potongJudul(judul: string, max = 70) {
    return judul.length > max ? `${judul.slice(0, max)}...` : judul;
}

export default function ManajemenBuku({ buku }: { buku: Buku[] }) {
    const axiosClient = useApi();
    const [list, setList] = useState<Buku[]>(buku);
    const [search, setSearch] = useState("");
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<Buku | null>(null);
    const [form, setForm] = useState<BukuForm>(emptyForm);
    const [coverFile, setCoverFile] = useState<File | null>(null);
    const [coverPreview, setCoverPreview] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);

    // Ambil ulang katalog saat server mengirim data terbaru.
    useEffect(() => {
        setList(buku);
    }, [buku]);

    const filtered = useMemo(() => {
        const q = search.toLowerCase();
        if (!q) return list;
        return list.filter(
            (item) =>
                item.judul.toLowerCase().includes(q) ||
                (item.pengarang ?? "").toLowerCase().includes(q) ||
                (item.kategori ?? "").toLowerCase().includes(q),
        );
    }, [list, search]);

    function setField(key: keyof BukuForm, value: string) {
        setForm((prev) => ({ ...prev, [key]: value }));
    }

    function openTambah() {
        setEditing(null);
        setForm(emptyForm);
        setCoverFile(null);
        setCoverPreview(null);
        setDialogOpen(true);
    }

    function openUbah(item: Buku) {
        setEditing(item);
        setForm({
            judul: item.judul ?? "",
            pengarang: item.pengarang ?? "",
            jumlah_halaman: item.jumlah_halaman ? String(item.jumlah_halaman) : "",
            tahun_terbit: item.tahun_terbit ? String(item.tahun_terbit) : "",
            bahasa: item.bahasa ?? "",
            kategori: item.kategori ?? "",
            sinopsis: item.sinopsis ?? "",
            stok: String(item.stok ?? 0),
        });
        setCoverFile(null);
        setCoverPreview(item.cover_url);
        setDialogOpen(true);
    }

    function onCoverChange(event: ChangeEvent<HTMLInputElement>) {
        const file = event.target.files?.[0] ?? null;

        if (file && file.size > 500 * 1024) {
            toast.error("Ukuran cover maksimal 500 KB.");
            event.target.value = "";
            setCoverFile(null);
            setCoverPreview(null);
            return;
        }

        setCoverFile(file);
        setCoverPreview(file ? URL.createObjectURL(file) : null);
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        if (submitting) return;
        setSubmitting(true);

        const data = new FormData();
        data.append("judul", form.judul);
        data.append("pengarang", form.pengarang);
        data.append("jumlah_halaman", form.jumlah_halaman);
        data.append("tahun_terbit", form.tahun_terbit);
        data.append("bahasa", form.bahasa);
        data.append("kategori", form.kategori);
        data.append("sinopsis", form.sinopsis);
        data.append("stok", form.stok);

        if (coverFile) {
            data.append("cover", coverFile);
        }

        const url = editing ? route("buku-ubah", { buku: editing.id }) : route("buku-tambah");

        axiosClient
            .post(url, data)
            .then((response) => {
                const saved = response.data?.data as Buku;
                if (saved) {
                    setList((prev) => {
                        if (editing) {
                            return prev.map((item) => (item.id === saved.id ? saved : item));
                        }
                        return [...prev, saved].sort((a, b) => a.judul.localeCompare(b.judul));
                    });
                }
                toast.success(response.data?._status ?? "Buku berhasil disimpan.");
                setDialogOpen(false);
            })
            .catch((error) => {
                const errors = error?.response?.data?.errors ?? {};
                const firstError = Object.values(errors)[0] as string[] | undefined;
                toast.error(firstError?.[0] ?? error?.response?.data?.message ?? "Gagal menyimpan buku.");
            })
            .finally(() => setSubmitting(false));
    }

    function hapus(item: Buku) {
        axiosClient
            .post(route("buku-hapus", { buku: item.id }))
            .then((response) => {
                setList((prev) => prev.filter((row) => row.id !== item.id));
                toast.success(response.data?._status ?? "Buku berhasil dihapus.");
            })
            .catch((error) => {
                const message =
                    error?.response?.data?.errors?._status?.[0] ??
                    error?.response?.data?.message ??
                    "Gagal menghapus buku.";
                toast.error(message);
            });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Master Buku" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="w-full">
                    <Heading
                        title="Master Buku"
                        description="Tambah, ubah, dan hapus katalog buku yang tampil di List Buku."
                    />

                    <div className="mb-4 flex flex-col gap-3 md:flex-row md:items-center">
                        <div className="relative w-full flex-1">
                            <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                            <Input
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder="Cari buku..."
                                className="pl-9"
                            />
                        </div>
                        <Button className="flex shrink-0 items-center gap-1 bg-primary text-primary-foreground" onClick={openTambah}>
                            <Plus />
                            Tambah Buku
                        </Button>
                    </div>

                    <div className="overflow-hidden rounded-md border bg-white">
                        <Table>
                            <TableHeader className="bg-accent">
                                <TableRow>
                                    <TableHead className="text-center">No</TableHead>
                                    <TableHead>Cover</TableHead>
                                    <TableHead>Judul</TableHead>
                                    <TableHead>Penulis</TableHead>
                                    <TableHead className="text-center">Tahun</TableHead>
                                    <TableHead>Bahasa</TableHead>
                                    <TableHead className="text-center">Halaman</TableHead>
                                    <TableHead className="text-center">Stok</TableHead>
                                    <TableHead className="text-center">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {filtered.length > 0 ? (
                                    filtered.map((item, index) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="text-center">{index + 1}</TableCell>
                                            <TableCell>
                                                <div className="flex h-14 w-10 items-center justify-center overflow-hidden rounded-md border border-slate-200 bg-slate-50">
                                                    {item.cover_url ? (
                                                        <img
                                                            src={item.cover_url}
                                                            alt={item.judul}
                                                            className="h-full w-full object-contain"
                                                        />
                                                    ) : (
                                                        <BookOpen className="size-5 text-slate-300" />
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCellWrap className="font-medium">
                                                <span title={item.judul}>{potongJudul(item.judul)}</span>
                                            </TableCellWrap>
                                            <TableCellWrap>{item.pengarang || "-"}</TableCellWrap>
                                            <TableCell className="text-center">{item.tahun_terbit ?? "-"}</TableCell>
                                            <TableCellWrap>{item.bahasa || "-"}</TableCellWrap>
                                            <TableCell className="text-center">
                                                {item.jumlah_halaman ?? "-"}
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <Badge variant={item.stok > 0 ? "success_" : "destructive_"}>
                                                    {item.stok}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-center">
                                                <div className="flex items-center justify-center gap-2">
                                                    <Button variant="outline" size="sm" onClick={() => openUbah(item)}>
                                                        <Pencil className="size-4" />
                                                        Ubah
                                                    </Button>
                                                    <AlertDialog>
                                                        <AlertDialogTrigger asChild>
                                                            <Button variant="outline" size="sm">
                                                                <Trash2 className="size-4" />
                                                                Hapus
                                                            </Button>
                                                        </AlertDialogTrigger>
                                                        <AlertDialogContent>
                                                            <AlertDialogHeader>
                                                                <AlertDialogTitle>Hapus buku?</AlertDialogTitle>
                                                                <AlertDialogDescription>
                                                                    Buku "{item.judul}" akan dihapus dari katalog. Tindakan ini
                                                                    tidak dapat dibatalkan.
                                                                </AlertDialogDescription>
                                                            </AlertDialogHeader>
                                                            <AlertDialogFooter>
                                                                <AlertDialogCancel>Batal</AlertDialogCancel>
                                                                <AlertDialogAction onClick={() => hapus(item)}>
                                                                    Hapus
                                                                </AlertDialogAction>
                                                            </AlertDialogFooter>
                                                        </AlertDialogContent>
                                                    </AlertDialog>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={9} className="h-16 text-center text-slate-500">
                                            Belum ada buku.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </div>

            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>{editing ? "Ubah Buku" : "Tambah Buku"}</DialogTitle>
                    </DialogHeader>

                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="judul">Judul *</Label>
                                <Input
                                    id="judul"
                                    value={form.judul}
                                    onChange={(event) => setField("judul", event.target.value)}
                                    required
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="pengarang">Penulis</Label>
                                <Input
                                    id="pengarang"
                                    value={form.pengarang}
                                    onChange={(event) => setField("pengarang", event.target.value)}
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="jumlah_halaman">Jumlah Halaman</Label>
                                <Input
                                    id="jumlah_halaman"
                                    type="number"
                                    min={1}
                                    value={form.jumlah_halaman}
                                    onChange={(event) => setField("jumlah_halaman", event.target.value)}
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="tahun_terbit">Tahun Terbit</Label>
                                <Input
                                    id="tahun_terbit"
                                    type="number"
                                    value={form.tahun_terbit}
                                    onChange={(event) => setField("tahun_terbit", event.target.value)}
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="bahasa">Bahasa</Label>
                                <Input
                                    id="bahasa"
                                    value={form.bahasa}
                                    onChange={(event) => setField("bahasa", event.target.value)}
                                    placeholder="Indonesia"
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="kategori">Kategori</Label>
                                <Input
                                    id="kategori"
                                    value={form.kategori}
                                    onChange={(event) => setField("kategori", event.target.value)}
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="stok">Stok *</Label>
                                <Input
                                    id="stok"
                                    type="number"
                                    min={0}
                                    value={form.stok}
                                    onChange={(event) => setField("stok", event.target.value)}
                                    required
                                />
                            </div>

                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="sinopsis">Sinopsis</Label>
                                <Textarea
                                    id="sinopsis"
                                    value={form.sinopsis}
                                    onChange={(event) => setField("sinopsis", event.target.value)}
                                    placeholder="Tulis sinopsis buku di sini..."
                                    rows={6}
                                    className="whitespace-pre-wrap"
                                />
                                <p className="text-xs text-slate-400">
                                    Tekan Enter untuk membuat baris baru. Pisahkan dengan satu baris kosong
                                    untuk membuat paragraf baru (boleh lebih dari satu paragraf).
                                </p>
                            </div>

                            <div className="space-y-2 md:col-span-2">
                                <Label htmlFor="cover">Cover Buku</Label>
                                <div className="flex items-center gap-4">
                                    <div className="flex h-28 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                        {coverPreview ? (
                                            <img src={coverPreview} alt="Preview cover" className="h-full w-full object-contain" />
                                        ) : (
                                            <BookOpen className="size-6 text-slate-300" />
                                        )}
                                    </div>
                                    <Input id="cover" type="file" accept="image/*" onChange={onCoverChange} />
                                </div>
                                <p className="text-xs text-slate-400">
                                    Format JPG, JPEG, PNG, atau WEBP. Ukuran maksimal 500 KB.
                                </p>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>
                                Batal
                            </Button>
                            <Button type="submit" disabled={submitting}>
                                {submitting ? <Loader2 className="size-4 animate-spin" /> : "Simpan"}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

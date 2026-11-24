import AppLayout from "@/layouts/app-layout";
import Heading from "@/components/heading";
import { Head, router } from "@inertiajs/react";
import { type BreadcrumbItem, type Buku, type BukuPinjam } from "@/types";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useApi } from "@/lib/API/useApi";
import { refreshBukuNotif } from "@/lib/buku-notif";
import { ArrowLeft, BookOpen, Loader2 } from "lucide-react";
import { useEffect, useState } from "react";
import { toast } from "sonner";

function InfoRow({ label, value }: { label: string; value: string | number | null | undefined }) {
    return (
        <div className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
            <dt className="text-[11px] font-medium uppercase tracking-wide text-slate-500">{label}</dt>
            <dd className="mt-0.5 text-sm font-semibold text-slate-800">{value || value === 0 ? value : "-"}</dd>
        </div>
    );
}

function StatusInfo({ pinjam }: { pinjam: BukuPinjam }) {
    if (pinjam.is_terlambat) {
        return <Badge variant="destructive_">Terlambat {pinjam.hari_terlambat} hari</Badge>;
    }

    if (pinjam.status === "menunggu") {
        return <Badge variant="warning_">Menunggu Persetujuan Admin</Badge>;
    }

    if (pinjam.status === "dipinjam" && pinjam.sisa_hari !== null && pinjam.sisa_hari <= 3) {
        return (
            <Badge variant="warning_">
                {pinjam.sisa_hari === 0
                    ? "Jatuh tempo hari ini"
                    : pinjam.sisa_hari === 1
                      ? "Jatuh tempo besok"
                      : `Jatuh tempo ${pinjam.sisa_hari} hari lagi`}
            </Badge>
        );
    }

    return <Badge variant="default">Sedang Dipinjam</Badge>;
}

export default function DetailBuku({ buku, pinjam }: { buku: Buku; pinjam: BukuPinjam | null }) {
    const axiosClient = useApi();
    const [pengajuan, setPengajuan] = useState<BukuPinjam | null>(pinjam);
    const [loading, setLoading] = useState(false);
    const [durasi, setDurasi] = useState("7");
    const [durasiOpen, setDurasiOpen] = useState(false);

    // Sinkronkan status pinjaman saat server mengirim data terbaru.
    useEffect(() => {
        setPengajuan(pinjam);
    }, [pinjam]);

    const durasiAngka = Number(durasi);
    const durasiValid = Number.isInteger(durasiAngka) && durasiAngka >= 1 && durasiAngka <= 14;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: "Buku", href: "/lopi/buku" },
        { title: "List Buku", href: "/lopi/buku" },
        { title: buku.judul, href: `/lopi/buku/${buku.id}` },
    ];

    const habis = buku.stok <= 0;
    const tidakTersedia = Boolean(pengajuan) || habis;

    function ajukanPinjam() {
        if (loading || tidakTersedia || !durasiValid) return;
        setLoading(true);

        axiosClient
            .post(route("buku-pinjam", { buku: buku.id }), { durasi: durasiAngka })
            .then((response) => {
                toast.success(response.data?._status ?? "Pengajuan peminjaman berhasil dikirim.");
                const baru = response.data?.data as BukuPinjam | undefined;
                if (baru) {
                    setPengajuan(baru);
                }
                setDurasiOpen(false);
                refreshBukuNotif(1);
            })
            .catch((error) => {
                const message =
                    error?.response?.data?.errors?._status?.[0] ??
                    error?.response?.data?.message ??
                    "Gagal mengajukan peminjaman.";
                toast.error(message);
            })
            .finally(() => setLoading(false));
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={buku.judul} />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="w-full">
                    <Button variant="outline" size="sm" className="mb-4 gap-2" onClick={() => router.visit(route("buku-list"))}>
                        <ArrowLeft className="size-4" />
                        Kembali ke List Buku
                    </Button>

                    <Heading title="Detail Buku" description="Informasi lengkap buku dan pengajuan peminjaman." />

                    <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 lg:p-8">
                        <div className="flex flex-col gap-6 lg:grid lg:grid-cols-[300px_1fr] lg:gap-10">
                            {/* Cover: paling atas di mobile, kiri di desktop */}
                            <div className="mx-auto w-full max-w-[240px] sm:max-w-[280px] lg:mx-0 lg:max-w-none">
                                <div className="w-full overflow-hidden rounded-xl border border-slate-200 bg-slate-100 shadow-md">
                                    {buku.cover_url ? (
                                        <img src={buku.cover_url} alt={buku.judul} className="block h-auto w-full" />
                                    ) : (
                                        <div className="flex aspect-[3/4] w-full items-center justify-center">
                                            <BookOpen className="size-12 text-slate-300" />
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Informasi + tombol: bawah di mobile, kanan di desktop */}
                            <div className="flex min-w-0 flex-col gap-5">
                                <div>
                                    <h2 className="text-xl font-bold leading-snug text-slate-900 sm:text-2xl">
                                        {buku.judul}
                                    </h2>
                                    <p className="mt-1 text-sm text-slate-500">
                                        {buku.pengarang || "Penulis tidak diketahui"}
                                    </p>
                                    {pengajuan && (
                                        <div className="mt-2">
                                            <StatusInfo pinjam={pengajuan} />
                                        </div>
                                    )}
                                </div>

                                <dl className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                    <InfoRow label="Jumlah Halaman" value={buku.jumlah_halaman} />
                                    <InfoRow label="Tahun Terbit" value={buku.tahun_terbit} />
                                    <InfoRow label="Bahasa" value={buku.bahasa} />
                                    <InfoRow label="Stok" value={buku.stok} />
                                </dl>

                                <div>
                                    <p className="mb-1 text-sm font-semibold text-slate-800">Sinopsis</p>
                                    <p className="whitespace-pre-wrap text-justify text-sm leading-relaxed text-slate-600">
                                        {buku.sinopsis || "Belum ada sinopsis untuk buku ini."}
                                    </p>
                                </div>

                                {/* Tombol paling bawah */}
                                <div className="mt-auto flex flex-col gap-3 border-t border-slate-200 pt-4">
                                    <Button
                                        className="w-full gap-2"
                                        size="lg"
                                        disabled={tidakTersedia || loading}
                                        onClick={() => setDurasiOpen(true)}
                                    >
                                        {loading ? (
                                            <Loader2 className="size-4 animate-spin" />
                                        ) : (
                                            <BookOpen className="size-4" />
                                        )}
                                        {pengajuan
                                            ? pengajuan.status === "menunggu"
                                                ? "Menunggu Persetujuan Admin"
                                                : "Sedang Dipinjam"
                                            : habis
                                              ? "Stok Habis"
                                              : "Ajukan Pinjaman"}
                                    </Button>
                                    <p className="text-center text-xs text-slate-400">
                                        Jatuh tempo dihitung sejak pengajuan disetujui admin, sesuai durasi yang kamu
                                        pilih (maksimal 14 hari).
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Dialog open={durasiOpen} onOpenChange={(open) => !loading && setDurasiOpen(open)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Ajukan Pinjaman</DialogTitle>
                        <DialogDescription>
                            Tentukan lama peminjaman untuk buku{" "}
                            <span className="font-medium text-slate-700">{buku.judul}</span>.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-2">
                        <Label htmlFor="durasi">Durasi Pinjam (hari)</Label>
                        <Input
                            id="durasi"
                            type="number"
                            min={1}
                            max={14}
                            value={durasi}
                            onChange={(event) => setDurasi(event.target.value)}
                            className="w-full"
                            autoFocus
                        />
                        <p className="text-xs text-slate-400">Minimal 1 hari, maksimal 14 hari.</p>
                        {!durasiValid && (
                            <p className="text-xs font-medium text-red-500">
                                Durasi harus antara 1 sampai 14 hari.
                            </p>
                        )}
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDurasiOpen(false)} disabled={loading}>
                            Batal
                        </Button>
                        <Button onClick={ajukanPinjam} disabled={loading || !durasiValid} className="gap-2">
                            {loading ? <Loader2 className="size-4 animate-spin" /> : <BookOpen className="size-4" />}
                            Ajukan Pinjaman
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

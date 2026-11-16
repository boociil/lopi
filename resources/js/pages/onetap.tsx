import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";
import { Head, usePage } from "@inertiajs/react";
import { useEffect, useMemo, useState, type FormEvent, type ReactNode } from "react";
import {
    ArrowDown,
    ArrowUp,
    Building2,
    ChevronLeft,
    ChevronRight,
    ChevronsUpDown,
    Copy,
    Download,
    ExternalLink,
    Eye,
    Facebook,
    FileText,
    Globe,
    Instagram,
    Link as LinkIcon,
    Mail,
    MapPin,
    MessageCircle,
    Music2,
    Pencil,
    Phone,
    Plus,
    RefreshCcw,
    Save,
    Search,
    Send,
    Trash2,
    Twitter,
    Youtube,
    X,
    type LucideIcon,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Input } from "@/components/ui/input";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from "@/components/ui/command";
import { useLoading } from "@/props/LoadingContext";
import { useApi } from "@/lib/API/useApi";
import { toast } from "sonner";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "OneTap",
        href: "/lopi/onetap",
    },
];

type OneTapAccess = "public" | "private";
type OneTapVisibilitas = "publik" | "internal" | "privat";
type VisibilitasFilter = "all" | "internal" | "privat";
type OneTapTheme = "orange" | "blue" | "green";
type OneTapItemType = "link" | "divider";
type MainView = "list" | "trash";
type EditorMode = "add" | "edit";

type AuthUser = {
    id: number;
    username: string;
    nama: string;
};

type OneTapUser = {
    username: string;
    nama: string;
};

type OneTapTim = {
    id: string;
    nama: string;
    anggota: string[];
};

type SharedProps = {
    auth: { user: AuthUser | null };
};

interface OneTapButton {
    id?: string;
    tipe: OneTapItemType;
    judul: string;
    url: string;
    ikon: string;
    warna: string;
    aktif: boolean;
    urutan: number;
}

interface OneTapPengguna {
    id?: number;
    pengguna_username: string;
}

interface OneTapPage {
    id: string;
    judul: string;
    slug: string;
    deskripsi: string;
    akses: OneTapAccess;
    visibilitas: OneTapVisibilitas;
    tema: OneTapTheme;
    foto_url: string;
    warna_latar: string;
    pembuat_username?: string;
    satuan_kerja_tahun_id?: string | null;
    created_at?: string;
    updated_at?: string;
    deleted_at?: string | null;
    buttons: OneTapButton[];
    pengguna?: OneTapPengguna[];
    jumlah_tombol?: number;
}

interface OneTapFormData {
    id: string;
    judul: string;
    slug: string;
    deskripsi: string;
    akses: OneTapAccess;
    visibilitas: OneTapVisibilitas;
    tema: OneTapTheme;
    foto_url: string;
    warna_latar: string;
    buttons: OneTapButton[];
    pengguna: string[];
}

interface OneTapProps {
    data?: OneTapPage[];
    trash?: OneTapPage[];
    users?: OneTapUser[];
    userNames?: Record<string, string>;
    tim?: OneTapTim[];
}

interface ValidationErrors {
    [key: string]: string | undefined;
}

const EMPTY_FORM: OneTapFormData = {
    id: "",
    judul: "",
    slug: "",
    deskripsi: "",
    akses: "private",
    visibilitas: "privat",
    tema: "orange",
    foto_url: "",
    warna_latar: "",
    buttons: [],
    pengguna: [],
};

const VISIBILITAS_OPTIONS: Array<{
    value: OneTapVisibilitas;
    label: string;
    description: string;
}> = [
    { value: "publik", label: "Publik", description: "Semua pengguna dapat melihat." },
    { value: "internal", label: "Internal", description: "Hanya pengguna yang dipilih." },
    { value: "privat", label: "Privat", description: "Hanya pembuat OneTap." },
];

const THEME_OPTIONS: Array<{
    value: OneTapTheme;
    label: string;
    background: string;
}> = [
    { value: "orange", label: "Oranye", background: "linear-gradient(135deg, #febd26, #f79039)" },
    { value: "blue", label: "Biru", background: "linear-gradient(135deg, #00adef, #004aad)" },
    { value: "green", label: "Hijau", background: "linear-gradient(135deg, #8cc63e, #00bf63)" },
];

interface IconOption {
    value: string;
    label: string;
    icon: LucideIcon;
}

const ICON_OPTIONS: IconOption[] = [
    { value: "link", label: "Link", icon: LinkIcon },
    { value: "website", label: "Website", icon: Globe },
    { value: "instagram", label: "Instagram", icon: Instagram },
    { value: "facebook", label: "Facebook", icon: Facebook },
    { value: "youtube", label: "YouTube", icon: Youtube },
    { value: "tiktok", label: "TikTok", icon: Music2 },
    { value: "x", label: "X / Twitter", icon: Twitter },
    { value: "whatsapp", label: "WhatsApp", icon: MessageCircle },
    { value: "telegram", label: "Telegram", icon: Send },
    { value: "email", label: "Email", icon: Mail },
    { value: "phone", label: "Telepon", icon: Phone },
    { value: "location", label: "Lokasi", icon: MapPin },
    { value: "document", label: "Dokumen", icon: FileText },
    { value: "download", label: "Unduh", icon: Download },
    { value: "building", label: "Instansi", icon: Building2 },
];

export default function OneTap({ data = [], trash = [], users = [], userNames = {}, tim = [] }: OneTapProps) {
    const axiosClient = useApi();
    const { auth } = usePage<SharedProps>().props;
    const { setIsLoading } = useLoading();
    const username = auth.user?.username;
    const [pages, setPages] = useState<OneTapPage[]>(data);
    const [trashPages, setTrashPages] = useState<OneTapPage[]>(trash);
    const [view, setView] = useState<MainView>("list");
    const [searchQuery, setSearchQuery] = useState("");
    const [accessFilter, setAccessFilter] = useState<"all" | OneTapAccess>("all");
    const [visibilitasFilter, setVisibilitasFilter] = useState<VisibilitasFilter>("all");
    const [perPage, setPerPage] = useState<10 | 20 | 50>(10);
    const [currentPage, setCurrentPage] = useState(1);
    const [selectedPage, setSelectedPage] = useState<OneTapPage | null>(data[0] ?? null);
    const [editorOpen, setEditorOpen] = useState(false);
    const [editorMode, setEditorMode] = useState<EditorMode>("add");
    const [formData, setFormData] = useState<OneTapFormData>(EMPTY_FORM);
    const [errors, setErrors] = useState<ValidationErrors>({});
    const [saving, setSaving] = useState(false);
    const [confirmDelete, setConfirmDelete] = useState<{
        open: boolean;
        page: OneTapPage | null;
        permanent: boolean;
    }>({ open: false, page: null, permanent: false });

    useEffect(() => {
        setPages(data);
        setSelectedPage((current) => {
            if (current && data.some((page) => page.id === current.id)) return current;
            return data[0] ?? null;
        });
    }, [data]);

    useEffect(() => setTrashPages(trash), [trash]);

    const filteredPages = useMemo(() => {
        const keyword = searchQuery.trim().toLowerCase();
        return [...pages]
            .sort((a, b) => new Date(b.created_at ?? "").getTime() - new Date(a.created_at ?? "").getTime())
            .filter((page) => {
                const isOwner = page.pembuat_username === username;
                const visibilitas = normalizeVisibilitas(page.visibilitas);
                const matchesVisibilitas =
                    visibilitasFilter === "all"
                        ? isOwner || visibilitas === "publik"
                        : visibilitasFilter === "internal"
                          ? isOwner || visibilitas === "internal"
                          : isOwner;
                const matchesSearch =
                    keyword === "" ||
                    page.judul.toLowerCase().includes(keyword) ||
                    page.slug.toLowerCase().includes(keyword) ||
                    page.deskripsi?.toLowerCase().includes(keyword);
                const matchesAccess = accessFilter === "all" || page.akses === accessFilter;
                return matchesSearch && matchesAccess && matchesVisibilitas;
            });
    }, [pages, searchQuery, accessFilter, visibilitasFilter, username]);

    const totalPages = Math.max(1, Math.ceil(filteredPages.length / perPage));

    const paginatedPages = useMemo(
        () => filteredPages.slice((currentPage - 1) * perPage, currentPage * perPage),
        [filteredPages, currentPage, perPage],
    );

    useEffect(() => {
        setCurrentPage(1);
    }, [searchQuery, accessFilter, visibilitasFilter, perPage]);

    useEffect(() => {
        setCurrentPage((page) => Math.min(page, totalPages));
    }, [totalPages]);

    const filteredTrash = useMemo(() => {
        const keyword = searchQuery.trim().toLowerCase();
        return trashPages.filter((page) =>
            keyword === "" || page.judul.toLowerCase().includes(keyword) || page.slug.toLowerCase().includes(keyword),
        );
    }, [trashPages, searchQuery]);

    const openCreate = () => {
        setEditorMode("add");
        setFormData({ ...EMPTY_FORM, buttons: [] });
        setErrors({});
        setEditorOpen(true);
    };

    const openEdit = (page: OneTapPage) => {
        setEditorMode("edit");
        setFormData({
            id: page.id,
            judul: page.judul ?? "",
            slug: page.slug ?? "",
            deskripsi: page.deskripsi ?? "",
            akses: page.akses ?? "private",
            visibilitas: normalizeVisibilitas(page.visibilitas),
            tema: page.tema ?? "orange",
            foto_url: page.foto_url ?? "",
            warna_latar: page.warna_latar ?? "",
            buttons: page.buttons?.map((button, index) => ({
                id: button.id,
                tipe: button.tipe ?? "link",
                judul: button.judul ?? "",
                url: button.url ?? "",
                ikon: button.ikon ?? "",
                warna: button.warna ?? "",
                aktif: Boolean(button.aktif),
                urutan: button.urutan ?? index + 1,
            })) ?? [],
            pengguna: page.pengguna?.map((item) => item.pengguna_username) ?? [],
        });
        setErrors({});
        setEditorOpen(true);
    };

    const closeEditor = () => {
        if (saving) return;
        setEditorOpen(false);
        setEditorMode("add");
        setFormData(EMPTY_FORM);
        setErrors({});
    };

    const setField = <K extends keyof OneTapFormData>(key: K, value: OneTapFormData[K]) => {
        setFormData((current) => ({ ...current, [key]: value }));
        setErrors((current) => ({ ...current, [key]: undefined }));
    };

    const setButtonField = <K extends keyof OneTapButton>(index: number, key: K, value: OneTapButton[K]) => {
        setFormData((current) => {
            const buttons = [...current.buttons];
            buttons[index] = { ...buttons[index], [key]: value };
            return { ...current, buttons };
        });
        setErrors((current) => ({ ...current, [`buttons.${index}.${String(key)}`]: undefined }));
    };

    const addButton = (type: OneTapItemType, index?: number) => {
        setFormData((current) => {
            const position = index ?? current.buttons.length;
            const buttons = [...current.buttons];
            buttons.splice(position, 0, {
                tipe: type,
                judul: "",
                url: "",
                ikon: "",
                warna: "",
                aktif: true,
                urutan: position + 1,
            });

            return {
                ...current,
                buttons: buttons.map((button, itemIndex) => ({ ...button, urutan: itemIndex + 1 })),
            };
        });
    };

    const removeButton = (index: number) => {
        setFormData((current) => ({
            ...current,
            buttons: current.buttons
                .filter((_, itemIndex) => itemIndex !== index)
                .map((button, itemIndex) => ({ ...button, urutan: itemIndex + 1 })),
        }));
    };

    const moveButton = (index: number, direction: "up" | "down") => {
        setFormData((current) => {
            const target = direction === "up" ? index - 1 : index + 1;
            if (target < 0 || target >= current.buttons.length) return current;
            const buttons = [...current.buttons];
            [buttons[index], buttons[target]] = [buttons[target], buttons[index]];
            return {
                ...current,
                buttons: buttons.map((button, itemIndex) => ({ ...button, urutan: itemIndex + 1 })),
            };
        });
    };

    const addPengguna = (username: string) => {
        setFormData((current) => current.pengguna.includes(username)
            ? current
            : { ...current, pengguna: [...current.pengguna, username] });
        setErrors((current) => ({ ...current, visibilitas: undefined }));
    };

    const removePengguna = (username: string) => {
        setFormData((current) => ({
            ...current,
            pengguna: current.pengguna.filter((item) => item !== username),
        }));
        setErrors((current) => ({ ...current, visibilitas: undefined }));
    };

    const validateForm = (): boolean => {
        const nextErrors: ValidationErrors = {};
        if (!formData.judul.trim()) nextErrors.judul = "Judul wajib diisi.";
        if (!formData.slug.trim()) nextErrors.slug = "Slug wajib diisi.";
        if (formData.visibilitas === "internal" && formData.pengguna.length === 0) {
            nextErrors.visibilitas = "Pilih minimal satu pengguna untuk visibilitas internal.";
        }
        formData.buttons.forEach((button, index) => {
            if (button.tipe === "link" && !button.judul.trim()) nextErrors[`buttons.${index}.judul`] = "Judul tombol wajib diisi.";
            if (button.tipe === "link" && !button.url.trim()) nextErrors[`buttons.${index}.url`] = "URL wajib diisi untuk item link.";
        });
        setErrors(nextErrors);
        return Object.keys(nextErrors).length === 0;
    };

    const savePage = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (!validateForm()) {
            toast.error("Periksa kembali data yang wajib diisi.");
            return;
        }

        const payload = {
            ...formData,
            slug: normalizeSlug(formData.slug),
            pengguna: formData.visibilitas === "internal" ? formData.pengguna : [],
            buttons: formData.buttons.map((button, index) => ({ ...button, urutan: index + 1 })),
        };

        setSaving(true);
        setIsLoading(true);

        try {
            if (editorMode === "add") {
                const response = await axiosClient.post(route("onetap.store"), payload);
                const created = response.data.data as OneTapPage;
                setPages((current) => [created, ...current]);
                toast.success("OneTap berhasil ditambahkan.");
            } else {
                const response = await axiosClient.put(route("onetap.update", { id: formData.id }), payload);
                const updated = response.data.data as OneTapPage;
                setPages((current) => current.map((page) => (page.id === updated.id ? updated : page)));
                toast.success("OneTap berhasil diperbarui.");
            }
            setEditorOpen(false);
            setEditorMode("add");
            setFormData(EMPTY_FORM);
            setErrors({});
        } catch (error: any) {
            const responseErrors = error?.response?.data?.errors;
            if (responseErrors) {
                const mapped: ValidationErrors = {};
                Object.entries(responseErrors).forEach(([key, value]) => {
                    mapped[key] = Array.isArray(value) ? String(value[0]) : String(value);
                });
                setErrors(mapped);
                toast.error("Data belum dapat disimpan.");
            } else {
                console.error(error);
                toast.error("Terjadi kesalahan saat menyimpan OneTap.");
            }
        } finally {
            setSaving(false);
            setIsLoading(false);
        }
    };

    const moveToTrash = async (page: OneTapPage) => {
        setIsLoading(true);
        try {
            await axiosClient.delete(route("onetap.destroy", { id: page.id }));
            setPages((current) => current.filter((item) => item.id !== page.id));
            setTrashPages((current) => [{ ...page, deleted_at: new Date().toISOString() }, ...current]);
            setSelectedPage((current) => current?.id === page.id ? null : current);
            toast.success("OneTap dipindahkan ke sampah.");
        } catch (error) {
            console.error(error);
            toast.error("OneTap gagal dipindahkan ke sampah.");
        } finally {
            setIsLoading(false);
            setConfirmDelete({ open: false, page: null, permanent: false });
        }
    };

    const restorePage = async (page: OneTapPage) => {
        setIsLoading(true);
        try {
            const response = await axiosClient.patch(route("onetap.restore", { id: page.id }));
            const restored = (response.data?.data as OneTapPage | undefined) ?? { ...page, deleted_at: null };
            setTrashPages((current) => current.filter((item) => item.id !== page.id));
            setPages((current) => [restored, ...current]);
            setSelectedPage(restored);
            toast.success("OneTap berhasil dipulihkan.");
        } catch (error) {
            console.error(error);
            toast.error("OneTap gagal dipulihkan.");
        } finally {
            setIsLoading(false);
        }
    };

    const forceDeletePage = async (page: OneTapPage) => {
        setIsLoading(true);
        try {
            await axiosClient.delete(route("onetap.force-delete", { id: page.id }));
            setTrashPages((current) => current.filter((item) => item.id !== page.id));
            setSelectedPage((current) => current?.id === page.id ? null : current);
            toast.success("OneTap dihapus permanen.");
        } catch (error) {
            console.error(error);
            toast.error("OneTap gagal dihapus permanen.");
        } finally {
            setIsLoading(false);
            setConfirmDelete({ open: false, page: null, permanent: false });
        }
    };

    const copyPublicLink = async (page: OneTapPage) => {
        const url = `${window.location.origin}/lopi/ot/${page.slug}`;
        try {
            await navigator.clipboard.writeText(url);
            toast.success("Link publik berhasil disalin.");
        } catch {
            toast.error("Link publik gagal disalin.");
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="OneTap" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="min-h-screen text-slate-800">
                    <div className="mx-auto w-full max-w-7xl">
                        <div className="mb-6 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight text-slate-900">OneTap</h1>
                                <p className="mt-1 text-sm text-slate-500">Kelola halaman tautan, tampilan publik, dan tombol OneTap dalam satu halaman.</p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Button type="button" variant={view === "list" ? "default" : "secondary"} onClick={() => setView("list")}>
                                    <LinkIcon size={16} /> Daftar
                                </Button>
                                <Button type="button" variant={view === "trash" ? "default" : "secondary"} onClick={() => setView("trash")}>
                                    <Trash2 size={16} /> Sampah
                                    {trashPages.length > 0 && <span className="ml-1 rounded-full bg-white/20 px-1.5 py-0.5 text-[10px]">{trashPages.length}</span>}
                                </Button>
                                <Button type="button" onClick={openCreate}><Plus size={16} /> Tambah OneTap</Button>
                            </div>
                        </div>

                        <div className="mb-6 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row md:items-center">
                            <div className="relative flex-1">
                                <Search size={18} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                <Input value={searchQuery} onChange={(event) => setSearchQuery(event.target.value)} placeholder={view === "list" ? "Cari judul, slug, atau deskripsi..." : "Cari OneTap di sampah..."} className="pl-10" />
                            </div>
                            {view === "list" && (
                                <>
                                    <Select value={accessFilter} onValueChange={(value) => setAccessFilter(value as "all" | OneTapAccess)}>
                                        <SelectTrigger className="w-full md:w-48"><SelectValue placeholder="Filter akses" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua Akses</SelectItem>
                                            <SelectItem value="public">Public</SelectItem>
                                            <SelectItem value="private">Private</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <Select value={visibilitasFilter} onValueChange={(value) => setVisibilitasFilter(value as VisibilitasFilter)}>
                                        <SelectTrigger className="w-full md:w-48"><SelectValue placeholder="Filter visibilitas" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua</SelectItem>
                                            <SelectItem value="internal">Untuk Saya</SelectItem>
                                            <SelectItem value="privat">Privat</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <Select value={String(perPage)} onValueChange={(value) => setPerPage(Number(value) as 10 | 20 | 50)}>
                                        <SelectTrigger className="w-full md:w-36"><SelectValue placeholder="Tampilkan" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="10">10 data</SelectItem>
                                            <SelectItem value="20">20 data</SelectItem>
                                            <SelectItem value="50">50 data</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </>
                            )}
                        </div>

                        <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
                            <div className="min-w-0">
                                {view === "list" ? (
                                    <>
                                        <OneTapList
                                            pages={paginatedPages}
                                            selectedId={selectedPage?.id ?? null}
                                            onSelect={setSelectedPage}
                                            onEdit={openEdit}
                                            onDelete={(page) => setConfirmDelete({ open: true, page, permanent: false })}
                                            onCopy={copyPublicLink}
                                        />
                                        <PaginationFooter
                                            currentPage={currentPage}
                                            itemsPerPage={perPage}
                                            totalItems={filteredPages.length}
                                            totalPages={totalPages}
                                            onPageChange={setCurrentPage}
                                        />
                                    </>
                                ) : (
                                    <OneTapTrash
                                        pages={filteredTrash}
                                        selectedId={selectedPage?.id ?? null}
                                        onSelect={setSelectedPage}
                                        onRestore={restorePage}
                                        onForceDelete={(page) => setConfirmDelete({ open: true, page, permanent: true })}
                                    />
                                )}
                            </div>

                            <aside className="xl:sticky xl:top-4 xl:self-start">
                                <ListPreview page={selectedPage} deleted={view === "trash"} />
                            </aside>
                        </div>
                    </div>
                </div>
            </div>

            <Dialog open={editorOpen} onOpenChange={closeEditor}>
                <DialogContent className="max-h-[95vh] !w-[96vw] !max-w-[1600px] overflow-y-auto">
                    <DialogHeader><DialogTitle>{editorMode === "add" ? "Tambah OneTap" : "Ubah OneTap"}</DialogTitle></DialogHeader>
                    <OneTapEditor mode={editorMode} data={formData} errors={errors} saving={saving} users={users} userNames={userNames} tim={tim} onFieldChange={setField} onButtonChange={setButtonField} onAddButton={addButton} onRemoveButton={removeButton} onMoveButton={moveButton} onAddPengguna={addPengguna} onRemovePengguna={removePengguna} onSubmit={savePage} onCancel={closeEditor} />
                </DialogContent>
            </Dialog>

            <Dialog open={confirmDelete.open} onOpenChange={(open) => setConfirmDelete((current) => ({ ...current, open }))}>
                <DialogContent className="max-w-md">
                    <DialogHeader><DialogTitle>{confirmDelete.permanent ? "Hapus Permanen" : "Pindahkan ke Sampah"}</DialogTitle></DialogHeader>
                    <div className="space-y-4">
                        <p className="text-sm leading-6 text-slate-600">
                            {confirmDelete.permanent
                                ? `OneTap “${confirmDelete.page?.judul ?? ""}” akan dihapus permanen dan tidak dapat dipulihkan.`
                                : `OneTap “${confirmDelete.page?.judul ?? ""}” akan dipindahkan ke sampah.`}
                        </p>
                        <div className="flex justify-end gap-2">
                            <Button type="button" variant="secondary" onClick={() => setConfirmDelete({ open: false, page: null, permanent: false })}>Batal</Button>
                            <Button type="button" variant="destructive" onClick={() => {
                                const page = confirmDelete.page;
                                if (!page) return;
                                confirmDelete.permanent ? forceDeletePage(page) : moveToTrash(page);
                            }}>
                                <Trash2 size={16} /> {confirmDelete.permanent ? "Hapus Permanen" : "Pindahkan"}
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

function OneTapList({ pages, selectedId, onSelect, onEdit, onDelete, onCopy }: {
    pages: OneTapPage[];
    selectedId: string | null;
    onSelect: (page: OneTapPage) => void;
    onEdit: (page: OneTapPage) => void;
    onDelete: (page: OneTapPage) => void;
    onCopy: (page: OneTapPage) => void;
}) {

    const { auth } = usePage<SharedProps>().props;
    if (pages.length === 0) return <EmptyState icon={<LinkIcon size={34} />} title="Belum ada OneTap" description="Tambahkan OneTap pertama untuk mulai membuat halaman tautan." />;

    return (
        <div className="space-y-3">
            {pages.map((page, index) => {
                const theme = getTheme(page.tema);
                const activeButtons = page.buttons?.filter((button) => button.aktif).length ?? page.jumlah_tombol ?? 0;
                const selected = selectedId === page.id;

                return (
                    <article
                        key={page.id}
                        role="button"
                        tabIndex={0}
                        aria-selected={selected}
                        onClick={() => onSelect(page)}
                        onKeyDown={(event) => {
                            if (event.key === "Enter" || event.key === " ") {
                                event.preventDefault();
                                onSelect(page);
                            }
                        }}
                        className={`group cursor-pointer overflow-hidden rounded-2xl border bg-white shadow-sm transition focus:outline-none focus:ring-2 focus:ring-slate-400 ${selected ? "border-slate-500 ring-2 ring-slate-200" : "border-slate-200 hover:border-slate-300 hover:shadow-md"}`}
                    >
                        <div className="flex flex-col md:flex-row">
                            <div className="flex w-full items-center gap-4 p-4 md:w-24 md:justify-center" style={{ background: page.warna_latar?.trim() || theme.background }}>
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/90 text-sm font-bold text-slate-800 shadow">{index + 1}</div>
                            </div>
                            <div className="min-w-0 flex-1 p-4">
                                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h2 className="truncate text-lg font-bold text-slate-900">{page.judul}</h2>
                                            {/* <span className={`rounded-full px-2.5 py-1 text-[10px] font-bold uppercase ${page.akses === "public" ? "bg-emerald-100 text-emerald-700" : "bg-slate-100 text-slate-600"}`}>{page.pembuat_username}</span> */}
                                            <span className={`rounded-full px-2.5 py-1 text-[10px] font-bold uppercase ${page.akses === "public" ? "bg-emerald-100 text-emerald-700" : "bg-slate-100 text-slate-600"}`}>{page.akses}</span>
                                            <span className="rounded-full bg-sky-50 px-2.5 py-1 text-[10px] font-bold uppercase text-sky-700">{getVisibilitasLabel(page.visibilitas)}</span>
                                            <span className="rounded-full bg-orange-50 px-2.5 py-1 text-[10px] font-bold uppercase text-orange-700">{theme.label}</span>
                                        </div>
                                        <p className="mt-1 truncate text-sm font-medium text-slate-500">/ot/{page.slug}</p>
                                        
                                        <div className="mt-3 flex flex-wrap gap-x-2 gap-y-1 text-xs text-slate-500">
                                            
                                            <span className="font-medium bg-slate-500 text-white rounded-lg px-2">{page.pembuat_username}</span>
                                            <span>{formatDate(page.updated_at ?? "")}</span>
                                        </div>
                                    </div>
                                    <div className="flex shrink-0 flex-wrap gap-2" onClick={(event) => event.stopPropagation()}>
                                        {/* <Button type="button" size="icon" variant="secondary" title="Ubah OneTap" aria-label={`Ubah ${page.judul}`} onClick={() => onEdit(page)}><Pencil size={15} /></Button> */}
                                        <Button className={`${auth.user?.username === page.pembuat_username ? 'span' : 'hidden'}`} type="button" size="icon" variant="secondary" title="Ubah OneTap" aria-label={`Ubah ${page.judul}`} onClick={() => onEdit(page)}><Pencil size={15} /></Button>
                                        <Button type="button" size="icon" variant="secondary" title="Salin tautan" aria-label={`Salin tautan ${page.judul}`} onClick={() => onCopy(page)}><Copy size={15} /></Button>
                                        <a href={`/lopi/ot/${page.slug}`} target="_blank" rel="noreferrer" title="Buka OneTap" aria-label={`Buka ${page.judul}`} className="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50"><ExternalLink size={15} /></a>
                                        <Button type="button" size="icon" variant="ghost" title="Pindahkan ke sampah" aria-label={`Hapus ${page.judul}`} className={`${auth.user?.username === page.pembuat_username ? 'span' : 'hidden'} text-red-600 hover:bg-red-50 hover:text-red-700 transition-all duration-300`} onClick={() => onDelete(page)}><Trash2 size={15} /></Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                );
            })}
        </div>
    );
}

function OneTapTrash({ pages, selectedId, onSelect, onRestore, onForceDelete }: {
    pages: OneTapPage[];
    selectedId: string | null;
    onSelect: (page: OneTapPage) => void;
    onRestore: (page: OneTapPage) => void;
    onForceDelete: (page: OneTapPage) => void;
}) {
    if (pages.length === 0) return <EmptyState icon={<Trash2 size={34} />} title="Sampah kosong" description="OneTap yang dihapus sementara akan tampil di sini." />;

    return (
        <div className="space-y-3">
            {pages.map((page) => {
                const selected = selectedId === page.id;
                return (
                    <article
                        key={page.id}
                        role="button"
                        tabIndex={0}
                        aria-selected={selected}
                        onClick={() => onSelect(page)}
                        onKeyDown={(event) => {
                            if (event.key === "Enter" || event.key === " ") {
                                event.preventDefault();
                                onSelect(page);
                            }
                        }}
                        className={`flex cursor-pointer flex-col gap-4 rounded-2xl border bg-white p-4 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-slate-400 md:flex-row md:items-center md:justify-between ${selected ? "border-slate-500 ring-2 ring-slate-200" : "border-slate-200 hover:border-slate-300 hover:shadow-md"}`}
                    >
                        <div className="min-w-0">
                            <div className="flex flex-wrap items-center gap-2">
                                <h2 className="truncate font-semibold text-slate-900">{page.judul}</h2>
                                <span className={`rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ${page.akses === "public" ? "bg-emerald-100 text-emerald-700" : "bg-slate-100 text-slate-600"}`}>{page.akses}</span>
                            </div>
                            <p className="mt-1 truncate text-sm text-slate-500">/ot/{page.slug}</p>
                            <p className="mt-2 text-xs text-slate-400">Dihapus {page.deleted_at ? formatDate(page.deleted_at) : "-"}</p>
                        </div>
                        <div className="flex flex-wrap gap-2" onClick={(event) => event.stopPropagation()}>
                            <Button type="button" size="sm" variant="secondary" onClick={() => onRestore(page)}><RefreshCcw size={14} /> Pulihkan</Button>
                            <Button type="button" size="sm" variant="destructive" onClick={() => onForceDelete(page)}><Trash2 size={14} /> Hapus Permanen</Button>
                        </div>
                    </article>
                );
            })}
        </div>
    );
}

function ListPreview({ page, deleted = false }: { page: OneTapPage | null; deleted?: boolean }) {
    if (!page) {
        return (
            <div className="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm">
                <Eye size={30} className="mx-auto mb-3 text-slate-300" />
                <p className="font-medium text-slate-700">Pilih sebuah card</p>
                <p className="mt-1 text-sm leading-6 text-slate-500">Preview OneTap akan tampil di bagian ini sebelum dibuka.</p>
            </div>
        );
    }

    const theme = getTheme(page.tema);
    const background = page.warna_latar?.trim() || theme.background;
    const activeButtons = page.buttons?.filter((button) => button.aktif) ?? [];

    return (
        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div className="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                <div>
                    <h3 className="font-semibold text-slate-900">Preview</h3>
                    <p className="text-xs text-slate-500">{deleted ? "Data berada di sampah." : "Klik Buka untuk melihat halaman penuh."}</p>
                </div>
                <span className={`rounded-full px-2 py-1 text-[10px] font-bold uppercase ${page.akses === "public" ? "bg-emerald-100 text-emerald-700" : "bg-slate-100 text-slate-600"}`}>{page.akses}</span>
            </div>
            <div className="max-h-[70vh] min-h-[520px] overflow-y-auto p-5 text-white" style={{ background }}>
                <div className="mx-auto max-w-sm">
                    <div className="mb-6 text-center">
                        {page.foto_url ? <img src={page.foto_url} alt={page.judul} className="mx-auto mb-4 h-20 w-20 rounded-full border-4 border-white/80 object-cover shadow-lg" /> : <div className="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full border-4 border-white/70 bg-white/20 shadow-lg"><LinkIcon size={30} /></div>}
                        <h2 className="break-words text-xl font-bold">{page.judul}</h2>
                        {page.deskripsi && <p className="mt-2 whitespace-pre-line text-sm leading-6 text-white/90">{page.deskripsi}</p>}
                    </div>
                    <div className="space-y-3">
                        {activeButtons.map((button, index) => button.tipe === "divider" ? (
                            <div key={button.id ?? `preview-divider-${index}`} className="flex items-center gap-3 py-2">
                                <span className="h-px flex-1 bg-white/40" />
                                {button.judul.trim() !== "" && <span className="max-w-[70%] text-center text-xs font-semibold uppercase tracking-wider text-white/90">{button.judul}</span>}
                                <span className="h-px flex-1 bg-white/40" />
                            </div>
                        ) : (
                            <div key={button.id ?? `preview-link-${index}`} className="flex min-h-12 items-center gap-3 rounded-xl px-4 py-3 text-slate-800 shadow-md" style={{ background: button.warna || "rgba(255,255,255,.95)" }}>
                                {renderButtonIcon(button.ikon, "h-[17px] w-[17px] shrink-0")}
                                <span className="min-w-0 flex-1 break-words text-sm font-semibold">{button.judul}</span>
                                <ExternalLink size={13} className="shrink-0 opacity-60" />
                            </div>
                        ))}
                        {activeButtons.length === 0 && <div className="rounded-xl border border-white/30 bg-white/10 px-4 py-8 text-center text-sm text-white/80">Belum ada tombol aktif.</div>}
                    </div>
                    {!deleted && (
                        <a href={`/lopi/ot/${page.slug}`} target="_blank" rel="noreferrer" className="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-white/40 bg-white/15 px-4 py-3 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/25">
                            <ExternalLink size={16} /> Buka OneTap
                        </a>
                    )}
                </div>
            </div>
        </div>
    );
}

function OneTapEditor({ mode, data, errors, saving, users, userNames, tim, onFieldChange, onButtonChange, onAddButton, onRemoveButton, onMoveButton, onAddPengguna, onRemovePengguna, onSubmit, onCancel }: {
    mode: EditorMode;
    data: OneTapFormData;
    errors: ValidationErrors;
    saving: boolean;
    users: OneTapUser[];
    userNames: Record<string, string>;
    tim: OneTapTim[];
    onFieldChange: <K extends keyof OneTapFormData>(key: K, value: OneTapFormData[K]) => void;
    onButtonChange: <K extends keyof OneTapButton>(index: number, key: K, value: OneTapButton[K]) => void;
    onAddButton: (type: OneTapItemType, index?: number) => void;
    onRemoveButton: (index: number) => void;
    onMoveButton: (index: number, direction: "up" | "down") => void;
    onAddPengguna: (username: string) => void;
    onRemovePengguna: (username: string) => void;
    onSubmit: (event: FormEvent<HTMLFormElement>) => void;
    onCancel: () => void;
}) {
    const theme = getTheme(data.tema);
    const previewBackground = data.warna_latar.trim() || theme.background;

    return (
        <form onSubmit={onSubmit} className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_420px]">
            <div className="space-y-6">
                <section className="rounded-2xl border border-slate-200 bg-white p-5">
                    <div className="mb-5"><h3 className="font-semibold text-slate-900">Informasi Halaman</h3><p className="mt-1 text-sm text-slate-500">Form yang sama digunakan untuk tambah dan ubah OneTap.</p></div>
                    <div className="grid gap-4 md:grid-cols-2">
                        <FormField label="Judul" required error={errors.judul}>
                            <Input value={data.judul} onChange={(event) => {
                                const value = event.target.value;
                                onFieldChange("judul", value);
                                if (mode === "add" && (!data.slug || data.slug === normalizeSlug(data.judul))) onFieldChange("slug", normalizeSlug(value));
                            }} placeholder="Contoh: BPS Sulawesi Barat" />
                        </FormField>
                        <FormField label="Slug" required error={errors.slug} description="Alamat publik halaman OneTap.">
                            <div className="flex overflow-hidden rounded-md border border-input bg-background">
                                <span className="flex items-center border-r bg-slate-50 px-3 text-sm text-slate-500">/ot/</span>
                                <input value={data.slug} onChange={(event) => onFieldChange("slug", normalizeSlug(event.target.value))} className="min-w-0 flex-1 bg-transparent px-3 py-2 text-sm outline-none" placeholder="bps-sulbar" />
                            </div>
                        </FormField>
                        <div className="md:col-span-2">
                            <FormField label="Deskripsi" error={errors.deskripsi}>
                                <textarea value={data.deskripsi} onChange={(event) => onFieldChange("deskripsi", event.target.value)} rows={4} className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-ring" placeholder="Deskripsi singkat halaman..." />
                            </FormField>
                        </div>
                        <FormField label="Akses" required error={errors.akses}>
                            <Select value={data.akses} onValueChange={(value) => onFieldChange("akses", value as OneTapAccess)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent><SelectItem value="private">Private</SelectItem><SelectItem value="public">Public</SelectItem></SelectContent>
                            </Select>
                        </FormField>
                        <FormField label="Visibilitas" required error={errors.visibilitas} description={VISIBILITAS_OPTIONS.find((option) => option.value === data.visibilitas)?.description}>
                            <Select value={data.visibilitas} onValueChange={(value) => onFieldChange("visibilitas", value as OneTapVisibilitas)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    {VISIBILITAS_OPTIONS.map((option) => <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        </FormField>
                        {data.visibilitas === "internal" && (
                            <div className="border-t border-slate-200 pt-4 md:col-span-2">
                                <p className="text-sm font-medium text-slate-700">Pengguna yang Dapat Melihat</p>
                                <p className="mb-2 mt-1 text-xs text-slate-500">Pilih akun atau tim kerja yang boleh melihat OneTap ini.</p>
                                <PenggunaPicker users={users} userNames={userNames} tim={tim} selected={data.pengguna} onAdd={onAddPengguna} onRemove={onRemovePengguna} />
                            </div>
                        )}
                        <FormField label="URL Foto Profil" error={errors.foto_url}>
                            <Input value={data.foto_url} onChange={(event) => onFieldChange("foto_url", event.target.value)} placeholder="https://..." />
                        </FormField>
                    </div>
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white p-5">
                    <div className="mb-5"><h3 className="font-semibold text-slate-900">Tampilan</h3><p className="mt-1 text-sm text-slate-500">Pilih tema atau gunakan warna latar khusus.</p></div>
                    <div className="grid gap-3 sm:grid-cols-3">
                        {THEME_OPTIONS.map((option) => (
                            <button key={option.value} type="button" onClick={() => onFieldChange("tema", option.value)} className={`rounded-xl border-2 p-3 text-left transition ${data.tema === option.value ? "border-indigo-600 ring-2 ring-indigo-100" : "border-slate-200 hover:border-slate-300"}`}>
                                <span className="mb-3 block h-16 rounded-lg" style={{ background: option.background }} />
                                <span className="text-sm font-semibold text-slate-800">{option.label}</span>
                            </button>
                        ))}
                    </div>
                    <div className="mt-4">
                        <FormField label="Warna Latar Khusus" error={errors.warna_latar} description="Kosongkan untuk menggunakan tema. Dapat berupa kode warna atau CSS gradient.">
                            <Input value={data.warna_latar} onChange={(event) => onFieldChange("warna_latar", event.target.value)} placeholder="#004aad atau linear-gradient(...)" />
                        </FormField>
                    </div>
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white p-5">
                    <div className="mb-5">
                        <h3 className="font-semibold text-slate-900">Daftar Tombol</h3>
                        <p className="mt-1 text-sm text-slate-500">Tambahkan link atau divider di antara tombol, lalu atur urutannya.</p>
                    </div>
                    <div className="space-y-4">
                        {data.buttons.length > 0 ? (
                            data.buttons.map((button, index) => (
                                <div key={button.id ?? index} className="space-y-1">
                                    <ButtonEditor button={button} index={index} total={data.buttons.length} errors={errors} onChange={onButtonChange} onRemove={onRemoveButton} onMove={onMoveButton} />
                                    <InsertButtons onAdd={(type) => onAddButton(type, index + 1)} />
                                </div>
                            ))
                        ) : (
                            <div className="rounded-xl border-2 border-dashed border-slate-200 px-5 py-10 text-center">
                                <LinkIcon size={30} className="mx-auto mb-3 text-slate-300" />
                                <p className="text-sm font-medium text-slate-600">Belum ada tombol.</p>
                                <InsertButtons empty onAdd={(type) => onAddButton(type, 0)} />
                            </div>
                        )}
                    </div>
                </section>

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" onClick={onCancel} disabled={saving}><X size={16} /> Batal</Button>
                    <Button type="submit" disabled={saving}><Save size={16} /> {saving ? "Menyimpan..." : "Simpan OneTap"}</Button>
                </div>
            </div>

            <aside className="lg:sticky lg:top-0 lg:self-start">
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-slate-200 px-4 py-3"><div><h3 className="font-semibold text-slate-900">Preview</h3><p className="text-xs text-slate-500">Tampilan langsung halaman publik.</p></div><Eye size={18} className="text-slate-400" /></div>
                    <div className="min-h-[650px] p-5 text-white" style={{ background: previewBackground }}>
                        <div className="mx-auto max-w-sm">
                            <div className="mb-7 text-center">
                                {data.foto_url ? <img src={data.foto_url} alt={data.judul} className="mx-auto mb-4 h-24 w-24 rounded-full border-4 border-white/80 object-cover shadow-lg" /> : <div className="mx-auto mb-4 flex h-24 w-24 items-center justify-center rounded-full border-4 border-white/70 bg-white/20 shadow-lg"><LinkIcon size={34} /></div>}
                                <h2 className="break-words text-2xl font-bold">{data.judul || "Judul OneTap"}</h2>
                                {data.deskripsi && <p className="mt-2 whitespace-pre-line text-sm leading-6 text-white/90">{data.deskripsi}</p>}
                            </div>
                            <div className="space-y-3">
                                {data.buttons.filter((button) => button.aktif).map((button, index) => button.tipe === "divider" ? (
                                    <div key={button.id ?? `divider-${index}`} className="flex items-center gap-3 py-2"><span className="h-px flex-1 bg-white/40" />{button.judul.trim() !== "" && <span className="max-w-[70%] text-center text-xs font-semibold uppercase tracking-wider text-white/90">{button.judul}</span>}<span className="h-px flex-1 bg-white/40" /></div>
                                ) : (
                                    <div key={button.id ?? `link-${index}`} className="flex min-h-14 items-center gap-3 rounded-xl px-4 py-3 text-slate-800 shadow-md" style={{ background: button.warna || "rgba(255,255,255,.95)" }}>{renderButtonIcon(button.ikon, "h-[18px] w-[18px] shrink-0")}<span className="min-w-0 flex-1 break-words text-sm font-semibold">{button.judul || "Judul Link"}</span><ExternalLink size={14} className="shrink-0 opacity-60" /></div>
                                ))}
                                {data.buttons.filter((button) => button.aktif).length === 0 && <div className="rounded-xl border border-white/30 bg-white/10 px-4 py-8 text-center text-sm text-white/80">Belum ada tombol aktif.</div>}
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </form>
    );
}

function PenggunaPicker({ users, userNames, tim, selected, onAdd, onRemove }: {
    users: OneTapUser[];
    userNames: Record<string, string>;
    tim: OneTapTim[];
    selected: string[];
    onAdd: (username: string) => void;
    onRemove: (username: string) => void;
}) {
    const [open, setOpen] = useState(false);
    const availableUsers = users.filter((user) => !selected.includes(user.username));
    const availableTim = tim.filter((team) => team.anggota.some((username) => !selected.includes(username)));
    const getUserName = (username: string) => userNames[username] ?? users.find((user) => user.username === username)?.nama ?? username;

    return (
        <div className="space-y-3">
            <Popover open={open} onOpenChange={setOpen} modal={true}>
                <PopoverTrigger asChild>
                    <Button type="button" variant="outline" role="combobox" className="w-full justify-between text-slate-600">
                        Pilih pengguna atau tim...
                        <ChevronsUpDown className="opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="z-[100] w-[var(--radix-popover-trigger-width)] p-0" align="start">
                    <Command>
                        <CommandInput placeholder="Cari pengguna atau tim..." className="h-9" />
                        <CommandList>
                            <CommandEmpty>Tidak ditemukan.</CommandEmpty>
                            {availableTim.length > 0 && (
                                <CommandGroup heading="Tim Kerja">
                                    {availableTim.map((team) => (
                                        <CommandItem
                                            key={`tim-${team.id}`}
                                            value={`tim ${team.nama}`}
                                            onSelect={() => {
                                                team.anggota.forEach(onAdd);
                                                setOpen(false);
                                            }}
                                        >
                                            <span className="flex-1">{team.nama}</span>
                                            <span className="text-xs text-slate-400">Tim · {team.anggota.length}</span>
                                        </CommandItem>
                                    ))}
                                </CommandGroup>
                            )}
                            {availableUsers.length > 0 && (
                                <CommandGroup heading="Pengguna">
                                    {availableUsers.map((user) => (
                                        <CommandItem
                                            key={user.username}
                                            value={`${user.nama} ${user.username}`}
                                            onSelect={() => {
                                                onAdd(user.username);
                                                setOpen(false);
                                            }}
                                        >
                                            <span className="flex-1">{user.nama}</span>
                                            <span className="text-xs text-slate-400">{user.username}</span>
                                        </CommandItem>
                                    ))}
                                </CommandGroup>
                            )}
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>

            {selected.length > 0 ? (
                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <table className="w-full text-left text-sm">
                        <tbody className="divide-y divide-slate-100">
                            {selected.map((username) => (
                                <tr key={username}>
                                    <td className="px-4 py-3">
                                        <span className="font-medium text-slate-700">{getUserName(username)}</span>
                                        <span className="ml-2 text-xs text-slate-400">{username}</span>
                                    </td>
                                    <td className="w-10 px-3 py-3 text-center">
                                        <Button type="button" size="icon" variant="ghost" className="text-red-600 hover:bg-red-50" onClick={() => onRemove(username)}>
                                            <Trash2 size={14} />
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : (
                <p className="text-xs text-slate-500">Belum ada pengguna atau tim dipilih.</p>
            )}
        </div>
    );
}

function InsertButtons({ onAdd, empty = false }: {
    onAdd: (type: OneTapItemType) => void;
    empty?: boolean;
}) {
    const buttonClass = empty
        ? "gap-1"
        : "h-8 gap-1 px-2 text-xs font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-800 border-2 border-slate-200 rounded-lg transition cursor-pointer";

    return (
        <div className={empty ? "mt-4 flex items-center justify-center gap-2" : "flex items-center justify-center gap-1 py-1"}>
            <Button type="button" size="sm" variant={empty ? "secondary" : "ghost"} className={buttonClass} onClick={() => onAdd("link")}>
                <Plus size={14} /> Link
            </Button>
            <Button type="button" size="sm" variant={empty ? "secondary" : "ghost"} className={buttonClass} onClick={() => onAdd("divider")}>
                <Plus size={14} /> Divider
            </Button>
        </div>
    );
}

function ButtonEditor({ button, index, total, errors, onChange, onRemove, onMove }: {
    button: OneTapButton;
    index: number;
    total: number;
    errors: ValidationErrors;
    onChange: <K extends keyof OneTapButton>(index: number, key: K, value: OneTapButton[K]) => void;
    onRemove: (index: number) => void;
    onMove: (index: number, direction: "up" | "down") => void;
}) {
    const titleError = errors[`buttons.${index}.judul`];
    const urlError = errors[`buttons.${index}.url`];

    return (
        <article className="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
            <div className="mb-4 flex items-center justify-between gap-3">
                <div><span className="text-sm font-semibold text-slate-700">Item {index + 1}</span><span className="ml-2 rounded-full bg-slate-200 px-2 py-1 text-[10px] font-bold uppercase text-slate-600">{button.tipe}</span></div>
                <div className="flex gap-1">
                    <Button type="button" size="icon" variant="secondary" disabled={index === 0} onClick={() => onMove(index, "up")}><ArrowUp size={14} /></Button>
                    <Button type="button" size="icon" variant="secondary" disabled={index === total - 1} onClick={() => onMove(index, "down")}><ArrowDown size={14} /></Button>
                    <Button type="button" size="icon" variant="ghost" className="text-red-600 hover:bg-red-50" onClick={() => onRemove(index)}><Trash2 size={14} /></Button>
                </div>
            </div>
            <div className="grid gap-4 md:grid-cols-2">
                <FormField label="Tipe">
                    <Select value={button.tipe} onValueChange={(value) => onChange(index, "tipe", value as OneTapItemType)}>
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent><SelectItem value="link">Link</SelectItem><SelectItem value="divider">Divider</SelectItem></SelectContent>
                    </Select>
                </FormField>
                <FormField label={button.tipe === "divider" ? "Judul Divider (opsional)" : "Judul Tombol"} required={button.tipe === "link"} error={button.tipe === "link" ? titleError : undefined} description={button.tipe === "divider" ? "Kosongkan agar divider tampil sebagai garis saja." : undefined}>
                    <Input value={button.judul} onChange={(event) => onChange(index, "judul", event.target.value)} placeholder={button.tipe === "divider" ? "Kosong = hanya garis" : "Contoh: Website Resmi"} />
                </FormField>
                {button.tipe === "link" && (
                    <>
                        <div className="md:col-span-2"><FormField label="URL" required error={urlError}><Input value={button.url} onChange={(event) => onChange(index, "url", event.target.value)} placeholder="https://..." /></FormField></div>
                        <FormField label="Ikon Tombol" description="Klik salah satu ikon. Pilihan langsung tampil pada preview.">
                            <div className="grid grid-cols-5 gap-2 rounded-xl border border-slate-200 bg-white p-3 sm:grid-cols-8 md:grid-cols-5 xl:grid-cols-8">
                                {ICON_OPTIONS.map((option) => {
                                    const Icon = option.icon;
                                    const selected = (button.ikon || "link") === option.value;
                                    return (
                                        <button key={option.value} type="button" title={option.label} onClick={() => onChange(index, "ikon", option.value)} className={`flex h-10 items-center justify-center rounded-lg border transition ${selected ? "border-slate-900 bg-slate-900 text-white shadow-sm" : "border-slate-200 bg-white text-slate-600 hover:border-slate-400 hover:bg-slate-50"}`}>
                                            <Icon size={18} />
                                        </button>
                                    );
                                })}
                            </div>
                        </FormField>
                        <FormField label="Warna Tombol">
                            <div className="flex gap-2"><input type="color" value={safeHexColor(button.warna)} onChange={(event) => onChange(index, "warna", event.target.value)} className="h-10 w-12 cursor-pointer rounded-md border border-input bg-white p-1" /><Input value={button.warna} onChange={(event) => onChange(index, "warna", event.target.value)} placeholder="#ffffff" /></div>
                        </FormField>
                        <label className="md:col-span-2 flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 bg-white p-3">
                            <input type="checkbox" checked={button.aktif} onChange={(event) => onChange(index, "aktif", event.target.checked)} className="h-4 w-4 rounded" />
                            <span><span className="block text-sm font-medium text-slate-700">Tombol aktif</span><span className="block text-xs text-slate-500">Tombol aktif akan tampil pada halaman publik.</span></span>
                        </label>
                    </>
                )}
            </div>
        </article>
    );
}

function FormField({ label, required = false, error, description, children }: { label: string; required?: boolean; error?: string; description?: string; children: ReactNode; }) {
    return (
        <label className="block">
            <span className="mb-1.5 block text-sm font-medium text-slate-700">{label}{required && <span className="ml-1 text-red-500">*</span>}</span>
            {children}
            {description && !error && <span className="mt-1.5 block text-xs leading-5 text-slate-500">{description}</span>}
            {error && <span className="mt-1.5 block text-xs text-red-600">{error}</span>}
        </label>
    );
}

function EmptyState({ icon, title, description }: { icon: ReactNode; title: string; description: string; }) {
    return (
        <div className="rounded-2xl border-2 border-dashed border-slate-200 bg-white px-6 py-16 text-center">
            <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">{icon}</div>
            <h2 className="font-semibold text-slate-700">{title}</h2>
            <p className="mt-1 text-sm text-slate-500">{description}</p>
        </div>
    );
}

function renderButtonIcon(value: string, className = "h-4 w-4") {
    const option = ICON_OPTIONS.find((item) => item.value === value) ?? ICON_OPTIONS[0];
    const Icon = option.icon;
    return <Icon className={className} aria-hidden="true" />;
}

function normalizeSlug(value: string): string {
    return value.toLowerCase().trim().replace(/[^a-z0-9\s-]/g, "").replace(/\s+/g, "-").replace(/-+/g, "-").replace(/^-|-$/g, "");
}

function safeHexColor(value: string): string {
    const normalized = value.trim();
    return /^#[0-9a-fA-F]{6}$/.test(normalized) ? normalized : "#ffffff";
}

function getTheme(theme: OneTapTheme) {
    return THEME_OPTIONS.find((item) => item.value === theme) ?? THEME_OPTIONS[0];
}

function normalizeVisibilitas(visibilitas: string | undefined): OneTapVisibilitas {
    if (visibilitas === "publik" || visibilitas === "public") return "publik";
    if (visibilitas === "internal" || visibilitas === "semi") return "internal";
    return "privat";
}

function getVisibilitasLabel(visibilitas: OneTapVisibilitas | undefined): string {
    return VISIBILITAS_OPTIONS.find((item) => item.value === normalizeVisibilitas(visibilitas))?.label ?? "-";
}

function formatDate(value: string): string {
    if (!value) return "-";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return "-";
    return new Intl.DateTimeFormat("id-ID", { day: "2-digit", month: "short", year: "numeric" }).format(date);
}

function PaginationFooter({
    currentPage,
    itemsPerPage,
    totalItems,
    totalPages,
    onPageChange,
}: {
    currentPage: number;
    itemsPerPage: number;
    totalItems: number;
    totalPages: number;
    onPageChange: (page: number) => void;
}) {
    if (totalItems === 0) return null;

    const start = (currentPage - 1) * itemsPerPage + 1;
    const end = Math.min(currentPage * itemsPerPage, totalItems);

    return (
        <div className="mt-3 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <p className="text-xs text-slate-500">
                Menampilkan <span className="font-semibold text-slate-700">{start}</span>
                {" - "}
                <span className="font-semibold text-slate-700">{end}</span> dari{" "}
                <span className="font-semibold text-slate-700">{totalItems}</span> data
            </p>
            <div className="flex items-center gap-1">
                {totalPages > 1 && (
                    <>
                        <Button
                            type="button"
                            variant="outline"
                            className="h-8 w-8 p-0"
                            onClick={() => onPageChange(currentPage - 1)}
                            disabled={currentPage === 1}
                            aria-label="Halaman sebelumnya"
                        >
                            <ChevronLeft size={16} />
                        </Button>
                        {getPageNumbers(currentPage, totalPages).map((page, index) =>
                            page === "..." ? (
                                <span key={`ellipsis-${index}`} className="px-1 text-xs text-slate-400">...</span>
                            ) : (
                                <Button
                                    key={page}
                                    type="button"
                                    variant={page === currentPage ? "outline" : "ghost"}
                                    className={`h-8 w-8 p-0 text-xs ${page === currentPage ? "border-slate-900 bg-accent" : ""}`}
                                    onClick={() => onPageChange(page)}
                                >
                                    {page}
                                </Button>
                            ),
                        )}
                        <Button
                            type="button"
                            variant="outline"
                            className="h-8 w-8 p-0"
                            onClick={() => onPageChange(currentPage + 1)}
                            disabled={currentPage === totalPages}
                            aria-label="Halaman berikutnya"
                        >
                            <ChevronRight size={16} />
                        </Button>
                    </>
                )}
            </div>
        </div>
    );
}

function getPageNumbers(currentPage: number, totalPages: number): Array<number | "..."> {
    if (totalPages <= 7) {
        return Array.from({ length: totalPages }, (_, index) => index + 1);
    }

    const pages: Array<number | "..."> = [1];
    const start = Math.max(2, currentPage - 1);
    const end = Math.min(totalPages - 1, currentPage + 1);

    if (start > 2) pages.push("...");
    for (let page = start; page <= end; page += 1) pages.push(page);
    if (end < totalPages - 1) pages.push("...");
    pages.push(totalPages);

    return pages;
}

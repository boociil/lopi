import AppLayout from "@/layouts/app-layout";
import { type User, type BreadcrumbItem, Satuan_Kerja_Tahun, Tim, Jurnal_Harian_Proyek, Setting_User, SharedData } from "@/types";
import { Head, usePage } from "@inertiajs/react";
import Heading from "@/components/heading";

import React, { useState, useMemo, useRef, useEffect } from "react";
import * as LucideIcons from "lucide-react";
import {
    Plus,
    Pencil,
    Trash2,
    Link as LinkIcon,
    Search,
    LayoutGrid,
    GripVertical,
    ExternalLink,
    Pin,
    PinOff,
    ChevronDown,
    Check,
    Filter,
} from "lucide-react";

// Import dnd-kit pikeun fitur Drag and Drop
import {
    DndContext,
    closestCenter,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
    DragEndEvent,
} from "@dnd-kit/core";
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    verticalListSortingStrategy,
    useSortable,
} from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader } from "@/components/ui/dialog";
import { useLoading } from "@/props/LoadingContext";
import { useApi } from "@/lib/API/useApi";
import { toast } from "sonner";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { DialogTitle } from "@radix-ui/react-dialog";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Rumah Link",
        href: "/lopi/rumah_link",
    },
];


interface Rumah_Link {
    id: string;
    title: string;
    url: string;
    color: string;
    icon: TIconOption;
    access_type: string;
    access_id: string;
    default_pin: '0' | '1';
    pembuat_username: string;
    satuan_kerja_tahun_id: string;
}

interface Grup {
    type: 'all' | 'satuan_kerja_tahun' | 'tim' | 'proyek' | 'user';
    id: string | null;
    satuan_kerja_tahun_id: string | undefined;
    tim_id: string | undefined;
    proyek_id: string | undefined;
    name: string;
    color: TColorOption | undefined;
    data: Satuan_Kerja_Tahun | Tim | Jurnal_Harian_Proyek | undefined;
}

interface Setting_User_Link_Pinned extends Setting_User {
    data: string[];
}

interface Setting_User_Link_Sort extends Setting_User {
    data: {
        [key: string]: number;
    };
}

function hashUuid(uuid: string) {
  let hash = 0;
  for (let i = 0; i < uuid.length; i++) {
    hash = uuid.charCodeAt(i) + ((hash << 5) - hash);
  }
  return Math.abs(hash);
}

function uuidToColor(uuid: string) {
  const index = hashUuid(uuid) % ColorOption.length;
  return ColorOption[index] as TColorOption;
}


export default function Index({
        data,
        satuan_kerja_tahun,
        tim,
        proyek,
        setting_pinned,
        setting_sort
    } : {
        data: Rumah_Link[],
        satuan_kerja_tahun: Satuan_Kerja_Tahun[],
        tim: Tim[],
        proyek: Jurnal_Harian_Proyek[],
        setting_pinned?: Setting_User_Link_Pinned,
        setting_sort?: Setting_User_Link_Sort
    }) {

    const axiosClient = useApi();
    const auth = usePage<SharedData>().props.auth;
    const {setIsLoading} = useLoading();
    const [isFirst, setIsFirst] = useState<boolean>(true);

    const group_ : Grup[] = [];

    group_.push({
        type: 'user' ,
        id: auth.user.username,
        satuan_kerja_tahun_id: undefined,
        tim_id:  undefined,
        proyek_id:  undefined,
        name: 'Pribadi',
        color: 'blue',
        data: undefined
    })

    satuan_kerja_tahun.forEach(element => {
        group_.push({
            type: 'satuan_kerja_tahun' ,
            id:  element.id,
            satuan_kerja_tahun_id: element.id,
            tim_id:  undefined,
            proyek_id:  undefined,
            name: element.nama,
            color: uuidToColor(element.id),
            data: element
        })
    });

    tim.forEach(element => {
        group_.push({
            type: 'tim' ,
            id:  element.id,
            satuan_kerja_tahun_id: element.satuan_kerja_tahun_id,
            tim_id:  element.id,
            proyek_id:  undefined,
            name: element.nama,
            color: uuidToColor(element.id),
            data: element
        })
    });

    proyek.forEach(element => {
        group_.push({
            type: 'proyek' ,
            id:  element.id,
            satuan_kerja_tahun_id: element.satuan_kerja_tahun_id,
            tim_id:  element.tim_id,
            proyek_id:  element.id,
            name: element.nama,
            color: uuidToColor(element.id),
            data: element
        })
    });


    if(setting_sort){
        data.sort((a:Rumah_Link, b:Rumah_Link ) => {
            if(setting_sort.data.hasOwnProperty(a.id) && setting_sort.data.hasOwnProperty(a.id)){
                if(setting_sort.data[a.id] < setting_sort.data[b.id]){
                    return -1
                }else if(setting_sort.data[a.id] > setting_sort.data[b.id]){
                    return 1
                }else{
                    return 0
                }
            }else if(setting_sort.data.hasOwnProperty(a.id) && !setting_sort.data.hasOwnProperty(a.id)){
                return -1
            }else if(!setting_sort.data.hasOwnProperty(a.id) && setting_sort.data.hasOwnProperty(a.id)){
                return 1
            }
            return 0
        })
    }


    const [groups, setGroups] = useState<Grup[]>(group_);

    const [links, setLinks] = useState<Rumah_Link[]>(data);

    const [pinnedIds, setPinnedIds] = useState<string[]>(setting_pinned ? setting_pinned.data : [])

    const [searchQuery, setSearchQuery] = useState("");
    const [activeGroup, setActiveGroup] = useState<Grup>({ type: "all", name: "Semua Kategori", id: null } as Grup);
    const [linkModal, setLinkModal] = useState<{ open: boolean, type: 'add' | 'edit' | null, data: Rumah_Link | null }>({ open: false, type: null, data: null });

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 8 } }),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;
        if (!over) return;
        if (active.id !== over?.id) {
            setLinks((items) => {
                const oldIndex = items.findIndex((i) => i.id === active.id);
                const newIndex = items.findIndex((i) => i.id === over.id);
                return arrayMove(items, oldIndex, newIndex);
            });
        }
    };

    useEffect(() => {
        if(isFirst){
            setIsFirst(false)
            return
        }
        const setting_sort_ids: {
            [key: string]: number;
        } = {}
        links.forEach((element, index) => {
            setting_sort_ids[element.id] = index
        });
        axiosClient.post(route("rumah_link-setting"), {'data_sort': setting_sort_ids, 'data_pinned': pinnedIds})
            .then(function (response: any) {
                toast.success('Berhasil mengupdate data')
            })
            .catch(function (error: any) {
                console.log(error)
                toast.success('Gagal mengupdate data')
            });
    }, [links, pinnedIds]);

    const togglePin = (id: string) => {
        if(pinnedIds.includes(id)){
            setPinnedIds(pinnedIds.filter(item => item !== id));
        }else{
            setPinnedIds(prevArray => [...prevArray, id]);
        }
    };

    const saveLink = () => {
        if(linkModal.type == 'add'){
            toast.info('Mencoba menambahkan link')
            setIsLoading(true)
            axiosClient.post(route("rumah_link-tambah"), linkModal.data)
                .then(function (response: any) {
                    setLinks(prevArray => [...prevArray, {...response.data.data} as Rumah_Link]);
                    setLinkModal({ open: false, type: null, data: null })
                    setIsLoading(false)
                    toast.success('Berhasil menambahkan link')
                })
                .catch(function (error: any) {
                    console.log(error)
                    setIsLoading(false)
                    toast.success('Gagal menambahkan link')
                });
        }else{
            if( linkModal.data){
                toast.info('Mencoba mengubah link')
                setIsLoading(true)
                axiosClient.post(route("rumah_link-ubah", {rumah_link: linkModal.data.id}), linkModal.data)
                    .then(function (response: any) {
                        setLinks(prevArray => prevArray.map(item =>
                            item.id === linkModal.data?.id
                                ? {...response.data.data} as Rumah_Link
                                : item
                        ));
                        setLinkModal({ open: false, type: null, data: null })
                        setIsLoading(false)
                        toast.success('Berhasil mengubah link')
                    })
                    .catch(function (error: any) {
                        console.log(error)
                        setIsLoading(false)
                        toast.success('Gagal mengubah link')
                    });
            }

        }

    };

    const deleteLink = (id: string) => {
        toast.info('Mencoba menghapus link')
        setIsLoading(true)
        axiosClient.post(route("rumah_link-hapus", {rumah_link: id}))
            .then(function (response: any) {
                setIsLoading(false)
                setLinks(links.filter(item => item.id !== id));
                toast.success('Berhasil menghapus link')
            })
            .catch(function (error: any) {
                console.log(error)
                setIsLoading(false)
                toast.success('Gagal menghapus link')
            });
    };

    const pinnedLinks = useMemo(() => links.filter((l) => l.default_pin == '1' || pinnedIds.includes(l.id)), [links, pinnedIds]);

    const filteredLinks = useMemo(() => {
        return links.filter((l) => {
            const matchesSearch =
                l.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
                l.url.toLowerCase().includes(searchQuery.toLowerCase());
            return matchesSearch;
        });
    }, [links, activeGroup, searchQuery]);

    const getColorClass = (color: TColorOption) => {
        const map = {
            indigo: "bg-indigo-100 text-indigo-600 ring-indigo-500",
            pink: "bg-pink-100 text-pink-600 ring-pink-500",
            orange: "bg-orange-100 text-orange-600 ring-orange-500",
            emerald: "bg-emerald-100 text-emerald-600 ring-emerald-500",
            blue: "bg-blue-100 text-blue-600 ring-blue-500",
        };
        return map[color] || map.indigo;
    };

    const filterOptions = useMemo(
        () => [{ type: "all", name: "Semua Kategori", id: null } as Grup, ...groups],
        [groups],
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Rumah Link" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="w-full">
                    <div className="overflow-hidden">
                        <div className="min-h-screen flex-1 flex flex-col text-slate-800 font-sans">
                            {/* Kolom Pilarian */}
                            <div className="w-full pt-10 pb-6 px-6 flex flex-col items-center shrink-0">
                                <div className="w-full max-w-2xl relative group">
                                    <div className="absolute inset-y-0 left-5 flex items-center pointer-events-none">
                                        <Search
                                            className="text-slate-400 group-focus-within:text-indigo-500 transition-colors"
                                            size={20}
                                        />
                                    </div>
                                    <input
                                        type="text"
                                        placeholder="Cari link ..."
                                        className="w-full pl-14 pr-6 py-4 bg-white border border-slate-200 rounded-full shadow-sm hover:shadow-md focus:shadow-lg focus:outline-none focus:ring-4 focus:ring-indigo-500/5 transition-all text-base text-slate-700"
                                        value={searchQuery}
                                        onChange={(e) =>
                                            setSearchQuery(e.target.value)
                                        }
                                    />
                                </div>
                                <div className="mt-4 flex gap-3">
                                    <Button
                                        size="sm"
                                        variant="secondary"
                                        onClick={() => {
                                            setLinkModal({
                                                open: true,
                                                type: 'add',
                                                data: {
                                                    id: '123456789123456789',
                                                    title: '',
                                                    url: '',
                                                    color: 'indigo',
                                                    icon: 'Link' as TIconOption,
                                                    access_type: 'user',
                                                    access_id: auth.user.username,
                                                    default_pin: '0',
                                                    pembuat_username: auth.user.username,
                                                    satuan_kerja_tahun_id: '',
                                                },
                                            });
                                        }}
                                    >
                                        <Plus size={16} /> Tambah Tautan
                                    </Button>
                                </div>
                            </div>

                            {/* Daftar Tautan */}
                            <div className="flex-1 overflow-y-auto px-6 pb-12">
                                {/* Aksés Gancang (Favorit) */}
                                {pinnedLinks.length > 0 &&
                                    searchQuery === "" && (
                                        <div className="max-w-6xl mx-auto mb-10">
                                            <div className="flex items-center gap-2 mb-4">
                                                <Pin
                                                    size={16}
                                                    className="text-amber-500 fill-amber-500"
                                                />
                                                <h3 className="text-sm font-bold text-slate-700 uppercase tracking-wide">
                                                    Favorit
                                                </h3>
                                            </div>
                                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                                {pinnedLinks.map((link) => (
                                                    <SortableLinkCard
                                                        key={`pinned-${link.id}`}
                                                        link={link}
                                                        pinnedId={pinnedIds}
                                                        onEdit={(l) => {
                                                            setLinkModal({
                                                                open: true,
                                                                type: 'edit',
                                                                data: {...l},
                                                            });
                                                        }}
                                                        onDelete={(id) => deleteLink (id)}
                                                        onTogglePin={togglePin}
                                                        colorClass={getColorClass(link.color as TColorOption,)}
                                                    />
                                                ))}
                                            </div>
                                            <div className="h-px bg-slate-200 mt-8" />
                                        </div>
                                    )}

                                <div className="max-w-6xl mx-auto">
                                    {/* Bagian Judul + Filter (Select2 Style) */}
                                    <div className="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                        <h2 className="text-xl font-bold text-slate-900 tracking-tight">
                                            Daftar Link
                                        </h2>

                                        <div className="flex items-center gap-3">
                                            <span className="text-xs font-bold text-slate-400 uppercase tracking-widest hidden sm:inline">
                                                Filter:
                                            </span>
                                            <CustomSelect
                                                options={filterOptions}
                                                value={activeGroup}
                                                onChange={(group: Grup) => {setActiveGroup(group)}}
                                            />
                                        </div>
                                    </div>

                                    <DndContext
                                        sensors={sensors}
                                        collisionDetection={closestCenter}
                                        onDragEnd={handleDragEnd}
                                    >
                                        <SortableContext
                                            items={filteredLinks.map(
                                                (l) => l.id,
                                            )}
                                            strategy={
                                                verticalListSortingStrategy
                                            }
                                        >
                                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                                {filteredLinks.length > 0 ? (
                                                    filteredLinks.map(
                                                        (link) => (
                                                            <SortableLinkCard
                                                                key={link.id}
                                                                link={link}
                                                                pinnedId={pinnedIds}
                                                                onEdit={(l) => {
                                                                    setLinkModal(
                                                                        {
                                                                            open: true,
                                                                            type: 'edit',
                                                                            data: {...l},
                                                                        },
                                                                    );
                                                                }}
                                                                onDelete={(id,) =>
                                                                    deleteLink (id)
                                                                }
                                                                onTogglePin={
                                                                    togglePin
                                                                }
                                                                colorClass={getColorClass(link.color as TColorOption)}
                                                            />
                                                        ),
                                                    )
                                                ) : (
                                                    <div className="col-span-full py-12 text-center border-2 border-dashed border-slate-200 rounded-xl">
                                                        <p className="text-slate-400 text-sm">
                                                            Belum ada link
                                                        </p>
                                                    </div>
                                                )}
                                            </div>
                                        </SortableContext>
                                    </DndContext>
                                </div>
                            </div>

                            <Dialog
                                open={linkModal.open}
                                onOpenChange={() =>
                                    setLinkModal({ open: false, type: null, data: null })
                                }

                            >
                                <DialogContent>
                                    <DialogHeader>
                                        <DialogTitle>
                                            {(linkModal.type == null) && ""}
                                            {(linkModal.type == 'add') &&  "Tambah Tautan"}
                                            {(linkModal.type == 'edit') &&  "Édit Tautan"}
                                        </DialogTitle>
                                    </DialogHeader>
                                    <form onSubmit={saveLink} className="space-y-4">
                                        <div className="space-y-1">
                                            <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">
                                                Nama & URL
                                            </label>
                                            <Input
                                                name="title"
                                                defaultValue={
                                                    linkModal.data?.title || ""
                                                }
                                                onChange={e => {
                                                    if(linkModal.data){
                                                        linkModal.data.title =  e.target.value
                                                    }
                                                }}
                                                required
                                                placeholder="Nama Link"
                                                className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm mb-2 outline-none focus:ring-2 focus:ring-indigo-500"
                                            />
                                            <Input
                                                name="url"
                                                defaultValue={
                                                    linkModal.data?.url || ""
                                                }
                                                onChange={e => {
                                                    if(linkModal.data){
                                                        linkModal.data.url =  e.target.value
                                                    }
                                                }}
                                                required
                                                type="url"
                                                placeholder="https://sulbar.bps.go.id/id"
                                                className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-indigo-500"
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">
                                                Kategori
                                            </label>
                                            <Select
                                                defaultValue={ (linkModal.data && linkModal.data.access_id) ? linkModal.data.access_id : undefined}
                                                onValueChange={(id) => {
                                                    const groupSelected = groups.find(item => item.id == id)
                                                    if(linkModal.data && groupSelected){
                                                        linkModal.data.access_type = groupSelected.type
                                                        linkModal.data.access_id = groupSelected.id as string
                                                    }
                                                }}
                                            >
                                                <SelectTrigger className="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-indigo-500">
                                                    <SelectValue placeholder="Select a user" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {groups.map((g) => (
                                                        g.id && (
                                                            <SelectItem key={g.id} value={ g.id }>
                                                                {g.name}
                                                            </SelectItem>
                                                        )
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="space-y-2">
                                            <label className="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">
                                                Icon
                                            </label>
                                            <div className="grid grid-cols-5 gap-2 p-2 bg-slate-50 border border-slate-200 rounded-xl">
                                                {ICON_OPTIONS.map((iconName) => (
                                                    <button
                                                        key={iconName}
                                                        type="button"
                                                        onClick={() => {
                                                            if(linkModal.data){
                                                                linkModal.data.icon = iconName as TIconOption
                                                                setLinkModal({... linkModal})
                                                            }
                                                        }}
                                                        className={`p-2 flex items-center justify-center rounded-lg transition-all ${(linkModal.data && linkModal.data.icon  == iconName) ? "bg-indigo-600 text-white shadow-md" : "bg-white text-slate-400 hover:text-slate-600"}`}
                                                    >
                                                        <DynamicIcon
                                                            name={iconName as TIconOption}
                                                            size={20}
                                                        />
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                        <Button
                                            type="submit"
                                            className="w-full py-3 mt-2"
                                        >
                                            Simpan Tautan
                                        </Button>
                                    </form>
                                </DialogContent>

                            </Dialog>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

const CustomSelect = ({ options, value, onChange }: {options: Grup[], value: Grup,  onChange(group: Grup) : void }) => {
    const [isOpen, setIsOpen] = useState(false);
    const containerRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (!containerRef.current) return;
            if (!containerRef.current.contains(event.target as Node)) {
                setIsOpen(false);
            }
        };

        document.addEventListener("mousedown", handleClickOutside);

        return () => {
            document.removeEventListener("mousedown", handleClickOutside);
        };
    }, []);


    const selectedOption = options.find((opt) => opt.id === value.id && opt.type === value.type);

    return (
        <div className="relative min-w-[180px]" ref={containerRef}>
            <button
                type="button"
                onClick={() => setIsOpen(!isOpen)}
                className="w-full flex items-center justify-between px-3 py-1.5 bg-white border border-slate-200 rounded-lg shadow-sm text-sm hover:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/10 transition-all"
            >
                <div className="flex items-center gap-2 truncate">
                    <Filter size={14} className="text-slate-400" />
                    <span className="text-slate-700 font-medium truncate">
                        {selectedOption
                            ? selectedOption.name
                            : "Pilih Kategori"}
                    </span>
                </div>
                <ChevronDown
                    size={14}
                    className={`text-slate-400 transition-transform ${isOpen ? "rotate-180" : ""}`}
                />
            </button>

            {isOpen && (
                <div className="absolute right-0 z-50 w-full mt-2 bg-white border border-slate-100 rounded-xl shadow-xl overflow-hidden animate-in fade-in zoom-in-95 duration-100">
                    <div className="max-h-60 overflow-y-auto">
                        {options.map((option) => (
                            <button
                                key={option.id}
                                onClick={() => {
                                    onChange(option);
                                    setIsOpen(false);
                                }}
                                className={`w-full flex items-center justify-between px-4 py-2 text-sm transition-colors hover:bg-slate-50 ${(value.id === option.id && value.type === option.type) ? "bg-indigo-50 text-indigo-600 font-semibold" : "text-slate-600"}`}
                            >
                                <div className="flex items-center gap-2 truncate">
                                    {option.type !== "all" && (
                                        <span
                                            className={`w-2 h-2 rounded-full shrink-0 ${
                                                option.color === "indigo"
                                                    ? "bg-indigo-500"
                                                    : option.color === "pink"
                                                      ? "bg-pink-500"
                                                      : option.color ===
                                                          "orange"
                                                        ? "bg-orange-500"
                                                        : option.color ===
                                                            "emerald"
                                                          ? "bg-emerald-500"
                                                          : "bg-blue-500"
                                            }`}
                                        />
                                    )}
                                    <span className="truncate">
                                        {option.name}
                                    </span>
                                </div>
                                {(value.id === option.id && value.type === option.type) && <Check size={14} />}
                            </button>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
};

const ICON_OPTIONS = [
    "Link",
    "Globe",
    "Github",
    "Youtube",
    "Twitter",
    "Instagram",
    "Facebook",
    "Slack",
    "MessageCircle",
    "Video",
    "Music",
    "Briefcase",
    "Layers",
    "Code",
    "Hash",
];

export type TIconOption =
	"Link" |
    "Globe" |
    "Github" |
    "Youtube"|
    "Twitter"|
    "Instagram"|
    "Facebook"|
    "Slack"|
    "MessageCircle"|
    "Video"|
    "Music"|
    "Briefcase"|
    "Layers"|
    "Code"|
    "Hash";

const ColorOption = [
	"indigo" ,
    "pink" ,
    "orange" ,
    "emerald",
    "blue"
]


export type TColorOption =
	"indigo" |
    "pink" |
    "orange" |
    "emerald"|
    "blue";

const DynamicIcon = ({ name, size = 18, className = "" } : { name: TIconOption, size?: number, className?: string}) => {
    const IconComponent = LucideIcons[name] || LinkIcon;
    return <IconComponent size={size} className={className} />;
};



const SortableLinkCard = ({
    link,
    onEdit,
    onDelete,
    onTogglePin,
    colorClass,
    pinnedId
}: {link: Rumah_Link, onEdit: (link: Rumah_Link) => void, onDelete: (id: string) => void, onTogglePin: (id: string) => void,  colorClass: string, pinnedId: string[]}) => {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: link.id });
    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        zIndex: isDragging ? 50 : 0,
        opacity: isDragging ? 0.5 : 1,
    };
    let hostname = "";
    try {
        hostname = new URL(link.url).hostname;
    } catch (e) {
        hostname = link.url;
    }

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={`group bg-white border ${ (link.default_pin == '1' || pinnedId.includes(link.id)) ? "border-amber-200 shadow-sm" : "border-slate-200"} rounded-xl p-3 flex items-center gap-3 transition-all hover:shadow-md hover:border-indigo-300 ${isDragging ? "shadow-xl" : ""}`}
        >
            <button
                {...attributes}
                {...listeners}
                className="cursor-grab active:cursor-grabbing p-1 text-slate-300 hover:text-slate-500 transition-colors shrink-0"
            >
                <GripVertical size={18} />
            </button>
            <div
                className={`shrink-0 w-10 h-10 rounded-lg flex items-center justify-center ${colorClass.split(" ")[0]} ${colorClass.split(" ")[1]}`}
            >
                <DynamicIcon name={link.icon as TIconOption|| "Link" as TIconOption} />
            </div>
            <div className="flex-1 min-w-0">
                <a
                    href={link.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="block group/title outline-none"
                >
                    <div className="flex items-center gap-1.5">
                        <h3 className="font-semibold text-slate-800 text-sm truncate leading-tight group-hover/title:text-indigo-600 transition-colors">
                            {link.title}
                        </h3>
                        {(link.default_pin == '1' || pinnedId.includes(link.id)) && (
                            <Pin
                                size={10}
                                className="text-amber-500 fill-amber-500 shrink-0"
                            />
                        )}
                    </div>
                    <p className="text-[11px] text-slate-400 truncate flex items-center gap-1 mt-0.5 group-hover/title:text-slate-500">
                        {hostname}
                    </p>
                </a>
            </div>
            <div className="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                <button
                    onClick={(e) => {
                        e.preventDefault();
                        onTogglePin(link.id);
                    }}
                    className={`p-1.5 rounded-md transition-colors ${(link.default_pin == '1' || pinnedId.includes(link.id)) ? "text-amber-500 hover:bg-amber-50" : "text-slate-400 hover:text-amber-500 hover:bg-amber-50"}`}
                    title={(link.default_pin == '1' || pinnedId.includes(link.id))? "Leupaskeun Pin" : "Pin Tautan"}
                >
                    {(link.default_pin == '1' || pinnedId.includes(link.id)) ? <PinOff size={14} /> : <Pin size={14} />}
                </button>
                <button
                    onClick={(e) => {
                        e.preventDefault();
                        onEdit(link);
                    }}
                    className="p-1.5 text-slate-400 hover:text-indigo-600 rounded-md hover:bg-indigo-50 transition-colors"
                    title="Édit"
                >
                    <Pencil size={14} />
                </button>
                <button
                    onClick={(e) => {
                        e.preventDefault();
                        onDelete(link.id);
                    }}
                    className="p-1.5 text-slate-400 hover:text-red-500 rounded-md hover:bg-red-50 transition-colors"
                    title="Hapus"
                >
                    <Trash2 size={14} />
                </button>
            </div>
        </div>
    );
};

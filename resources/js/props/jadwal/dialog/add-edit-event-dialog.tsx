import { addMinutes, set } from "date-fns";
import { useEffect, useMemo, useState } from "react";
import { useForm } from "@inertiajs/react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import {
    Modal,
    ModalClose,
    ModalContent,
    ModalFooter,
    ModalHeader,
    ModalTitle,
    ModalTrigger,
} from "@/components/ui/responsive-modal";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { useCalendar } from "@/components/calender_modules/contexts/calendar-context";
import { useDisclosure } from "@/components/calender_modules/hooks";
import { Label } from "@/components/ui/label";
import InputError from "@/components/input-error";
import { DatePicker22 } from "@/components/ui/date-picker";
import {
    capitalizeWords,
    formatDate,
    formatTime,
} from "@/components/calender_modules/helpers";
import { Time24Picker } from "@/components/ui/time24-picker";
import {
    pairIEventDenganUser,
    jadwal_req,
    JadwalIEvent,
    produceJadwalIEvent,
    type jadwal,
    SettingCalenderJadwal,
} from "../types";
import { CalenderUser, IEvent } from "@/components/calender_modules/interfaces";
import { Add_Edit_Event_IProps } from "@/components/calender_modules/dialogs/props-dialog";
import { progress as progressIndikator } from "@inertiajs/react";
import { defineStepper } from "@stepperize/react";
import { Separator } from "@/components/ui/separator";
import {
    CheckCircle,
    ChevronLeft,
    ChevronRight,
    ChevronsUpDown,
    FileText,
    MapPin,
    Trash2,
    Users,
    Video,
} from "lucide-react";
import { Textarea } from "@/components/ui/textarea";
import { Input } from "@/components/ui/input";
import { Popover, PopoverTrigger } from "@/components/ui/popover";
import { PopoverContent } from "@radix-ui/react-popover";
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from "@/components/ui/command";

export function AddEditEventDialog({
    children,
    startDate,
    startTime,
    event,
    onUpdateOpenChange,
}: Add_Edit_Event_IProps) {
    const { isOpen, onClose, onToggle } = useDisclosure();
    const {
        users,
        addEvent,
        updateEvent,
        settingCalender,
        use24HourFormat,
        axiosClient,
        havePeran,
        userDict,
    } = useCalendar();
    const isEditing = !!event;

    // const [tipe, setTipe] = useState<string>("sekali");

    const initialDates = useMemo(() => {
        if (!isEditing && !event) {
            if (!startDate) {
                const now = new Date();
                return { startDate: now, endDate: addMinutes(now, 30) };
            }
            const start = startTime
                ? set(new Date(startDate), {
                      hours: startTime.hour,
                      minutes: startTime.minute,
                      seconds: 0,
                  })
                : new Date(startDate);
            const end = addMinutes(start, 30);
            return { startDate: start, endDate: end };
        }

        return {
            startDate: new Date(event.startDate),
            endDate: new Date(event.endDate),
        };
    }, [startDate, startTime, event, isEditing]);

    const { data, setData, progress, errors, reset, clearErrors, setError } =
        useForm<Required<jadwal_req>>({
            id: "12345",
            kegiatan: "",
            description: "",
            tipe: "sekali",

            tanggal: formatDate(new Date().toISOString()),
            tanggal_awal: formatDate(new Date().toISOString()),
            tanggal_akhir: formatDate(new Date().toISOString()),
            waktu_awal: "07:30:00",
            waktu_akhir: "16:00:00",
            status: "menunggu",

            ruang_status: "tidak",
            ruang: null,

            zoom_status: "tidak",
            zoom_password: null,
            zoom_akun: null,
            zoom_langsung_join_url: null,
            peserta: [],
        });

    const submit: React.FormEventHandler = (e) => {
        e.preventDefault();
        if (!isEditing) {
            toast.info("Mencoba menambahkan data baru");
            progressIndikator.start();
            axiosClient
                .post(route("jadwal-tambah"), data)
                .then(function (response: any) {
                    try {
                        const return_data: JadwalIEvent[] = produceJadwalIEvent(response.data.data as jadwal[]);
                        pairIEventDenganUser(return_data as JadwalIEvent[], users as CalenderUser[])

                        addEvent(return_data[0]);
                        progressIndikator.finish();
                        toast.success("Berhasil menambahkan data baru");
                        clearErrors();
                        onToggle();
                    } catch (error) {
                        console.log(error);
                        progressIndikator.finish();
                        toast.error("Gagal menambahkan data baru");
                    }
                })
                .catch(function (error: any) {
                    console.log(error);
                    progressIndikator.finish();
                    toast.error("Gagal menambahkan data baru");

                    clearErrors();
                    if (error.response?.status === 422) {
                        Object.keys(error.response.data.errors).forEach(
                            (key) => {
                                setError(
                                    key,
                                    error.response.data.errors[key][0],
                                );
                            },
                        );
                    }
                });
        } else {
            toast.info("Mencoba mengubah data");
            progressIndikator.start();
            axiosClient
                .post(route("jadwal-ubah", { jadwal: event.id }), data)
                .then(function (response: any) {
                    try {
                        const return_data: JadwalIEvent[] = produceJadwalIEvent(response.data.data as jadwal[]);
                        pairIEventDenganUser(return_data as JadwalIEvent[], users as CalenderUser[])
                        updateEvent(return_data[0]);

                        progressIndikator.finish();
                        toast.success("Berhasil mengubah data");
                        clearErrors();
                        onToggle();
                        if (onUpdateOpenChange) {
                            onUpdateOpenChange(return_data[0]);
                        }
                    } catch (error) {
                        console.log(error);
                        progressIndikator.finish();
                        toast.error("Gagal mengubah data");
                    }
                })
                .catch(function (error: any) {
                    console.log(error);
                    progressIndikator.finish();
                    toast.error("Gagal mengubah data");

                    clearErrors();
                    if (error.response?.status === 422) {
                        Object.keys(error.response.data.errors).forEach(
                            (key) => {
                                setError(
                                    key,
                                    error.response.data.errors[key][0],
                                );
                            },
                        );
                    }
                });
        }
    };

    useEffect(() => {
        if (event && (event as JadwalIEvent).jadwal_ori != null) {
            const event_: JadwalIEvent = event as JadwalIEvent;
            setData("id", event.id);
            setData("kegiatan", event.title);
            setData("description", event.description)
            setData("tipe", "sekali");
            if (event._startDate != event._endDate) {
                setData("tipe", "berulang");
            }
            setData("tanggal_awal", event._startDate);
            setData("tanggal_akhir", event._endDate);
            setData("tanggal", event.date);
            setData("waktu_awal", formatTime(event.startDate, use24HourFormat));
            setData("waktu_akhir", formatTime(event.endDate, use24HourFormat));

            setData("peserta", []);
            (event as JadwalIEvent).jadwal_ori.peserta.forEach(element => {
                setData("peserta", [...data.peserta, element.username]);
            });

            if ((event as JadwalIEvent).jadwal_ori) {
                setData("zoom_status", event_.jadwal_ori.zoom_status);
                setData(
                    "zoom_langsung_join_url",
                    event_.jadwal_ori.zoom_langsung_join_url,
                );

                if ((event as JadwalIEvent).jadwal_ori.zoom_pinjam) {
                    setData(
                        "zoom_akun",
                        event_.jadwal_ori.zoom_pinjam?.zoom_akun as
                            | "statsulbar"
                            | "sp2020",
                    );
                    setData(
                        "zoom_password",
                        event_.jadwal_ori.zoom_pinjam?.password as
                            | string
                            | null,
                    );
                } else {
                    setData("zoom_password", null);
                    setData("zoom_akun", null);
                }

                setData("ruang_status", event_.jadwal_ori.ruang_status);
                setData("ruang", event_.jadwal_ori.ruang);
            } else {
                setData("zoom_status", "tidak");
                setData("zoom_password", null);
                setData("zoom_akun", null);

                setData("ruang_status", "tidak");
                setData("ruang", null);
            }
        } else {
            setData("id", "xxxx-xxxx-xxxx");
            setData("kegiatan", "");
            setData("tipe", "sekali");
            setData("tanggal_awal", formatDate(initialDates.startDate));
            setData("tanggal_akhir", formatDate(initialDates.endDate));
            setData("tanggal", formatDate(initialDates.startDate));
            setData(
                "waktu_awal",
                formatTime(initialDates.startDate, use24HourFormat),
            );
            setData(
                "waktu_akhir",
                formatTime(initialDates.endDate, use24HourFormat),
            );

            setData("ruang_status", "tidak");
            setData("ruang", null);

            setData("zoom_status", "tidak");
            setData("zoom_password", null);
            setData("zoom_akun", null);
        }
        setStep(1);
    }, [event, initialDates]);

    const [step, setStep] = useState(1);
    const steps = [
        { id: 1, title: "Info", icon: <FileText size={18} /> },
        { id: 2, title: "Ruang", icon: <MapPin size={18} /> },
        { id: 3, title: "Zoom", icon: <Video size={18} /> },
        { id: 4, title: "Peserta", icon: <Users size={18} /> },
    ];

    function deleteUser(username: string) {
        setData(
            "peserta",
            data.peserta.filter((item) => item != username),
        );
    }

    const [openPengguna, setOpenPengguna] = useState(false);

    const nextStep = () => setStep((s) => Math.min(s + 1, 4));
    const prevStep = () => setStep((s) => Math.max(s - 1, 1));

    const renderStepContent = () => {
        switch (step) {
            case 1:
                return (
                    <div className="space-y-2 animate-in fade-in slide-in-from-right-4 duration-300">
                        <div>
                            <label className="block text-xs font-bold text-slate-700 mb-1.5">
                                Nama Kegiatan
                            </label>
                            <Input
                                type="text"
                                className="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none"
                                value={data.kegiatan}
                                onChange={(e) =>
                                    setData("kegiatan", e.target.value)
                                }
                                placeholder="Masukkan nama kegiatan"
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-bold text-slate-700 mb-1">
                                Deskripsi
                            </label>
                            <Textarea
                                className="w-full px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none"
                                rows={2}
                                value={data.description}
                                onChange={(e) =>
                                    setData("description", e.target.value)
                                }
                                placeholder="Detail kegiatan..."
                            ></Textarea>
                        </div>
                        <div>
                            <label className="block text-[10px] font-bold text-slate-700 uppercase tracking-wider mb-2">
                                Tipe
                            </label>
                            <div className="grid grid-cols-2 gap-3">
                                <button
                                    type="button"
                                    onClick={() => setData("tipe", "sekali")}
                                    className={`py-2 rounded-xl text-xs font-bold border transition-all ${data.tipe === "sekali" ? "bg-blue-600 border-blue-600 text-white" : "bg-white border-slate-200 text-slate-500"}`}
                                >
                                    Sekali
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setData("tipe", "berulang")}
                                    className={`py-2 rounded-xl text-xs font-bold border transition-all ${data.tipe === "berulang" ? "bg-blue-600 border-blue-600 text-white" : "bg-white border-slate-200 text-slate-500"}`}
                                >
                                    Berulang
                                </button>
                            </div>
                        </div>

                        {data.tipe === "sekali" ? (
                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1.5">
                                    Tanggal
                                </label>
                                <DatePicker22
                                    className="w-full px-4 py-2.5 rounded-xl border border-slate-200 outline-none text-sm"
                                    value={initialDates.startDate}
                                    setValue={(value) => {
                                        setData(
                                            "tanggal",
                                            formatDate(value.toISOString()),
                                        );
                                        setData(
                                            "tanggal_awal",
                                            formatDate(value.toISOString()),
                                        );
                                    }}
                                    modal={true}
                                />
                                <InputError message={errors.tanggal} />
                                <InputError message={errors.tanggal_awal} />
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1.5">
                                        Tanggal Awal
                                    </label>
                                    <DatePicker22
                                        className="w-full px-4 py-2.5 rounded-xl border border-slate-200 outline-none text-sm"
                                        value={initialDates.startDate}
                                        setValue={(value) => {
                                            setData(
                                                "tanggal",
                                                formatDate(value.toISOString()),
                                            );
                                            setData(
                                                "tanggal_awal",
                                                formatDate(value.toISOString()),
                                            );
                                        }}
                                        modal={true}
                                    />
                                    <InputError message={errors.tanggal} />
                                    <InputError message={errors.tanggal_awal} />
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1.5">
                                        Tanggal Akhir
                                    </label>
                                    <DatePicker22
                                        className="w-full px-4 py-2.5 rounded-xl border border-slate-200 outline-none text-sm"
                                        value={initialDates.endDate}
                                        setValue={(value) => {
                                            setData(
                                                "tanggal_akhir",
                                                formatDate(value.toISOString()),
                                            );
                                        }}
                                        modal={true}
                                    />
                                    <InputError
                                        message={errors.tanggal_akhir}
                                    />
                                </div>
                            </div>
                        )}
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1.5">
                                    Mulai
                                </label>
                                <Time24Picker
                                    className="w-full px-4 py-2.5 rounded-xl border border-slate-200 outline-none text-sm"
                                    value={
                                        new Date(
                                            `${data.tanggal}T${data.waktu_awal}`,
                                        )
                                    }
                                    setValue={(value) => {
                                        setData("waktu_awal", value);
                                    }}
                                    modal={true}
                                />
                                <InputError message={errors.waktu_awal} />
                            </div>
                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1.5">
                                    Selesai
                                </label>
                                <Time24Picker
                                    className="w-full px-4 py-2.5 rounded-xl border border-slate-200 outline-none text-sm"
                                    value={
                                        new Date(
                                            `${data.tanggal}T${data.waktu_akhir}`,
                                        )
                                    }
                                    setValue={(value) => {
                                        setData("waktu_akhir", value);
                                    }}
                                    modal={true}
                                />
                                <InputError message={errors.waktu_akhir} />
                            </div>
                        </div>
                    </div>
                );
            case 2:
                return (
                    <div className="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                        <div className="grid grid-cols-1 gap-3">
                            {!havePeran("ruang") && (
                                <>
                                    {["tidak", "menunggu"].map((status) => (
                                        <Label
                                            key={status}
                                            className={`flex items-center p-4 border rounded-2xl cursor-pointer transition-all ${data.ruang_status === status ? "border-blue-500 bg-blue-50 ring-2 ring-blue-200" : "border-slate-200 hover:bg-slate-50"}`}
                                        >
                                            <Input
                                                type="radio"
                                                className="hidden"
                                                name="ruang_status"
                                                value={status}
                                                checked={
                                                    data.ruang_status === status
                                                }
                                                onChange={(e) => {
                                                    setData(
                                                        "ruang_status",
                                                        e.target.value as
                                                            | "tidak"
                                                            | "menunggu",
                                                    );
                                                    if (
                                                        e.target.value ==
                                                        "tidak"
                                                    ) {
                                                        setData("ruang", null);
                                                    }
                                                }}
                                            />
                                            <div className="flex-1">
                                                <p className="font-black text-slate-800">
                                                    {status === "tidak"
                                                        ? "Tanpa Ruangan"
                                                        : "Pinjam Ruangan"}
                                                </p>
                                                <p className="text-xs text-slate-500">
                                                    {status === "tidak"
                                                        ? "Kegiatan dilaksanakan daring atau diluar kantor"
                                                        : "Memerlukan persetujuan admin ruangan"}
                                                </p>
                                            </div>
                                            {data.ruang_status === status && (
                                                <CheckCircle
                                                    className="text-blue-600"
                                                    size={20}
                                                />
                                            )}
                                        </Label>
                                    ))}
                                </>
                            )}
                        </div>
                        {(data.ruang_status === "menunggu" ||
                            data.ruang_status === "disetujui") && (
                            <div className="animate-in slide-in-from-top-2">
                                <label className="block text-sm font-bold text-slate-700 mb-1">
                                    Pilih Ruangan
                                </label>
                                <Select
                                    name="ruang"
                                    required
                                    value={data.ruang as string | undefined}
                                    onValueChange={(value) => {
                                        setData("ruang", value);
                                    }}
                                >
                                    <SelectTrigger
                                        name="ruang"
                                        className="w-full px-3 py-2.5 border border-slate-300 rounded-xl outline-none"
                                    >
                                        <SelectValue placeholder="Pilih Tipe" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            {(
                                                settingCalender as SettingCalenderJadwal
                                            ).ruang.map((item, index) => (
                                                // Each element in a list must have a unique 'key' prop
                                                <SelectItem value={item}>
                                                    {capitalizeWords(item)}
                                                </SelectItem>
                                            ))}
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                            </div>
                        )}
                    </div>
                );
            case 3:
                return (
                    <div className="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                        <div className="grid grid-cols-1 gap-3">
                            {[
                                {
                                    id: "tidak",
                                    title: "Tidak Ada Zoom",
                                    desc: "Rapat offline murni",
                                },
                                {
                                    id: "langsung",
                                    title: "Link Zoom Langsung",
                                    desc: "Masukkan link zoom yang sudah Anda miliki",
                                },
                                {
                                    id: "menunggu",
                                    title: "Pinjam Akun Zoom",
                                    desc: "Request peminjaman akun zoom kantor",
                                },
                            ].map((opt) => (
                                <Label
                                    key={opt.id}
                                    className={`flex items-center p-4 border rounded-2xl cursor-pointer transition-all ${data.zoom_status === opt.id ? "border-blue-500 bg-blue-50 ring-2 ring-blue-200" : "border-slate-200 hover:bg-slate-50"}`}
                                >
                                    <Input
                                        type="radio"
                                        className="hidden"
                                        name="zoom_status"
                                        value={opt.id}
                                        checked={data.zoom_status === opt.id}
                                        onChange={(e) =>
                                            setData(
                                                "zoom_status",
                                                e.target.value as
                                                    | "tidak"
                                                    | "langsung"
                                                    | "menunggu"
                                                    | "disetujui"
                                                    | "ditolak",
                                            )
                                        }
                                    />
                                    <div className="flex-1">
                                        <p className="font-black text-slate-800">
                                            {opt.title}
                                        </p>
                                        <p className="text-xs text-slate-500">
                                            {opt.desc}
                                        </p>
                                    </div>
                                    {data.zoom_status === opt.id && (
                                        <CheckCircle
                                            className="text-blue-600"
                                            size={20}
                                        />
                                    )}
                                </Label>
                            ))}
                        </div>
                        {data.zoom_status === "langsung" && (
                            <div className="animate-in slide-in-from-top-2">
                                <label className="block text-sm font-bold text-slate-700 mb-1">
                                    Link Zoom Meeting
                                </label>
                                <input
                                    type="url"
                                    className="w-full px-3 py-2.5 border border-slate-300 rounded-xl outline-none text-sm"
                                    placeholder="https://zoom.us/j/..."
                                    value={data.zoom_langsung_join_url || ""}
                                    onChange={(e) =>
                                        setData(
                                            "zoom_langsung_join_url",
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        )}
                        {data.zoom_status == "menunggu" && (
                            <>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 mb-1">
                                            Password Meeting
                                        </label>
                                        <input
                                            type="text"
                                            name="password"
                                            maxLength={10}
                                            required
                                            className="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none"
                                            value={data.zoom_password || ""}
                                            onChange={(e) =>
                                                setData(
                                                    "zoom_password",
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    {havePeran("zoom") && (
                                        <div>
                                            <label className="block text-sm font-medium text-slate-700 mb-1">
                                                Zoom Akun
                                            </label>
                                            <Select
                                                name="tipe"
                                                required
                                                value={data.zoom_akun as string}
                                                onValueChange={(value) => {
                                                    setData(
                                                        "zoom_akun",
                                                        value as
                                                            | "statsulbar"
                                                            | "sp2020"
                                                            | null,
                                                    );
                                                }}
                                            >
                                                <SelectTrigger
                                                    name="zoom_status"
                                                    className="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary outline-no"
                                                >
                                                    <SelectValue placeholder="Pilih Zoom Akun" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectGroup>
                                                        <SelectItem value="statsulbar">
                                                            statsulbar
                                                        </SelectItem>
                                                        <SelectItem value="sp2020">
                                                            sp2020
                                                        </SelectItem>
                                                    </SelectGroup>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    )}
                                </div>
                            </>
                        )}
                    </div>
                );
            case 4:
                return (
                    <div className="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300">
                        <div>
                            <label className="block text-sm font-bold text-slate-700 mb-2">
                                Tambah Peserta
                            </label>
                            <div className="flex gap-2">
                                <Popover
                                    open={openPengguna}
                                    onOpenChange={setOpenPengguna}
                                    modal={true}
                                >
                                    <PopoverTrigger asChild>
                                        <Button
                                            variant="outline"
                                            role="combobox"
                                            className="justify-between text-secondary w-full"
                                        >
                                            Pilih Target...
                                            <ChevronsUpDown className="opacity-50" />
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent className="p-0 w-full shadow-2xl">
                                        <Command>
                                            <CommandInput
                                                placeholder="Cari Pengguna..."
                                                className="h-9"
                                            />
                                            <CommandList>
                                                <CommandEmpty>
                                                    Tidak ditemukan.
                                                </CommandEmpty>
                                                <CommandGroup>
                                                    {users.map((item) => (
                                                        <CommandItem
                                                            key={item.username+' '+item.name}
                                                            value={
                                                                item.username
                                                            }
                                                            onSelect={(
                                                                currentValue,
                                                            ) => {
                                                                setData(
                                                                    "peserta",
                                                                    [
                                                                        ...data.peserta,
                                                                        currentValue,
                                                                    ],
                                                                );
                                                                // setData("username", currentValue === data.username ? "" : currentValue)
                                                                setOpenPengguna(
                                                                    false,
                                                                );
                                                            }}
                                                        >
                                                            {item.name}
                                                            {/* {userDict.has(item.pemilik_username) ? userDict.get(item.pemilik_username) : item.pemilik_username} */}
                                                            {/* <Check
                                                        className={cn(
                                                        "ml-auto",
                                                            data.username === item.pemilik_username ? "opacity-100" : "opacity-0"
                                                        )}
                                                    /> */}
                                                        </CommandItem>
                                                    ))}
                                                </CommandGroup>
                                            </CommandList>
                                        </Command>
                                    </PopoverContent>
                                </Popover>
                            </div>
                        </div>

                        <div className="border border-slate-200 rounded-2xl overflow-hidden bg-slate-50/30">
                            <table className="w-full text-sm text-left">
                                <thead className="bg-slate-50 text-slate-500 font-bold uppercase text-[10px] tracking-widest">
                                    <tr>
                                        <th className="px-4 py-3">
                                            Nama Peserta
                                        </th>
                                        <th className="px-4 py-3 text-center w-10"></th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {data.peserta.map((item, index) => (
                                        <tr>
                                            <td className="px-4 py-3 font-semibold text-slate-700">
                                                {userDict.has(item)
                                                    ? userDict.get(item)?.name
                                                    : "-"}
                                            </td>
                                            <td className="px-4 py-3 text-center">
                                                <Trash2
                                                    onClick={(e) =>
                                                        deleteUser(item)
                                                    }
                                                    size={16}
                                                    className="text-red-400 cursor-pointer mx-auto"
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                );
            default:
                return null;
        }
    };

    return (
        <Modal open={isOpen} onOpenChange={onToggle} modal={false}>
            <ModalTrigger asChild>{children}</ModalTrigger>
            <ModalContent>
                <ModalHeader>
                    <ModalTitle>
                        {isEditing ? (
                            <>
                                Ubah{" "}
                                {capitalizeWords(settingCalender.name_event)}
                            </>
                        ) : (
                            <>
                                Tambah{" "}
                                {capitalizeWords(settingCalender.name_event)}{" "}
                                Baru
                            </>
                        )}
                        <div className="flex justify-between relative px-2 mt-3 mb-3">
                            {steps.map((s) => (
                                <div
                                    key={s.id}
                                    className="relative z-10 flex flex-col items-center gap-2 group"
                                >
                                    <div
                                        className={`w-10 h-10 rounded-full flex items-center justify-center transition-all border-2
                                        ${
                                            step === s.id
                                                ? "bg-blue-600 border-blue-600 text-white ring-4 ring-blue-50 shadow-lg shadow-blue-200"
                                                : step > s.id
                                                  ? "bg-green-500 border-green-500 text-white shadow-lg shadow-green-100"
                                                  : "bg-white border-slate-200 text-slate-400"
                                        }`}
                                    >
                                        {step > s.id ? (
                                            <CheckCircle size={20} />
                                        ) : (
                                            s.icon
                                        )}
                                    </div>
                                    <span
                                        className={`text-[10px] uppercase font-black tracking-tighter ${step >= s.id ? "text-slate-800" : "text-slate-400"}`}
                                    >
                                        {s.title}
                                    </span>
                                </div>
                            ))}
                        </div>

                        <div className=" mt-2 w-full h-0.5 bg-slate-100 -translate-y-1/2 z-0"></div>
                    </ModalTitle>
                </ModalHeader>

                <form className="flex flex-col gap-0 mt-5" onSubmit={submit}>
                    <div className="">
                        <div className="p-2">{renderStepContent()}</div>
                    </div>
                    <div className="">
                        <ModalFooter className="flex justify-end gap-2 m-0">
                            <ModalClose asChild>
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="text-accent-foreground"
                                >
                                    Cancel
                                </Button>
                            </ModalClose>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={prevStep}
                                disabled={step == 1}
                                className="text-accent-foreground"
                            >
                                Kembali
                            </Button>

                            {step != 4 ? (
                                <>
                                    <Button type="button" onClick={nextStep}>
                                        Next
                                    </Button>
                                </>
                            ) : (
                                <Button type="submit">
                                    {isEditing ? "Simpan Perubahan" : "Buat"}
                                </Button>
                            )}
                        </ModalFooter>
                    </div>
                </form>
            </ModalContent>
        </Modal>
    );
}

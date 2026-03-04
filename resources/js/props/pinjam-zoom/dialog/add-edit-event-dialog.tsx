import { addMinutes, set } from "date-fns";
import { useEffect, useMemo } from "react";
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
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { useCalendar } from "@/components/calender_modules/contexts/calendar-context";
import { useDisclosure } from "@/components/calender_modules/hooks";
import { Label } from "@/components/ui/label";
import InputError from "@/components/input-error";
import { SelectGroup } from "@radix-ui/react-select";
import { DatePicker22 } from "@/components/ui/date-picker";
import {
    capitalizeWords,
    formatDate,
    formatTime
} from "@/components/calender_modules/helpers";
import { Time24Picker } from "@/components/ui/time24-picker";
import { pairIEventDenganUser, pinjam_zoom_req, PinjamZoomIEvent, producePinjamZoomIEvent, type pinjam_zoom } from "../types";
import { CalenderUser, IEvent } from "@/components/calender_modules/interfaces";
import { Add_Edit_Event_IProps } from "@/components/calender_modules/dialogs/props-dialog";
import { progress as progressIndikator } from '@inertiajs/react'
import { Clock } from "lucide-react";

export function AddEditEventDialog({
    children,
    startDate,
    startTime,
    event,
    onUpdateOpenChange,
}: Add_Edit_Event_IProps) {
    const { isOpen, onClose, onToggle } = useDisclosure();
    const { users, addEvent, updateEvent, settingCalender, use24HourFormat, axiosClient, canApprove, havePeran } = useCalendar();
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

    const { data, setData, progress, errors, reset, clearErrors, setError } = useForm<
        Required<pinjam_zoom_req>
    >({
        id: "12345",
        kegiatan:  "Peminjaman Zoom",
        tipe: 'string',
        password: 'zoom',
        tanggal: formatDate(new Date().toISOString()),
        tanggal_awal: formatDate(new Date().toISOString()),
        tanggal_akhir: formatDate(new Date().toISOString()),
        waktu_awal: "07:30:00",
        waktu_akhir: "16:00:00",
        status: 'menunggu',
        pembuat_username: '',
        confirm_username: null,
        zoom_akun: null,
    });

    const submit: React.FormEventHandler = (e) => {
        e.preventDefault();
        if (!isEditing) {
            toast.info('Mencoba menambahkan data baru')
            progressIndikator.start()
            axiosClient.post(route('pinjam-zoom-tambah'), data)
                .then(function (response: any) {
                    try {
                        const return_data: IEvent[] = producePinjamZoomIEvent([response.data.data] as pinjam_zoom[]);
                        pairIEventDenganUser(return_data  as PinjamZoomIEvent[], users as CalenderUser[])
                        addEvent(return_data[0])
                        progressIndikator.finish()
                        toast.success('Berhasil menambahkan data baru')
                        clearErrors()
                        onToggle()
                    } catch (error) {
                        console.log(error)
                        progressIndikator.finish()
                        toast.error('Gagal menambahkan data baru')
                    }
                })
                .catch(function (error: any) {
                    console.log(error)
                    progressIndikator.finish()
                    toast.error('Gagal menambahkan data baru')

                    clearErrors();
                    if (error.response?.status === 422) {
                        Object.keys(error.response.data.errors).forEach((key) => {
                            setError(key, error.response.data.errors[key][0]);
                        });
                    }
                });

        } else {
            toast.info('Mencoba mengubah data')
            progressIndikator.start()
            axiosClient.post(route("pinjam-zoom-ubah", { pinjamZoom: event.id }), data)
                .then(function (response: any) {
                    try {
                        const return_data: IEvent[] = producePinjamZoomIEvent([response.data.data] as pinjam_zoom[]);
                        pairIEventDenganUser(return_data  as PinjamZoomIEvent[], users as CalenderUser[])
                        updateEvent(return_data[0])

                        progressIndikator.finish()
                        toast.success('Berhasil mengubah data')
                        clearErrors()
                        onToggle()
                        if(onUpdateOpenChange){
                            onUpdateOpenChange(return_data[0])
                        }
                    } catch (error) {
                        console.log(error)
                        progressIndikator.finish()
                        toast.error('Gagal mengubah data')
                    }
                })
                .catch(function (error: any) {
                    console.log(error)
                    progressIndikator.finish()
                    toast.error('Gagal mengubah data')

                    clearErrors();
                    if (error.response?.status === 422) {
                        Object.keys(error.response.data.errors).forEach((key) => {
                            setError(key, error.response.data.errors[key][0]);
                        });
                    }
                });
        }
    };

    useEffect(() => {
		if(event && (event as PinjamZoomIEvent).pinjam_zoom_ori != null){
            const event_: PinjamZoomIEvent = event as PinjamZoomIEvent
            setData("id", event.id);
            setData("kegiatan", event.title);
            setData('tipe', 'sekali')
            if(event._startDate != event._endDate){
                setData('tipe', 'berulang')
            }
            setData("tanggal_awal",  event._startDate);
            setData("tanggal_akhir",  event._endDate);
            setData("tanggal", event.date);
            setData("waktu_awal", formatTime(event.startDate, use24HourFormat));
            setData("waktu_akhir", formatTime(event.endDate, use24HourFormat));
            setData("zoom_akun",(event as PinjamZoomIEvent).zoom_akun);
            if(event_.pinjam_zoom_ori){
                setData("password", event_.pinjam_zoom_ori?.password);
                setData("status", event_.pinjam_zoom_ori?.status);
                setData('pembuat_username', event_.pinjam_zoom_ori?.pembuat_username)
                setData('confirm_username', event_.pinjam_zoom_ori?.confirm_username)
            }else{
                setData("password", '');
                setData("status", "diapp");
                setData('pembuat_username', '')
                setData('confirm_username', '')
            }
        }else{
            setData("id", 'xxxx-xxxx-xxxx');
            setData("kegiatan", "Zoom");
            setData('tipe', 'sekali')
            setData("tanggal_awal",  formatDate(initialDates.startDate));
            setData("tanggal_akhir",  formatDate(initialDates.endDate));
            setData("tanggal", formatDate(initialDates.startDate));
            setData("waktu_awal",formatTime(initialDates.startDate, use24HourFormat));
            setData("waktu_akhir", formatTime(initialDates.endDate, use24HourFormat));
            setData("zoom_akun", null);
            setData("password", "zoom")
            setData("status", 'menunggu');
            setData('pembuat_username', '')
            setData('confirm_username', '')
        }
	}, [event, initialDates]);



    return (
        <Modal open={isOpen} onOpenChange={onToggle} modal={false}>
            <ModalTrigger asChild>{children}</ModalTrigger>
            <ModalContent>
                <ModalHeader>
                    <ModalTitle>
                        {isEditing ? (
                            <>Ubah {capitalizeWords(settingCalender.name_event)}</>
                        ) : (
                            <>Tambah {capitalizeWords(settingCalender.name_event)}</>
                        )}
                    </ModalTitle>
                </ModalHeader>

                    <form onSubmit={submit} className="p-0 space-y-3">
                    <div className="space-y-4">
                        <h3 className="text-xs font-bold text-slate-400 uppercase tracking-widest border-b pb-1">Detail Kegiatan</h3>

                        <div>
                        <label className="block text-sm font-medium text-slate-700 mb-1">Nama Kegiatan</label>
                        <textarea
                            name="kegiatan"
                            required
                            rows={3}
                            className="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none"
                            placeholder="Contoh: Rapat Koordinasi Progress Project X"
                            value={data.kegiatan}
                            onChange={(e) =>
                                setData("kegiatan", e.target.value)
                            }
                        ></textarea>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Password Meeting</label>
                            <input
                            type="text"
                            name="password"
                            maxLength={10}
                            required
                            className="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none"
                            value={data.password}
                            onChange={(e) =>
                                setData("password", e.target.value)
                            }
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Tipe Peminjaman</label>
                            <Select
                                name="tipe"
                                required
                                value={data.tipe}
                                onValueChange={(value) => {
                                    setData("tipe", value)
                                }}
                            >
                                <SelectTrigger
                                    name="zoom_status"
                                    className="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none"
                                >
                                    <SelectValue placeholder="Pilih Tipe" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem value="sekali">
                                            Sekali
                                        </SelectItem>
                                        <SelectItem value="berulang">
                                            Berulang
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </div>
                        </div>
                    </div>

                    {/* Seksi Waktu */}
                    <div className="space-y-4 pt-2">
                        <h3 className="text-xs font-bold text-slate-400 uppercase tracking-widest border-b pb-1">Jadwal & Waktu</h3>

                        {data.tipe === 'sekali' ? (
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Tanggal Pelaksanaan</label>
                            <DatePicker22
                                className="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none"
                                value={initialDates.startDate}
                                setValue={(value) => {
                                    setData("tanggal", formatDate(value.toISOString()));
                                    setData("tanggal_awal", formatDate(value.toISOString()));
                                    setData("tanggal_akhir", formatDate(value.toISOString()));
                                }}
                                modal={true}
                            />
                            <InputError message={errors.tanggal} />
                            <InputError message={errors.tanggal_awal} />
                        </div>
                        ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Tanggal Awal</label>
                                <DatePicker22
                                    className="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none"
                                    value={initialDates.startDate}
                                    setValue={(value) => {
                                        setData("tanggal", formatDate(value.toISOString()));
                                        setData("tanggal_awal", formatDate(value.toISOString()));

                                    }}
                                    modal={true}
                                />
                                <InputError message={errors.tanggal_awal} />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Tanggal Akhir</label>
                                <DatePicker22
                                    className="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none"
                                    value={initialDates.endDate}
                                    setValue={(value) => {
                                        setData("tanggal_akhir", formatDate(value.toISOString()));
                                    }}
                                    modal={true}
                                />
                                <InputError message={errors.tanggal_awal} />
                            </div>
                        </div>
                        )}

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1 flex items-center gap-1">
                                <Clock size={14} /> Jam Mulai
                                </label>
                                <Time24Picker className="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none"
                                    value={new Date(`${data.tanggal}T${data.waktu_awal}`)}
                                    setValue={(value) => {
                                        setData("waktu_awal", value);
                                    }}
                                    modal={true}
                                />
                                <InputError message={errors.waktu_awal} />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1 flex items-center gap-1">
                                <Clock size={14} /> Jam Selesai
                                </label>
                                <Time24Picker className="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none"
                                    value={new Date(`${data.tanggal}T${data.waktu_akhir}`)}
                                    setValue={(value) => {
                                        setData("waktu_akhir", value);
                                    }}
                                    modal={true}
                                />
                                <InputError message={errors.waktu_akhir} />
                            </div>
                        </div>
                         {havePeran('zoom') &&
                            <div className="grid gap-3">
                                <Label
                                    className="block text-sm font-medium text-slate-700 mb-1 flex items-center gap-1"
                                    htmlFor="tipe-1"
                                >
                                    Zoom Akun
                                </Label>
                                <Select
                                    name="tipe"
                                    required
                                    value={data.zoom_akun as string}
                                    onValueChange={(value) => {
                                        setData("zoom_akun", value as 'statsulbar' | 'sp2020' | null)
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
                        }
                    </div>





                    <ModalFooter className="flex justify-end gap-2">
                        <ModalClose asChild>
                            <Button type="button" variant="outline" className="text-accent-foreground">
                                Cancel
                            </Button>
                        </ModalClose>
                        <Button type="submit">
                            {isEditing ? "Simpan Perubahan" : "Buat"}
                        </Button>
                    </ModalFooter>
                </form>
            </ModalContent>
        </Modal>
    );
}

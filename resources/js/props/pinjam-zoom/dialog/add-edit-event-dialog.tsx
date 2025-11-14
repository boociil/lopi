import { addMinutes, set } from "date-fns";
import { useEffect, useMemo } from "react";
import { useForm } from "@inertiajs/react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
    Modal,
    ModalClose,
    ModalContent,
    ModalDescription,
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
        topic:  "Peminjaman Zoom",
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
            setData("topic", event.title);
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
            setData("topic", "Zoom");
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
                    <ModalDescription>
                        {isEditing ? (
                            <>
                                Ubah {capitalizeWords(settingCalender.name_event)} yang sudah ada.
                            </>
                        ) : (
                            <>Buat {capitalizeWords(settingCalender.name_event)} baru untuk kalender Anda.</>
                        )}
                    </ModalDescription>
                </ModalHeader>

                <form className="flex flex-col gap-6 mt-5 mb-5" onSubmit={submit}>
                    <div className="grid gap-4">
                        <div className="grid gap-3">
                            <Label className="text-secondary" htmlFor="title-1">
                                Kegiatan
                            </Label>
                            <Input
                                id="title-1"
                                name="title"
                                required
                                value={data.topic}
                                onChange={(e) =>
                                    setData("topic", e.target.value)
                                }
                                className="text-secondary"
                            />
                            <InputError message={errors.topic} />
                        </div>
                        <div className="grid gap-3">
                            <Label className="text-secondary" htmlFor="password-1">
                                Password
                            </Label>
                            <Input
                                id="password-1"
                                name="password"
                                required
                                value={data.password}
                                onChange={(e) =>
                                    setData("password", e.target.value)
                                }
                                className="text-secondary"
                            />
                            <InputError message={errors.password} />
                        </div>
                        <div className="grid gap-3">
                            <Label
                                className="text-secondary"
                                htmlFor="tipe-1"
                            >
                                Tipe
                            </Label>
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
                                    className="text-secondary"
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
                        <div className="grid gap-3">
                            <Label
                                className="text-secondary"
                                htmlFor="starttime-1"
                            >
                                {data.tipe == 'sekali' ? <>Tanggal</> : <>Tanggal Awal</>}
                            </Label>
                            <DatePicker22
                                value={initialDates.startDate}
                                setValue={(value) => {
                                    setData("tanggal", formatDate(value.toISOString()));
                                    if(data.tipe == 'sekali'){
                                        setData("tanggal_awal", formatDate(value.toISOString()));
                                        setData("tanggal_akhir", formatDate(value.toISOString()));
                                    }else if(data.tipe == 'berulang'){
                                         setData("tanggal_awal", formatDate(value.toISOString()));
                                    }
                                }}
                                modal={true}
                            />
                            <InputError message={errors.tanggal} />
                            <InputError message={errors.tanggal_awal} />
                        </div>
                        {data.tipe == 'berulang' ? <>
                            <div className="grid gap-3">
                                <Label
                                    className="text-secondary"
                                    htmlFor="starttime-1"
                                >
                                    Tanggal Akhir
                                </Label>
                                <DatePicker22
                                    value={initialDates.endDate}
                                    setValue={(value) => {
                                        setData("tanggal_akhir", formatDate(value.toISOString()));
                                    }}
                                    modal={true}
                                />
                                <InputError message={errors.tanggal_akhir} />
                            </div>
                        </> : <></>}

                        <div className="grid gap-3">
                            <Label
                                className="text-secondary"
                                htmlFor="duration-1"
                            >
                                Waktu Awal
                            </Label>
                            <Time24Picker
                                value={new Date(`${data.tanggal}T${data.waktu_awal}`)}
                                setValue={(value) => {
                                    setData("waktu_awal", value);
                                }}
                                modal={true}
                            />
                            <InputError message={errors.waktu_awal} />
                        </div>
                        <div className="grid gap-3">
                            <Label
                                className="text-secondary"
                                htmlFor="duration-1"
                            >
                                Waktu Akhir
                            </Label>
                             <Time24Picker
                                value={new Date(`${data.tanggal}T${data.waktu_akhir}`)}
                                setValue={(value) => {
                                    setData("waktu_akhir", value);
                                }}
                                modal={true}
                            />
                            <InputError message={errors.waktu_akhir} />
                        </div>
                        {havePeran() &&
                            <div className="grid gap-3">
                                <Label
                                    className="text-secondary"
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
                                        className="text-secondary"
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

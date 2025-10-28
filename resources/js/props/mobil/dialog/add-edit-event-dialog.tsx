import { addMinutes, format, set } from "date-fns";
import { useEffect, useMemo, useState } from "react";
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
    formatDate,
    formatTime
} from "@/components/calender_modules/helpers";
import { Time24Picker } from "@/components/ui/time24-picker";
import { pinjam_mobil_req, PinjamMobilIEvent, producePinjamMobilIEvent, type pinjam_mobil } from "../types";
import { IEvent } from "@/components/calender_modules/interfaces";
import { Add_Edit_Event_IProps } from "@/components/calender_modules/dialogs/props-dialog";

export function AddEditEventDialog({
    children,
    startDate,
    startTime,
    event,
}: Add_Edit_Event_IProps) {
    const { isOpen, onClose, onToggle } = useDisclosure();
    const { addEvent, updateEvent, getRepeatEventById, settingCalender, use24HourFormat, axiosClient } = useCalendar();
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

        if(event.repeat_id == null){
             return {
                startDate: new Date(event.startDate),
                endDate: new Date(event.endDate),
            };
        }else{
            const repeatEvent: IEvent[] = getRepeatEventById(event.repeat_id)
            return {
                startDate: new Date(repeatEvent[0].startDate),
                endDate: new Date(repeatEvent[repeatEvent.length - 1].endDate),
            };
        }


    }, [startDate, startTime, event, isEditing]);

    const { data, setData, progress, errors, reset, clearErrors, setError } = useForm<
        Required<pinjam_mobil_req>
    >({
        id: "12345",
        kegiatan:  "Peminjaman Mobil",
        tipe: 'string',
        berulang_id: null,
        tanggal: formatDate(new Date().toISOString()),
        tanggal_awal: formatDate(new Date().toISOString()),
        tanggal_akhir: formatDate(new Date().toISOString()),
        waktu_awal: "07:30:00",
        waktu_akhir: "16:00:00",
        status: 'menunggu',
        pembuat_username: '',
        confirm_username: null
    });

    const submit: React.FormEventHandler = (e) => {
        e.preventDefault();
        if (!isEditing) {
            toast.info('Mencoba menambahkan data baru !')
            axiosClient.post(route('pinjam-mobil-tambah'), data)
                .then(function (response: any) {
                    try {
                        const return_data = response.data.data as pinjam_mobil[]
                        addEvent(producePinjamMobilIEvent(return_data))
                        toast.success('Berhasil menambahkan data baru !')
                        clearErrors()
                        onToggle()
                    } catch (error) {
                        console.log(error)
                        toast.error('Gagal menambahkan data baru !')
                    }
                })
                .catch(function (error: any) {
                    console.log(error)
                    toast.error('Gagal menambahkan data baru !')

                    clearErrors();
                    if (error.response?.status === 422) {
                        Object.keys(error.response.data.errors).forEach((key) => {
                            setError(key, error.response.data.errors[key][0]);
                        });
                    }
                });

        } else {
            toast.info('Mencoba mengubah data !')
            axiosClient.post(route("pinjam-mobil-ubah", { pinjamMobil: event.id }), data)
                .then(function (response: any) {
                    try {
                        const return_data = response.data.data as pinjam_mobil[]
                        updateEvent(producePinjamMobilIEvent(return_data), [data.id], [data.repeat_id])
                        toast.success('Berhasil mengubah data !')
                        clearErrors()
                        onToggle()
                    } catch (error) {
                        console.log(error)
                        toast.error('Gagal mengubah data !')
                    }
                })
                .catch(function (error: any) {
                    console.log(error)
                    toast.error('Gagal mengubah data !')

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
		if(event){
            const event_: PinjamMobilIEvent = event as PinjamMobilIEvent
            if(event.repeat_id){
                const repeatEvent: PinjamMobilIEvent[] = getRepeatEventById(event.repeat_id) as PinjamMobilIEvent[]
                setData("id", repeatEvent[0].id);
                setData("kegiatan", "Peminjaman Mobil");
                setData('tipe', 'sekali')
                setData("berulang_id", repeatEvent[0].repeat_id);
                setData("tanggal_awal",  formatDate( repeatEvent[0].date));
                setData("tanggal_akhir",  formatDate( repeatEvent[repeatEvent.length-1].date));
                setData("tanggal", formatDate( repeatEvent[0].date));
                setData("waktu_awal",formatTime( repeatEvent[0].startDate, use24HourFormat));
                setData("waktu_akhir", formatTime( repeatEvent[0].endDate, use24HourFormat));
                setData("status",  repeatEvent[0].pinjam_mobil_ori.status);
                setData('pembuat_username', repeatEvent[0].pinjam_mobil_ori.pembuat_username)
                setData('confirm_username', repeatEvent[0].pinjam_mobil_ori.confirm_username)
            }else{
                setData("id", event.id);
                setData("kegiatan", event.title);
                setData('tipe', 'sekali')
                setData("berulang_id", null);
                setData("tanggal_awal",  formatDate(event.date));
                setData("tanggal_akhir",  formatDate(event.date));
                setData("tanggal", formatDate(event.date));
                setData("waktu_awal", formatTime(event.startDate, use24HourFormat));
                setData("waktu_akhir", formatTime(event.endDate, use24HourFormat));
                setData("status", event_.pinjam_mobil_ori.status);
                setData('pembuat_username', event_.pinjam_mobil_ori.pembuat_username)
                setData('confirm_username', event_.pinjam_mobil_ori.confirm_username)
            }
        }else{
            setData("id", 'xxxx-xxxx-xxxx');
            setData("kegiatan", "Peminjaman Mobil");
            setData('tipe', 'sekali')
            setData("berulang_id", null);
            setData("tanggal_awal",  formatDate(initialDates.startDate));
            setData("tanggal_akhir",  formatDate(initialDates.endDate));
            setData("tanggal", formatDate(initialDates.startDate));
            setData("waktu_awal",formatTime(initialDates.startDate, use24HourFormat));
            setData("waktu_akhir", formatTime(initialDates.endDate, use24HourFormat));
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
                            <>Ubah {settingCalender.name_event}</>
                        ) : (
                            <>Tambah {settingCalender.name_event}</>
                        )}
                    </ModalTitle>
                    <ModalDescription>
                        {isEditing ? (
                            <>
                                Ubah {settingCalender.name_event} yang sudah ada.
                            </>
                        ) : (
                            <>Buat {settingCalender.name_event} baru untuk kalender Anda.</>
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
                                value={data.kegiatan}
                                onChange={(e) =>
                                    setData("kegiatan", e.target.value)
                                }
                                className="text-secondary"
                            />
                            <InputError message={errors.kegiatan} />
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
                                    setData("tanggal_awal", formatDate(value.toISOString()));
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
                                    value={initialDates.startDate}
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

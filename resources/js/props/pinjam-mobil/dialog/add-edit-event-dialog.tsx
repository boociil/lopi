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
import { useCalendar } from "@/components/calender_modules/contexts/calendar-context";
import { useDisclosure } from "@/components/calender_modules/hooks";
import InputError from "@/components/input-error";
import { DatePicker22 } from "@/components/ui/date-picker";
import {
    capitalizeWords,
    formatDate,
    formatTime
} from "@/components/calender_modules/helpers";
import { Time24Picker } from "@/components/ui/time24-picker";
import { pairIEventDenganUser, pinjam_mobil_req, PinjamMobilIEvent, producePinjamMobilIEvent, type pinjam_mobil } from "../types";
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
    const { users, addEvent, updateEvent, settingCalender, use24HourFormat, axiosClient } = useCalendar();
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
        Required<pinjam_mobil_req>
    >({
        id: "12345",
        kegiatan:  "Peminjaman Mobil Untuk ...",
        tipe: 'string',
        tanggal: formatDate(new Date().toISOString()),
        tanggal_awal: formatDate(new Date().toISOString()),
        tanggal_akhir: formatDate(new Date().toISOString()),
        waktu_awal: "07:30:00",
        waktu_akhir: "16:00:00",
        status: 'menunggu',
        pembuat_username: '',
        confirm_username: null,
        error_clash: '-',
    });

    const submit: React.FormEventHandler = (e) => {
        e.preventDefault();
        if (!isEditing) {
            toast.info('Mencoba menambahkan data baru')
            progressIndikator.start()
            axiosClient.post(route('pinjam-mobil-tambah'), data)
                .then(function (response: any) {
                    try {
                        const return_data: IEvent[] = producePinjamMobilIEvent([response.data.data] as pinjam_mobil[]);
                        pairIEventDenganUser(return_data  as PinjamMobilIEvent[], users as CalenderUser[])
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
            axiosClient.post(route("pinjam-mobil-ubah", { pinjamMobil: event.id }), data)
                .then(function (response: any) {
                    try {
                        const return_data: IEvent[] = producePinjamMobilIEvent([response.data.data] as pinjam_mobil[]);
                        pairIEventDenganUser(return_data  as PinjamMobilIEvent[], users as CalenderUser[])
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
		if(event){
            const event_: PinjamMobilIEvent = event as PinjamMobilIEvent
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
            setData("status", event_.pinjam_mobil_ori.status);
            setData('pembuat_username', event_.pinjam_mobil_ori.pembuat_username)
            setData('confirm_username', event_.pinjam_mobil_ori.confirm_username)
            setData('error_clash', '-')
        }else{
            setData("id", 'xxxx-xxxx-xxxx');
            setData("kegiatan", "Peminjaman Mobil");
            setData('tipe', 'sekali')
            setData("tanggal_awal",  formatDate(initialDates.startDate));
            setData("tanggal_akhir",  formatDate(initialDates.endDate));
            setData("tanggal", formatDate(initialDates.startDate));
            setData("waktu_awal",formatTime(initialDates.startDate, use24HourFormat));
            setData("waktu_akhir", formatTime(initialDates.endDate, use24HourFormat));
            setData("status", 'menunggu');
            setData('pembuat_username', '')
            setData('confirm_username', '')
            setData('error_clash', '-')

            console.log(data)
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
                 <div className="p-0 space-y-3">

                    <div>
                        <label className="block text-[10px] font-bold text-slate-700 uppercase tracking-wider mb-2">Nama Kegiatan</label>
                        <Input
                            required name="kegiatan"
                            value={data.kegiatan}
                            onChange={(e) =>
                                setData("kegiatan", e.target.value)
                            }
                            className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none text-sm font-medium"
                            placeholder="Contoh: Peminjaman Mobil untuk .."
                        />
                        <InputError message={errors.kegiatan} />
                    </div>

                    <div>
                        <label className="block text-[10px] font-bold text-slate-700 uppercase tracking-wider mb-2">Tipe Peminjaman</label>
                        <div className="grid grid-cols-2 gap-3">
                        <button type="button" onClick={() => {setData('tipe',  'sekali')}} className={`py-2 rounded-xl text-xs font-bold border transition-all ${data.tipe === 'sekali' ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-slate-200 text-slate-500'}`}>Sekali</button>
                        <button type="button" onClick={() => setData('tipe',  'berulang' )} className={`py-2 rounded-xl text-xs font-bold border transition-all ${data.tipe === 'berulang' ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-slate-200 text-slate-500'}`}>Berulang</button>
                        </div>
                    </div>

                    {data.tipe === 'sekali' ? (
                        <div>
                        <label className="block text-xs font-bold text-slate-700 mb-1.5">Tanggal</label>
                        <DatePicker22 className="w-full px-4 py-2.5 rounded-xl border border-slate-200 outline-none text-sm"
                            value={initialDates.startDate}
                            setValue={(value) => {
                                setData("tanggal", formatDate(value.toISOString()));
                            }}
                            modal={true}
                        />
                        <InputError message={errors.tanggal} />
                        <InputError message={errors.tanggal_awal} />
                    </div>
                    ) : (
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1.5">Tanggal Awal</label>
                                <DatePicker22 className="w-full px-4 py-2.5 rounded-xl border border-slate-200 outline-none text-sm"
                                    value={initialDates.startDate}
                                    setValue={(value) => {
                                        setData("tanggal_awal", formatDate(value.toISOString()));
                                    }}
                                    modal={true}
                                />
                                <InputError message={errors.tanggal} />
                                <InputError message={errors.tanggal_awal} />
                            </div>
                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1.5">Tanggal Akhir</label>
                                <DatePicker22 className="w-full px-4 py-2.5 rounded-xl border border-slate-200 outline-none text-sm"
                                    value={initialDates.endDate}
                                    setValue={(value) => {
                                        setData("tanggal_akhir", formatDate(value.toISOString()));
                                    }}
                                    modal={true}
                                />
                                <InputError message={errors.tanggal_akhir} />
                            </div>
                        </div>
                    )}

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-xs font-bold text-slate-700 mb-1.5">Mulai</label>
                            <Time24Picker className="w-full px-4 py-2.5 rounded-xl border border-slate-200 outline-none text-sm"
                                value={new Date(`${data.tanggal}T${data.waktu_awal}`)}
                                setValue={(value) => {
                                    setData("waktu_awal", value);
                                }}
                                modal={true}
                            />
                            <InputError message={errors.waktu_awal} />
                        </div>
                        <div>
                            <label className="block text-xs font-bold text-slate-700 mb-1.5">Selesai</label>
                            <Time24Picker className="w-full px-4 py-2.5 rounded-xl border border-slate-200 outline-none text-sm"
                                value={new Date(`${data.tanggal}T${data.waktu_akhir}`)}
                                setValue={(value) => {
                                    setData("waktu_akhir", value);
                                }}
                                modal={true}
                            />
                            <InputError message={errors.waktu_akhir} />
                        </div>
                    </div>
                    <InputError message={errors.error_clash} />

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

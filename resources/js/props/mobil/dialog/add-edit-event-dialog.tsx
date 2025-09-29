import { addMinutes, format, set } from "date-fns";
import { useEffect, useMemo, useState } from "react";
import {router, useForm, usePage } from "@inertiajs/react";
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
import { Textarea } from "@/components/ui/textarea";
import { COLORS } from "@/components/calender_modules/constants";
import { useCalendar } from "@/components/calender_modules/contexts/calendar-context";
import { useDisclosure } from "@/components/calender_modules/hooks";
import { type IEvent } from "@/components/calender_modules/interfaces";
import { Add_Edit_Event_IProps } from "@/components/calender_modules/dialogs/props-dialog";
import { Label } from "@/components/ui/label";
import InputError from "@/components/input-error";
import { SelectGroup } from "@radix-ui/react-select";
import { DatePicker22 } from "@/components/ui/date-picker";
import {
    formatDate,
    formatTime
} from "@/components/calender_modules/helpers";

export function AddEditEventDialog({
    children,
    startDate,
    startTime,
    event,
}: Add_Edit_Event_IProps) {
    const { isOpen, onClose, onToggle } = useDisclosure();
    const { addEvent, updateEvent, settingCalender, use24HourFormat } = useCalendar();
    const isEditing = !!event;
    const [tipe, setTipe] = useState<string>("sekali");

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

    function close_dialog() {
        setData("repeat_id", "");
        setData("startDate", new Date().toISOString());
        setData("endDate", new Date().toISOString());
        setData("date", formatDate(new Date().toISOString()));
        setData("_startDate", formatDate(new Date().toISOString()));
        setData("_endDate", formatDate(new Date().toISOString()));
        setData("_startTime", "07:30:00");
        setData("_endTime", "16:00:00");

        setData("title", "");
        setData("color", "orange");
        setData("description", "Peminjaman Mobil");
        setData("user", null);
        return true;
    }

    const { data, setData, post, processing, errors, reset } = useForm<
        Required<IEvent>
    >({
        id: "",
        repeat_id: "",
        startDate: new Date().toISOString(),
        endDate: new Date().toISOString(),
        date: formatDate(new Date().toISOString()),
        _startDate: formatDate(new Date().toISOString()),
        _endDate: formatDate(new Date().toISOString()),
        _startTime: "07:30:00",
        _endTime: "16:00:00",
        title: "",
        color: "orange",
        description: "Peminjaman Mobil",
        user: null,
    });

    const submit: React.FormEventHandler = (e) => {
        e.preventDefault();
        if (!isEditing) {
            post(route("zoom-tambah"), {
                onSuccess: () => {
                    close_dialog();
                },
            });
        } else {
            post(route("zoom-ubah", { zoom_id: event.id }), {
                onSuccess: () => {
                    close_dialog();
                },
            });
        }
    };

    useEffect(() => {
		if(event){
            if(event.repeat_id){
                setTipe('berulang')
            }else{
                setTipe('sekali')

                setData("repeat_id", null);
                setData("startDate",  `${event.date}T${event._startTime}`);
                setData("endDate",  `${event.date}T${event._endTime}`);
                setData("date", formatDate(event.date));
                setData("_startDate", formatDate(event.date));
                setData("_endDate", formatDate(event.date));
                setData("_startTime", event._startTime);
                setData("_endTime", event._endTime);

                setData("title", event.title);
                setData("color", "orange");
                setData("description", "Peminjaman Mobil");
                setData("user", null);
            }
        }else{
            setTipe('sekali')
            close_dialog()
        }
	}, [event, initialDates]);



    return (
        <Modal open={isOpen} onOpenChange={onToggle} modal={false}>
            <ModalTrigger asChild>{children}</ModalTrigger>
            <ModalContent>
                <ModalHeader>
                    <ModalTitle>
                        {isEditing ? (
                            <>"Ubah " {settingCalender.name_event}</>
                        ) : (
                            <>"Tambah" {settingCalender.name_event}</>
                        )}
                    </ModalTitle>
                    <ModalDescription>
                        {isEditing ? (
                            <>
                                "Ubah " {settingCalender.name_event} " yang
                                sudah ada."
                            </>
                        ) : (
                            <>Buat acara baru untuk kalender Anda.</>
                        )}
                    </ModalDescription>
                </ModalHeader>

                <form className="flex flex-col gap-6" onSubmit={submit}>
                    <div className="grid gap-4">
                        <div className="grid gap-3">
                            <Label className="text-secondary" htmlFor="title-1">
                                Kegiatan
                            </Label>
                            <Input
                                id="title-1"
                                name="title"
                                required
                                value={data.title}
                                onChange={(e) =>
                                    setData("title", e.target.value)
                                }
                                className="text-secondary"
                            />
                            <InputError message={errors.title} />
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
                                value={tipe}
                                onValueChange={(value) => {
                                    setTipe(value)
                                }}
                            >
                                <SelectTrigger
                                    name="zoom_status"
                                    className="text-secondary"
                                ></SelectTrigger>
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
                                Tanggal Awal
                            </Label>
                            <DatePicker22
                                value={initialDates.startDate}
                                setValue={(value) => {
                                    setData("date", formatDate(value.toISOString()));
                                    setData("_startDate", formatDate(value.toISOString()));
                                }}
                                modal={true}
                            />
                            <InputError message={errors._startDate} />
                        </div>
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
                                    setData("_endDate", formatDate(value.toISOString()));
                                }}
                                modal={true}
                            />
                            <InputError message={errors._endDate} />
                        </div>
                        <div className="grid gap-3">
                            <Label
                                className="text-secondary"
                                htmlFor="duration-1"
                            >
                                Waktu Awal
                            </Label>
                            <Input
                                type="time"
                                id="time-picker"
                                step="1"
                                defaultValue="10:30:00"
                                className="bg-background appearance-none [&::-webkit-calendar-picker-indicator]:hidden [&::-webkit-calendar-picker-indicator]:appearance-none"
                                value={data._startTime}
                                onChange={(e) =>
                                    setData("_startTime", e.target.value)
                                }
                            />
                            <InputError message={errors._startTime} />
                        </div>
                        <div className="grid gap-3">
                            <Label
                                className="text-secondary"
                                htmlFor="duration-1"
                            >
                                Waktu Akhir
                            </Label>
                            <Input
                                type="time"
                                id="time-picker"
                                step="1"
                                defaultValue="10:30:00"
                                className="bg-background appearance-none [&::-webkit-calendar-picker-indicator]:hidden [&::-webkit-calendar-picker-indicator]:appearance-none"
                                value={data._endTime}
                                onChange={(e) =>
                                    setData("_startTime", e.target.value)
                                }
                            />
                            <InputError message={errors._endTime} />
                        </div>
                    </div>
                </form>
                <ModalFooter className="flex justify-end gap-2">
                    <ModalClose asChild>
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </ModalClose>
                    <Button form="event-form" type="submit">
                        {isEditing ? "Simpan Perubahan" : "Buat"}
                    </Button>
                </ModalFooter>
            </ModalContent>
        </Modal>
    );
}

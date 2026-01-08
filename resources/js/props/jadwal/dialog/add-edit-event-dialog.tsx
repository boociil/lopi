import { addMinutes, set } from "date-fns";
import { useEffect, useMemo, Fragment } from "react";
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
    formatTime,
} from "@/components/calender_modules/helpers";
import { Time24Picker } from "@/components/ui/time24-picker";
import {
    pairIEventDenganUser,
    jadwal_req,
    JadwalIEvent,
    produceJadwalIEvent,
    type jadwal,
} from "../types";
import { CalenderUser, IEvent } from "@/components/calender_modules/interfaces";
import { Add_Edit_Event_IProps } from "@/components/calender_modules/dialogs/props-dialog";
import { progress as progressIndikator } from "@inertiajs/react";
import { defineStepper } from "@stepperize/react";
import { Separator } from "@/components/ui/separator";

const { useStepper, steps, utils } = defineStepper(
    { id: "info", title: "Informasi" },
    { id: "ruang_zoom", title: "Ruang dan Zoom" },
    { id: "peserta", title: "Peserta" },
);

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
        canApprove,
        havePeran,
    } = useCalendar();
    const isEditing = !!event;

    const stepper = useStepper();
    const currentIndex = utils.getIndex(stepper.current.id);
    stepper.reset;

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
            tipe: "string",

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
        });

    const submit: React.FormEventHandler = (e) => {
        e.preventDefault();
        if (!isEditing) {
            toast.info("Mencoba menambahkan data baru");
            progressIndikator.start();
            axiosClient
                .post(route("pinjam-zoom-tambah"), data)
                .then(function (response: any) {
                    try {
                        const return_data: IEvent[] = produceJadwalIEvent([
                            response.data.data,
                        ] as jadwal[]);
                        pairIEventDenganUser(
                            return_data as JadwalIEvent[],
                            users as CalenderUser[],
                        );
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
                .post(route("pinjam-zoom-ubah", { pinjamZoom: event.id }), data)
                .then(function (response: any) {
                    try {
                        const return_data: IEvent[] = produceJadwalIEvent([
                            response.data.data,
                        ] as jadwal[]);
                        pairIEventDenganUser(
                            return_data as JadwalIEvent[],
                            users as CalenderUser[],
                        );
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
            setData("tipe", "sekali");
            if (event._startDate != event._endDate) {
                setData("tipe", "berulang");
            }
            setData("tanggal_awal", event._startDate);
            setData("tanggal_akhir", event._endDate);
            setData("tanggal", event.date);
            setData("waktu_awal", formatTime(event.startDate, use24HourFormat));
            setData("waktu_akhir", formatTime(event.endDate, use24HourFormat));
            setData("zoom_akun", (event as JadwalIEvent).zoom_akun);
            if (event_.jadwal_ori) {
                setData("zoom_status", event_.jadwal_ori!.zoom_status);
                setData("zoom_password", event_.jadwal_ori!.zoom_password);
                setData("zoom_akun", event_.jadwal_ori!.zoom_akun);

                setData("ruang_status", event_.jadwal_ori!.ruang_status);
                setData("ruang", event_.jadwal_ori!.ruang);
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
    }, [event, initialDates]);

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
                                {capitalizeWords(settingCalender.name_event)}
                            </>
                        )}
                    </ModalTitle>

                    <ModalDescription>
                        <div className="flex justify-between">
                            <h2 className="">
                                {isEditing ? (
                                    <>
                                        Ubah{" "}
                                        {capitalizeWords(
                                            settingCalender.name_event,
                                        )}{" "}
                                        yang sudah ada.
                                    </>
                                ) : (
                                    <>
                                        Buat{" "}
                                        {capitalizeWords(
                                            settingCalender.name_event,
                                        )}{" "}
                                        baru untuk kalender Anda.
                                    </>
                                )}
                            </h2>
                            <div className="flex justify-between">
                                <div className="flex items-center gap-2">
                                    <span className=" text-muted-foreground">
                                        Step {currentIndex + 1} of{" "}
                                        {steps.length}
                                    </span>
                                    <div />
                                </div>
                            </div>
                        </div>
                    </ModalDescription>
                </ModalHeader>

                <form
                    className="flex flex-col gap-6 mt-5 mb-5"
                    onSubmit={submit}
                >
                    <div className="grid gap-4">
                        <nav aria-label="Checkout Steps" className="group my-4">
                            <ol
                                className="flex items-center justify-between gap-2"
                                aria-orientation="horizontal"
                            >
                                {stepper.all.map((step, index, array) => (
                                    <Fragment key={step.id}>
                                        <li className="flex items-center gap-4 flex-shrink-0">
                                            <Button
                                                type="button"
                                                role="tab"
                                                variant={
                                                    index <= currentIndex
                                                        ? "default"
                                                        : "outline"
                                                }
                                                aria-current={
                                                    stepper.current.id ===
                                                    step.id
                                                        ? "step"
                                                        : undefined
                                                }
                                                aria-posinset={index + 1}
                                                aria-setsize={steps.length}
                                                aria-selected={
                                                    stepper.current.id ===
                                                    step.id
                                                }
                                                className="flex size-10 items-center justify-center rounded-full"
                                                onClick={() =>
                                                    stepper.goTo(step.id)
                                                }
                                            >
                                                {index + 1}
                                            </Button>
                                            <span className="text-sm font-medium">
                                                {step.title}
                                            </span>
                                        </li>
                                        {index < array.length - 1 && (
                                            <Separator
                                                className={`flex-1 ${
                                                    index < currentIndex
                                                        ? "bg-primary"
                                                        : "bg-muted"
                                                }`}
                                            />
                                        )}
                                    </Fragment>
                                ))}
                            </ol>
                        </nav>
                        <div className="space-y-4">
                            {stepper.switch({
                                info: () =>
                                <>
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
                                </>,
                                ruang_zoom: () =>
                                <>
                                <div className="grid gap-3">
                                        <Label className="text-secondary" htmlFor="password-1">
                                            Password
                                        </Label>
                                        <Input
                                            id="password-1"
                                            name="password"
                                            required
                                            value={data.zoom_password!}
                                            onChange={(e) =>
                                                setData("zoom_password", e.target.value)
                                            }
                                            className="text-secondary"
                                        />
                                        <InputError message={errors.zoom_password} />
                                    </div>
                                </>,
                                peserta: () => <p>peserta</p>,
                            })}
                        </div>
                    </div>
                    <ModalFooter className="flex justify-end gap-2">
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
                            onClick={stepper.prev}
                            disabled={stepper.isFirst}
                            className="text-accent-foreground"
                        >
                            Kembali
                        </Button>

                        {!stepper.isLast ? (
                            <>

                                <Button type="button" onClick={stepper.next}>
                                    Next
                                </Button>
                            </>
                        ) : (
                            <Button type="submit">
                                {isEditing ? "Simpan Perubahan" : "Buat"}
                            </Button>
                        )}
                    </ModalFooter>
                </form>
            </ModalContent>
        </Modal>
    );
}

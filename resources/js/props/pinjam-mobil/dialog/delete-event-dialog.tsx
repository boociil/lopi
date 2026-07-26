import { TrashIcon } from "lucide-react";
import { toast } from "sonner";
import {
	AlertDialog,
	AlertDialogAction,
	AlertDialogCancel,
	AlertDialogContent,
	AlertDialogDescription,
	AlertDialogFooter,
	AlertDialogHeader,
	AlertDialogTitle,
	AlertDialogTrigger,
} from "@/components/ui/alert-dialog";
import { Button } from "@/components/ui/button";
import { useCalendar } from "@/components/calender_modules/contexts/calendar-context";
import { Delete_Event_Dialog_IProps } from "@/components/calender_modules/dialogs/props-dialog";
import { IEvent } from "@/components/calender_modules/interfaces";
import { progress as progressIndikator } from '@inertiajs/react'
import { capitalizeWords } from "@/components/calender_modules/helpers";

export default function DeleteEventDialog({ eventId }: Delete_Event_Dialog_IProps) {
	const { removeEvent , settingCalender, getEvent, axiosClient} = useCalendar();

    if (!eventId) {
		return null;
	}

    const eventItem: IEvent | undefined = getEvent(eventId)
    if(!eventItem){
        return null
    }

	const deleteEvent = () => {
        progressIndikator.start()
        axiosClient.post(route("pinjam-mobil-hapus", {pinjamMobil: eventId}))
            .then(function () {
                removeEvent(eventId);
                progressIndikator.finish()
                toast.success(capitalizeWords(settingCalender.name_event)+" telah dihapus dengan sukses.");
            })
            .catch(function () {
                progressIndikator.finish()
                toast.error("Kesalahan saat menghapus "+capitalizeWords(settingCalender.name_event)+".");
            });
	};

	return (
		<AlertDialog>
			<AlertDialogTrigger asChild>
				<Button variant="destructive">
					<TrashIcon />
					Hapus
				</Button>
			</AlertDialogTrigger>
			<AlertDialogContent>
				<AlertDialogHeader>
					<AlertDialogTitle>Apakah Anda benar-benar yakin?</AlertDialogTitle>
					<AlertDialogDescription>
						Tindakan ini tidak dapat dibatalkan.
                        Hal ini akan menghapus {capitalizeWords(settingCalender.name_event)} {eventItem.title} secara permanen.
					</AlertDialogDescription>
				</AlertDialogHeader>
				<AlertDialogFooter>
					<AlertDialogCancel>Cancel</AlertDialogCancel>
					<AlertDialogAction onClick={deleteEvent}>Continue</AlertDialogAction>
				</AlertDialogFooter>
			</AlertDialogContent>
		</AlertDialog>
	);
}

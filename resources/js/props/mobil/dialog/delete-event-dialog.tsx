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
import { router } from "@inertiajs/react";

export default function DeleteEventDialog({ eventId }: Delete_Event_Dialog_IProps) {
	const { removeEvent , settingCalender, getEvent} = useCalendar();

    if (!eventId) {
		return null;
	}

    const eventItem: IEvent | undefined = getEvent(eventId)
    if(!eventItem){
        return null
    }

	const deleteEvent = () => {
         router.visit(route("pinjam-mobil-hapus", {pinjamMobil: eventId}),
            { async: true, showProgress: true, method: 'get',
                    onSuccess: (page) => {
                        removeEvent(eventId);
			            toast.success("Event deleted successfully.");
                    },
                    onError: (page) => {
                       toast.error("Error deleting event.");
                    }
            }
        )
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
                        Hal ini akan menghapus {settingCalender.name_event} {eventItem.title} secara permanen.
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

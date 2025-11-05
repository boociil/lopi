import { cn } from "@/lib/utils";
import { appreciate_data, TargetData } from "../types";

interface StickyNoteProps {
    data: TargetData;
    userDict: Map<string, string>
    number1: number
    number2: number
    openDialog: (data: TargetData) => void
}

export default function StickyNote({
    data,
    userDict,
    number1,
    number2,
    openDialog,
}: StickyNoteProps) {
    const colors = {
        0: "bg-yellow-200",
        1: "bg-pink-200",
        2: "bg-green-200",
        3: "bg-red-200",
        4: "bg-sky-200",
    };
    const rotate = {
        0: "rotate-2",
        1: "",
        2: "-rotate-2",
    };

    return (
        <div
            className={cn(
                colors[number1],
                rotate[number2],
                "rounded-xl p-4 w-50 h-50 shadow-lg select-none m-2",
            )}
            onClick={() => {openDialog(data)}}
        >
            <div className="font-bold text-sm">Kepada : {userDict.has(data.target.username) ? userDict.get(data.target.username) : data.target.username}</div>
            <div className="mt-2 text-xs">
                {data.data &&  <div dangerouslySetInnerHTML={{ __html: data.data.pesan }} /> }
            </div>
        </div>
    );
}

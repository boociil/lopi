import { cn } from "@/lib/utils";
import { appreciate_data, TargetData } from "../types";

interface StickyNoteProps {
    data: appreciate_data;
    userDict: Map<string, string>
}

export default function StickyNoteBuka({
    data,
    userDict,
}: StickyNoteProps) {

    const number1: number =  Math.floor(Math.random() * 5)
    const number2: number =  Math.floor(Math.random() * 3)

    const colors = {
        0: "bg-yellow-200",
        1: "bg-pink-200",
        2: "bg-green-200",
        3: "bg-red-200",
        4: "bg-sky-200",
    };
    const rotate = {
        0: "rotate-1",
        1: "",
        2: "-rotate-1",
    };

    return (
        <div
            className={cn(
                colors[number1],
                rotate[number2],
                "rounded-xl p-4 w-60 h-60 shadow-lg select-none m-2 overflow-auto",
            )}
        >

            <div className="font-bold text-sm">Dari : {data.status_anonim  == "1" ? "Anonim" : userDict.has(data.dari) ? userDict.get(data.dari) : data.dari}</div>
            <div className="mt-2 text-xs">
                <div dangerouslySetInnerHTML={{ __html: data.pesan }} />
            </div>
        </div>
    );
}

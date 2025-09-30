"use client";

import * as React from "react";
import { ChevronDownIcon } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Calendar } from "@/components/ui/calendar";
import { Label } from "@/components/ui/label";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";

export function DatePicker22({
    value,
    setValue,
    modal = false,
}: {
    value: Date;
    setValue: (date: Date) => void;
    modal: boolean
}) {
    const [date, setDate] = React.useState<Date>(value ?? new Date());
    const [open, setOpen] = React.useState(false);

    return (
        <Popover open={open} onOpenChange={setOpen} modal={modal}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    id="date"
                    className="justify-between font-normal text-secondary"
                >
                    {date ? date.toLocaleDateString() : "Pilih Tanggal"}
                    <ChevronDownIcon />
                </Button>
            </PopoverTrigger>
            <PopoverContent
                className="w-auto overflow-hidden p-0 text-secondary"
                align="start"
            >
                <Calendar
                    mode="single"
                    selected={date}
                    captionLayout="dropdown"
                    onSelect={(date) => {
                        if(date){
                            setValue(date)
                            setDate(date);
                        }
                        setOpen(false);
                    }}
                />
            </PopoverContent>
        </Popover>
    );
}

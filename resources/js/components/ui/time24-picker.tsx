"use client";

import * as React from "react";
import { format, set } from "date-fns";

import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/components/ui/popover";
import { ScrollArea, ScrollBar } from "@/components/ui/scroll-area";

export function Time24Picker({
    value,
    setValue,
    modal = false,
}: {
    value: Date;
    setValue: (time: string) => void;
    modal: boolean;
}) {
    const [date, setDate] = React.useState<Date>(value);
    const [isOpen, setIsOpen] = React.useState(false);

    const hours = Array.from({ length: 24 }, (_, i) => i);

    const handleTimeChange = (type: "hour" | "minute", value: string) => {
        if (date) {
            const newDate = new Date(date);
            if (type === "hour") {
                newDate.setHours(parseInt(value));
            } else if (type === "minute") {
                newDate.setMinutes(parseInt(value));
            }
            setDate(newDate);
            setValue(format(newDate, "HH:mm"))
        }
    };

    return (
        <Popover open={isOpen} onOpenChange={setIsOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    id="date"
                    className="justify-between font-normal text-secondary"
                >
                    {date ? (
                        format(date, "HH:mm")
                    ) : (
                        <span>HH:mm</span>
                    )}
                </Button>
            </PopoverTrigger>
            <PopoverContent
                className="w-auto overflow-hidden p-0 text-secondary"
                align="start">
                <div className="sm:flex">
                    <div className="flex flex-col sm:flex-row sm:h-[200px] divide-y sm:divide-y-0 sm:divide-x">
                        <ScrollArea className="w-64 sm:w-auto">
                            <div className="flex sm:flex-col p-2">
                                <div className="grid grid-cols-5">
                                     {hours.map((hour) => (
                                        <Button
                                            key={hour}
                                            size="icon"
                                            variant={
                                                date && date.getHours() === hour
                                                    ? "default"
                                                    : "ghost"
                                            }
                                            className="sm:w-full shrink-0 aspect-square"
                                            onClick={() =>
                                                handleTimeChange(
                                                    "hour",
                                                    hour.toString(),
                                                )
                                            }
                                        >
                                            {hour}
                                        </Button>
                                    ))}
                                </div>

                            </div>
                            <ScrollBar
                                orientation="horizontal"
                                className="sm:hidden"
                            />
                        </ScrollArea>
                        <ScrollArea className="w-64 sm:w-auto">
                            <div className="flex sm:flex-col p-2">
                                 <div className="grid grid-cols-3">
                                    {Array.from(
                                        { length: 12 },
                                        (_, i) => i * 5,
                                    ).map((minute) => (
                                        <Button
                                            key={minute}
                                            size="icon"
                                            variant={
                                                date && date.getMinutes() === minute
                                                    ? "default"
                                                    : "ghost"
                                            }
                                            className="sm:w-full shrink-0 aspect-square"
                                            onClick={() =>
                                                handleTimeChange(
                                                    "minute",
                                                    minute.toString(),
                                                )
                                            }
                                        >
                                            {minute.toString().padStart(2, "0")}
                                        </Button>
                                    ))}
                                </div>
                            </div>
                            <ScrollBar
                                orientation="horizontal"
                                className="sm:hidden"
                            />
                        </ScrollArea>
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    );
}

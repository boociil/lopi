import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { AvatarGroup } from "@/components/ui/avatar-group";
import { Popover, PopoverTrigger, PopoverContent } from "@/components/ui/popover";
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from "@/components/ui/command";
import { useCalendar } from "@/components/calender_modules/contexts/calendar-context";
import { useState, useEffect } from "react";
import { Button } from "@/components/ui/button";
import { ChevronsUpDown } from "lucide-react";
import { IUser } from "../interfaces";

export function UserSelect() {
	const { users, selectedUserId, filterEventsBySelectedUser } = useCalendar();

    const [user, setUser] = useState<IUser|null>(null);

    useEffect(() => {
        const userFound = users.find(item=> item.id == selectedUserId)
        if(userFound){
            setUser(userFound)
        }else{
            setUser(null)
        }
    }, [selectedUserId]);

    const [openPengguna, setOpenPengguna] = useState(false);
    return (
         <Popover
            open={openPengguna}
            onOpenChange={setOpenPengguna}
            modal={true}
        >
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    className="w-full min-w-0 justify-between px-2 overflow-hidden"
                >
                    {selectedUserId == 'all' && (
                        <div className="flex items-center gap-2 min-w-0 truncate">
                            <span className="truncate">Semua</span>
                        </div>
                    ) }
                    {user && (
                        <div className="flex items-center gap-2 min-w-0 truncate">
                            <Avatar key={user.id} className="size-6 shrink-0">
                                <AvatarImage
                                    src={user.picturePath ?? undefined}
                                    alt={user.name}
                                />
                                <AvatarFallback className="text-xs">
                                    {user.name[0]}
                                </AvatarFallback>
                            </Avatar>

                            <p className="truncate">{user.name}</p>
                        </div>
                    ) }
                    {!(user || selectedUserId == 'all') && <span className="truncate">Tidak ditemukan.</span>}
                    <ChevronsUpDown className="opacity-50 shrink-0 ml-1" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="p-0 w-[var(--radix-popover-trigger-width)] z-[100] shadow-2xl">
                <Command>
                    <CommandInput
                        placeholder="Cari Pengguna..."
                        className="h-9"
                    />
                    <CommandList>
                        <CommandEmpty>
                            Tidak ditemukan.
                        </CommandEmpty>
                        <CommandGroup>
                            <CommandItem key="all" value="all"
                                onSelect={()=> {filterEventsBySelectedUser('all'); setOpenPengguna(false);}}>
                                <AvatarGroup className="mx-2 flex items-center" max={2}>
                                    {users.map((user) => (
                                        <Avatar key={user.id} className="size-6 text-xs">
                                            <AvatarImage
                                                src={user.picturePath ?? undefined}
                                                alt={user.name}
                                            />
                                            <AvatarFallback className="text-xs">
                                                {user.name[0]}
                                            </AvatarFallback>
                                        </Avatar>
                                    ))}
                                </AvatarGroup>
                                Semua
                            </CommandItem>
                            {users.map((item) => (
                                <CommandItem
                                    key={`${item.id}-${item.name}`}
                                    value={`${item.name} ${item.id}`}
                                    onSelect={() => {
                                        filterEventsBySelectedUser(item.id)
                                        setOpenPengguna(false);
                                    }}
                                >
                                    <div className="flex items-center gap-2">
                                        <Avatar key={item.id} className="size-6">
                                            <AvatarImage
                                                src={item.picturePath ?? undefined}
                                                alt={item.name}
                                            />
                                            <AvatarFallback className="text-xs">
                                                {item.name[0]}
                                            </AvatarFallback>
                                        </Avatar>

                                        <p className="truncate">{item.name}</p>
                                    </div>
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    )

}

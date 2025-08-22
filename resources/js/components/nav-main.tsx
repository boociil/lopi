import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem, SidebarMenuSub, SidebarMenuSubButton, SidebarMenuSubItem } from '@/components/ui/sidebar';
import { type NavGeneral } from '@/types';
import { NavGeneralTS } from './nav-general';

export function NavMain({ items = [] }: { items: NavGeneral[] }) {
    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>Menu</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => (
                    <NavGeneralTS key={item.href} item={item}></NavGeneralTS>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}



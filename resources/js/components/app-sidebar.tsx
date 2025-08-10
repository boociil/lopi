import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type Peran, type NavGeneral } from '@/types';
import { Link } from '@inertiajs/react';
import { LayoutGrid, CalendarDays, Video } from 'lucide-react';
import AppLogo from './app-logo';
import { type PropsWithChildren } from 'react';

export function AppSidebar({peran=[]}: PropsWithChildren<{ peran?: Peran[]}>) {
    const mainNavItems: NavGeneral[] = [
        {
            type: 'item',
            title: 'Beranda',
            href: '/lopi/beranda',
            icon: LayoutGrid,
        },
        {
            type: 'group',
            title: 'Jadwal',
            href: '/lopi/jadwal',
            icon: CalendarDays,
            subItems: [
                {
                    title: 'Kalender',
                    href: '/lopi/jadwal',
                },
                {
                    title: 'Ruang',
                    href: '/lopi/jadwal/ruang',
                },
            ]
        },
        {
            type: 'item',
            title: 'Zoom',
            href: '/lopi/zoom',
            icon: Video,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="./beranda" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

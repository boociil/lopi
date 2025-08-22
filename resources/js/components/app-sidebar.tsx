import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type Peran, type NavGeneral, type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, CalendarDays, Video, Folder, BookOpen, LinkIcon } from 'lucide-react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const mainNavItems: NavGeneral[] = [
        {
            type: 'item',
            title: 'Beranda',
            href: '/lopi/beranda',
            icon: LayoutGrid,
        },
        {
            type: 'item',
            title: 'Jadwal',
            href: '/lopi/jadwal',
            icon: CalendarDays,
        },
        {
            type: 'item',
            title: 'Zoom',
            href: '/lopi/zoom',
            icon: Video,
        },
    ];

    const tahun_active = usePage<SharedData>().props.tahun_active;
    const footerNavItems: NavGeneral[] = [
        {
            type: 'item',
            title: 'Tahun Aktif : '+tahun_active,
            href: '/lopi/tahun',
            icon: LinkIcon,
            subItems: [
                {
                    title: 'Tahun 2022',
                    href: 'tahun/2022/ubah',
                },
                {
                    title: 'Tahun 2023',
                    href: 'tahun/2022/ubah',
                },
                {
                    title: 'Tahun 2024',
                    href: 'tahun/2022/ubah',
                },
                {
                    title: 'Tahun 2025',
                    href: 'tahun/2022/ubah',
                },
            ]
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
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

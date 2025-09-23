import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type Peran, type NavGeneral, type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, CalendarDays, Video, LinkIcon, LockKeyholeOpen, Car } from 'lucide-react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const auth = usePage<SharedData>().props.auth;
    const peran = auth.peran;


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
            href: '/lopi/pinjam/jadwal',
            icon: CalendarDays,
        },
        {
            type: 'item',
            title: 'Pinjam Zoom',
            href: '/lopi/pinjam/zoom',
            icon: Video,
        },
        {
            type: 'item',
            title: 'Pinjam Mobil',
            href: '/lopi/pinjam/mobil',
            icon: Car,
        }
    ];

    let find_super_admin = peran.find(item => {
        if(item.peran == 'admin' && item.tipe_peran == 'global' && item.tahun == null){
            return true
        }
    })
    if(find_super_admin){
        mainNavItems.push({
            type: 'item',
            title: 'Peran',
            href: '/lopi/peran',
            icon: LockKeyholeOpen,
        })
    }

    const tahun_active = usePage<SharedData>().props.tahun_active;
    const footerNavItems: NavGeneral[] = [
        {

            type: 'group',
            title: 'Tahun Aktif : '+tahun_active,
            href: '/lopi/tahun',
            icon: LinkIcon,
            subItems: [
                {
                    title: 'Tahun 2022',
                    href: '/lopi/tahun/2022/ubah',
                },
                {
                    title: 'Tahun 2023',
                    href: '/lopi/tahun/2023/ubah',
                },
                {
                    title: 'Tahun 2024',
                    href: '/lopi/tahun/2024/ubah',
                },
                {
                    title: 'Tahun 2025',
                    href: '/lopi/tahun/2025/ubah',
                },
            ]
        }
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

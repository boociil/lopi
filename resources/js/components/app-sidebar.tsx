import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import type { Peran, NavGeneral, NavItem, SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, CalendarDays, LockKeyholeOpen, Car, SmilePlus, Archive, Hospital, Video } from 'lucide-react';
import AppLogo from './app-logo';
import { useEffect, useState } from 'react';
import { useApi } from '@/lib/API/useApi';

export function AppSidebar() {
    const auth = usePage<SharedData>().props.auth;
    const peran = auth.peran;
    const axiosClient = useApi();


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
            title: 'Pemakaian Zoom',
            href: '/lopi/pinjam/zoom',
            icon: Video,
        },
        {
            type: 'item',
            title: 'Pemakaian Mobil',
            href: '/lopi/pinjam/mobil',
            icon: Car,
        }
    ];

    let find_arsip = peran.find(item => {
        if(item.peran == 'arsip'){
            return true
        }
    })

     mainNavItems.push( {
        type: 'item',
        title: 'Kesehatan',
        href: '/lopi/kesehatan',
        icon: Hospital,
    })


    let find_appreciate = peran.find(item => {
        if(item.peran == 'appreciate'){
            return true
        }
    })

    if(find_appreciate){
        mainNavItems.push({
            type: 'group',
            title: 'Toples Apresiasi',
            href: '/lopi/appreciate',
            icon: SmilePlus,
            subItems: [
                {
                    title: 'Master',
                    href: '/lopi/appreciate/master',
                },
                {
                    title: 'Kirim',
                    href: '/lopi/appreciate',
                },
                {
                    title: 'Untukmu',
                    href: '/lopi/appreciate/buka',
                },
                {
                    title: 'Approval',
                    href: '/lopi/appreciate/aproval',
                },
                {
                    title: 'baca',
                    href: '/lopi/appreciate/baca',
                },
            ]
        })
    }else{
         mainNavItems.push({
            type: 'group',
            title: 'Toples Apresiasi',
            href: '/lopi/appreciate',
            icon: SmilePlus,
            subItems: [
                {
                    title: 'Kirim',
                    href: '/lopi/appreciate',
                },
                {
                    title: 'Untukmu',
                    href: '/lopi/appreciate/buka',
                },
            ]
        })
    }


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
    // const footerNavItems: NavGeneral[] = [
    //     {

    //         type: 'group',
    //         title: 'Tahun Aktif : '+tahun_active,
    //         href: '/lopi/tahun',
    //         icon: LinkIcon,
    //         subItems: [
    //             {
    //                 title: 'Tahun 2022',
    //                 href: '/lopi/tahun/2022/ubah',
    //             },
    //             {
    //                 title: 'Tahun 2023',
    //                 href: '/lopi/tahun/2023/ubah',
    //             },
    //             {
    //                 title: 'Tahun 2024',
    //                 href: '/lopi/tahun/2024/ubah',
    //             },
    //             {
    //                 title: 'Tahun 2025',
    //                 href: '/lopi/tahun/2025/ubah',
    //             },
    //         ]
    //     }
    // ];

    const footerNavItems: NavGeneral[] = []

    const [countMenu, setCountMenu] = useState<Map<string, number>>(new Map<string, number>);


    const fetchData = async () => {
         axiosClient.post(route('notif-count'))
            .then(function (response) {
                let countMenu_ = new Map<string, number>();
                const data = response.data
                Object.entries(data).forEach(([key, value]) => {
                    countMenu_.set(key as string, value as number)
                });
                setCountMenu(countMenu_)
            })
            .catch(function (error) {
                console.log(error)
            });
    };

    useEffect(() => {
        // fetch pertama kali
        fetchData();

        // fetch tiap 1 menit
        const interval = setInterval(fetchData, 60_000);

        // cleanup saat component unmount
        return () => clearInterval(interval);
    }, []);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/lopi/beranda" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} countMenu={countMenu}/>
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

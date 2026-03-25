import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import type { NavGeneral, SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, CalendarDays, LockKeyholeOpen, Car, SmilePlus, Hospital, Video, UserRoundCog, LinkIcon, CalendarCheck, Wallet } from 'lucide-react';
import AppLogo from './app-logo';
import { useEffect, useState } from 'react';
import { useApi } from '@/lib/API/useApi';

export function AppSidebar() {
    const auth = usePage<SharedData>().props.auth;
    const peran = auth.peran;
    const axiosClient = useApi();

    let satuan_kerja_tahun_7600 = auth.satuan_kerja_tahun.find(item => item.kode_satuan_kerja == '7600')
    let have_7600 = false

    if(satuan_kerja_tahun_7600){
        if(peran.find(item => item.parent_id == satuan_kerja_tahun_7600.id)){
            have_7600 = true
        }
    }


     const mainNavItems: NavGeneral[] = [
        {
            type: 'item',
            title: 'Beranda',
            href: '/lopi/beranda',
            icon: LayoutGrid,
        }
    ]

    if(!have_7600){
        mainNavItems.push(...[
            {
                type: 'item',
                title: 'Cek Cuti',
                href: '/lopi/cek_cuti',
                icon: CalendarCheck,
            },
        ])

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
                        title: 'Rekap Pegawai',
                        href: '/lopi/appreciate/rekap',
                    },
                    {
                        title: 'Baca',
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
    }else {
        mainNavItems.push(...[
            // {
            //     type: 'item',
            //     title: 'Rumah Link',
            //     href: '/lopi/rumah_link',
            //     icon: LinkIcon,
            // },
            {
                type: 'item',
                title: 'Cek Cuti',
                href: '/lopi/cek_cuti',
                icon: CalendarCheck,
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
            },
            // {
            //     type: 'group',
            //     title: 'Monica',
            //     href: '/lopi/monica',
            //     icon: Wallet,
            //     subItems: [
            //         {
            //             title: 'Master',
            //             href: '/lopi/monica/dashboard',
            //         },
            //         {
            //             title: 'Form',
            //             href: '/lopi/monica/form',
            //         },
            //     ]
            // }
        ])


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
                        title: 'Rekap Pegawai',
                        href: '/lopi/appreciate/rekap',
                    },
                    {
                        title: 'Baca',
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

        mainNavItems.push( {
            type: 'item',
            title: 'Kesehatan',
            href: '/lopi/kesehatan',
            icon: Hospital,
        })


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

        let find_global_admin = peran.find(item => {
            if(item.tipe_peran == 'global' && item.peran == 'admin'){
                return true
            }
        })

        if(find_global_admin){
            mainNavItems.push({
                type: 'item',
                title: 'Pengguna',
                href: '/lopi/admin/pengguna',
                icon: UserRoundCog,
            })
        }
    }




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
        const interval = setInterval(fetchData, 60*60*1000);

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

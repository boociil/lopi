import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import type { NavGeneral, NavItem, SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, CalendarDays, LockKeyholeOpen, SmilePlus, Hospital, UserRoundCog, CalendarCheck, Wallet, PackageSearch, GanttChart, Book, BookOpen, Link2 } from 'lucide-react';
import AppLogo from './app-logo';
import { useEffect, useMemo, useRef, useState } from 'react';
import { useApi } from '@/lib/API/useApi';
import { BUKU_NOTIF_REFRESH_EVENT } from '@/lib/buku-notif';

// Cache kecil di level modul: sidebar dimuat ulang tiap pindah halaman,
// jadi angka badge tidak perlu diambil ulang terus-menerus.
let notifCountCache: Record<string, number> | null = null;
let notifFetchedAt = 0;
const NOTIF_REFRESH_MS = 60 * 1000;

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
            // {
            //     type: 'item',
            //     title: 'Timeline',
            //     href: '/lopi/timeline',
            //     icon: GanttChart,
            // },
            {
                type: 'item',
                title: 'OneTap',
                href: '/lopi/onetap',
                icon: Link2,
            },
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
                type: 'group',
                title: 'Peminjaman',
                href: '/lopi/pinjam',
                icon: PackageSearch,
                subItems: [
                    {
                        title: 'Pemakaian Zoom',
                        href: '/lopi/pinjam/zoom',
                    },
                    {
                        title: 'Pemakaian Ruang',
                        href: '/lopi/pinjam/ruang',
                    },
                    {
                         title: 'Pemakaian Mobil',
                        href: '/lopi/pinjam/mobil',
                    }
                ]
            },
            // {
            //     type: 'item',
            //     title: 'Monica',
            //     href: '/lopi/monica',
            //     icon: Wallet,
            // },
            // {
            //     type: 'group',
            //     title: 'IT-Valet',
            //     href: '/lopi/it-valet',
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
            // },
            // {
            //     type: 'item',
            //     title: 'KMS',
            //     href: '/lopi/kms',
            //     icon: BookOpen
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




    const roleBuku = auth.user.role_buku ?? 'umum';
    const isAdminBuku = roleBuku === 'admin_buku' || roleBuku === 'super_admin';

    const subMenuBuku: NavItem[] = [
        {
            title: 'List Buku',
            href: '/lopi/buku',
        },
    ];

    if (isAdminBuku) {
        subMenuBuku.push(
            {
                title: 'Pinjaman Buku',
                href: '/lopi/buku/master',
            },
            {
                title: 'Master Buku',
                href: '/lopi/buku/manajemen',
            },
            {
                title: 'Manajemen Admin',
                href: '/lopi/buku/admin',
            },
        );
    }

    mainNavItems.push({
        type: 'group',
        title: 'Buku',
        href: '/lopi/buku',
        icon: BookOpen,
        subItems: subMenuBuku,
    });

    const footerNavItems: NavGeneral[] = []

    const [countMenu, setCountMenu] = useState<Map<string, number>>(
        () => new Map(Object.entries(notifCountCache ?? {})),
    );

    // Badge Buku dimuat lewat endpoint ringan sendiri supaya tidak menunggu
    // endpoint notif besar (yang juga memanggil layanan eksternal).
    const [bukuCount, setBukuCount] = useState(0);
    const bukuRequest = useRef<AbortController | null>(null);
    const notifRequest = useRef<AbortController | null>(null);
    const notifRetryTimer = useRef<number | null>(null);


    const fetchData = (force = false) => {
        if (!force) {
            // Hindari memanggil endpoint berat di setiap perpindahan halaman.
            const sisa = NOTIF_REFRESH_MS - (Date.now() - notifFetchedAt);
            if (sisa > 0) {
                if (notifRetryTimer.current) clearTimeout(notifRetryTimer.current);
                notifRetryTimer.current = window.setTimeout(() => fetchData(true), sisa);
                return;
            }
        }

        notifRequest.current?.abort();
        const controller = new AbortController();
        notifRequest.current = controller;
        notifFetchedAt = Date.now();

        axiosClient.post(route('notif-count'), undefined, { signal: controller.signal })
            .then(function (response) {
                let countMenu_ = new Map<string, number>();
                const data = response.data
                Object.entries(data).forEach(([key, value]) => {
                    countMenu_.set(key as string, value as number)
                });
                notifCountCache = data as Record<string, number>;
                setCountMenu(countMenu_)
            })
            .catch(function (error) {
                // Saat dibatalkan karena pindah halaman, jangan reset timer.
                if (error?.name === 'AbortError') return;
                notifFetchedAt = 0;
                console.log(error)
            });
    };

    const fetchBukuCount = () => {
        bukuRequest.current?.abort();
        const controller = new AbortController();
        bukuRequest.current = controller;

        axiosClient.post(route('buku-notif-count'), undefined, { signal: controller.signal })
            .then((response) => {
                const count = Number(response.data?.count ?? 0);
                setBukuCount(Number.isFinite(count) ? count : 0);
            })
            .catch((error) => {
                if (error?.name === 'AbortError') return;
                console.log(error);
            });
    };

    // Badge menu lain tetap memakai data dari endpoint notif yang lama.
    const menuCount = useMemo(() => {
        const merged = new Map(countMenu);
        merged.set('-lopi-buku-master', bukuCount);
        return merged;
    }, [countMenu, bukuCount]);

    useEffect(() => {
        // fetch pertama kali
        fetchData();
        fetchBukuCount();

        // fetch tiap 1 jam (fallback)
        const interval = setInterval(() => fetchData(true), 60*60*1000);
        // badge Buku ringan, jadi boleh lebih sering
        const bukuInterval = setInterval(fetchBukuCount, 3*60*1000);

        // update langsung setelah ada aksi di fitur Buku
        const refreshBuku = (event: Event) => {
            const delta = Number((event as CustomEvent<{ delta?: number }>).detail?.delta ?? 0);
            if (delta) {
                setBukuCount((prev) => Math.max(0, prev + delta));
            }
            fetchBukuCount();
        };
        window.addEventListener(BUKU_NOTIF_REFRESH_EVENT, refreshBuku);

        // cleanup saat component unmount
        return () => {
            clearInterval(interval);
            clearInterval(bukuInterval);
            window.removeEventListener(BUKU_NOTIF_REFRESH_EVENT, refreshBuku);
            bukuRequest.current?.abort();
            notifRequest.current?.abort();
            if (notifRetryTimer.current) clearTimeout(notifRetryTimer.current);
        };
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
                <NavMain items={mainNavItems} countMenu={menuCount}/>
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

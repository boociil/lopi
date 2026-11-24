import {  SidebarMenuButton, SidebarMenuItem, SidebarMenuSub, SidebarMenuSubButton, SidebarMenuSubItem } from '@/components/ui/sidebar';
import { type NavGeneral } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from './ui/collapsible';
import { ChevronDown } from 'lucide-react';
import { Badge } from './ui/badge';


export function NavGeneralTS({ item , countMenu}: { item: NavGeneral , countMenu:  Map<string, number>}) {
    const page = usePage();

    // URL Inertia bisa tanpa prefix /lopi, sedangkan href menu memakai /lopi.
    const currentPath = page.url.replace(/^\/lopi/, '').split(/[?#]/)[0].replace(/\/+$/, '') || '/';
    const cocokPath = (href: string) => currentPath.startsWith(href.replace(/^\/lopi/, ''));
    // Halaman Buku sering berubah datanya (pinjam/ACC/tolak), jadi jangan
    // memakai cache prefetch Inertia agar data yang tampil selalu terbaru.
    const bolehPrefetch = (href: string) => !href.startsWith('/lopi/buku');
    // Khusus grup Buku: header grup tidak ikut menyala, hanya submenu
    // yang sedang dibuka yang ditandai aktif.
    const grupBuku = item.type === 'group' && item.href.startsWith('/lopi/buku');
    const subMenuBukuAktif = (href: string) => {
        const target = href.replace(/^\/lopi/, '').replace(/\/+$/, '');
        if (currentPath === target) return true;
        // Halaman detail buku (/buku/{id}) tetap menandai submenu List Buku.
        if (target === '/buku' && /^\/buku\/\d+$/.test(currentPath)) return true;
        return false;
    };

    if (item.type == 'item'){
         return (
            <SidebarMenuItem key={item.title}>
                <SidebarMenuButton asChild isActive={page.url.startsWith(item.href)} tooltip={{ children: item.title }}>
                    <Link href={item.href} prefetch={bolehPrefetch(item.href)} className='flex items-center justify-between'>
                        {item.icon && <item.icon />}
                        <span className='grow'>{item.title}</span>

                        { (countMenu.has(item.href.replaceAll('/', '-')) && countMenu.get(item.href.replaceAll('/', '-')) as number > 0) &&
                             <Badge  className="h-5 min-w-5 rounded-full px-1 font-mono tabular-nums" variant="default">
                                {countMenu.get(item.href.replaceAll('/', '-'))}
                            </Badge>
                        }

                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        );
    }else if(item.type == 'group'){
        return (
            <Collapsible key={item.title} asChild defaultOpen={cocokPath(item.href)} className="group/collapsible">
                <SidebarMenuItem>
                    <CollapsibleTrigger asChild>
                        <SidebarMenuButton isActive={cocokPath(item.href) && !grupBuku} tooltip={item.title}>
                            {item.icon && <item.icon />}
                                <span className="grow">{item.title}</span>
                                <ChevronDown className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                        </SidebarMenuButton>
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                        <SidebarMenuSub>
                            {item.subItems ?. map((subItem) => (
                                <SidebarMenuSubItem key={subItem.title}>
                                    <SidebarMenuSubButton
                                        asChild
                                        isActive={grupBuku ? subMenuBukuAktif(subItem.href) : false}
                                        className={
                                            grupBuku
                                                ? "data-[active=true]:bg-primary data-[active=true]:font-medium data-[active=true]:text-primary-foreground hover:data-[active=true]:bg-primary hover:data-[active=true]:text-primary-foreground"
                                                : undefined
                                        }
                                    >
                                        <Link href={subItem.href} prefetch={bolehPrefetch(subItem.href)} className='flex items-center justify-between'>
                                            <span>{subItem.title}</span>
                                            { (countMenu.has(subItem.href.replaceAll('/', '-')) && countMenu.get(subItem.href.replaceAll('/', '-')) as number > 0) &&
                                                <Badge
                                                    className={
                                                        grupBuku && subItem.href === '/lopi/buku/master'
                                                            ? "h-5 min-w-5 rounded-full border border-primary bg-white px-1 font-mono text-primary tabular-nums"
                                                            : "h-5 min-w-5 rounded-full px-1 font-mono tabular-nums"
                                                    }
                                                    variant="default"
                                                >
                                                    {countMenu.get(subItem.href.replaceAll('/', '-'))}
                                                </Badge>
                                            }
                                        </Link>

                                    </SidebarMenuSubButton>

                                </SidebarMenuSubItem>
                            ))}
                        </SidebarMenuSub>
                    </CollapsibleContent>
                </SidebarMenuItem>
            </Collapsible>
        )
    }
}

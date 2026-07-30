import {  SidebarMenuButton, SidebarMenuItem, SidebarMenuSub, SidebarMenuSubButton, SidebarMenuSubItem } from '@/components/ui/sidebar';
import { type NavGeneral } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from './ui/collapsible';
import { ChevronDown } from 'lucide-react';
import { Badge } from './ui/badge';


export function NavGeneralTS({ item , countMenu}: { item: NavGeneral , countMenu:  Map<string, number>}) {
    const page = usePage();
    if (item.type == 'item'){
         return (
            <SidebarMenuItem key={item.title}>
                <SidebarMenuButton asChild isActive={page.url.startsWith(item.href)} tooltip={{ children: item.title }}>
                    <Link href={item.href} prefetch className='flex items-center justify-between'>
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
            <Collapsible key={item.title} asChild defaultOpen={page.url.startsWith(item.href)} className="group/collapsible">
                <SidebarMenuItem>
                    <CollapsibleTrigger asChild>
                        <SidebarMenuButton isActive={page.url.startsWith(item.href)} tooltip={item.title}>
                            {item.icon && <item.icon />}
                                <span>{item.title}</span>
                                <ChevronDown className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                        </SidebarMenuButton>
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                        <SidebarMenuSub>
                            {item.subItems ?. map((subItem) => (
                                <SidebarMenuSubItem key={subItem.title}>
                                    <SidebarMenuSubButton asChild isActive={false}>
                                        <Link href={subItem.href} prefetch className='flex items-center justify-between'>
                                            <span>{subItem.title}</span>
                                            { (countMenu.has(subItem.href.replaceAll('/', '-')) && countMenu.get(subItem.href.replaceAll('/', '-')) as number > 0) &&
                                                <Badge  className="h-5 min-w-5 rounded-full px-1 font-mono tabular-nums" variant="default">
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

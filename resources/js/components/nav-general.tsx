import {  SidebarMenuButton, SidebarMenuItem, SidebarMenuSub, SidebarMenuSubButton, SidebarMenuSubItem } from '@/components/ui/sidebar';
import { type NavGeneral } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from './ui/collapsible';
import { ChevronDown } from 'lucide-react';


export function NavGeneralTS({ item }: { item: NavGeneral }) {
    const page = usePage();
    if (item.type == 'item'){
         return (
            <SidebarMenuItem key={item.title}>
                <SidebarMenuButton asChild isActive={page.url.startsWith(item.href)} tooltip={{ children: item.title }}>
                    <Link href={item.href} prefetch>
                        {item.icon && <item.icon />}
                        <span>{item.title}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        );
    }else if(item.type == 'group'){
        return (
                <Collapsible key={item.title} asChild defaultOpen={false} className="group/collapsible">
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
                                            <Link href={subItem.href} prefetch>
                                            <span>{subItem.title}</span>
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

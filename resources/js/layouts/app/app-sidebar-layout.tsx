import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { type Peran, type BreadcrumbItem, type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { Toaster, toast } from 'sonner';
import type { PropsWithChildren } from 'react';

export default function AppSidebarLayout({ children, breadcrumbs = [] }: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    const props = usePage<SharedData>().props;
        if(props.flash.info){
            toast.info(props.flash.info)
        }
        if(props.flash.success){
            toast.success(props.flash.success)
        }
        if(props.flash.error){
            toast.error(props.flash.error)
        }

    return (
        <AppShell variant="sidebar">
            <Toaster />
            <AppSidebar/>
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
        </AppShell>
    );
}

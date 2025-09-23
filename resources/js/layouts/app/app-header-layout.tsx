import { AppContent } from '@/components/app-content';
import { AppHeader } from '@/components/app-header';
import { AppShell } from '@/components/app-shell';
import { type Peran, type BreadcrumbItem, type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { Toaster, toast } from 'sonner';
import type { PropsWithChildren } from 'react';

export default function AppHeaderLayout({ children, breadcrumbs , peran =[]}: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] , peran?: Peran[]}>) {

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
        <AppShell>
            <Toaster />
            <AppHeader breadcrumbs={breadcrumbs} />
            <AppContent>{children}</AppContent>
        </AppShell>
    );
}

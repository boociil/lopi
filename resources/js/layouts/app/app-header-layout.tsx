import { AppContent } from '@/components/app-content';
import { AppHeader } from '@/components/app-header';
import { AppShell } from '@/components/app-shell';
import { type Peran, type BreadcrumbItem } from '@/types';
import type { PropsWithChildren } from 'react';
import { Toaster } from 'sonner';

export default function AppHeaderLayout({ children, breadcrumbs , peran =[]}: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] , peran?: Peran[]}>) {
    return (
        <AppShell>
            <Toaster />
            <AppHeader breadcrumbs={breadcrumbs} />
            <AppContent>{children}</AppContent>
        </AppShell>
    );
}

import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { Peran, type BreadcrumbItem } from '@/types';
import { type ReactNode } from 'react';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
    peran? : Peran[];
}

export default ({ children, breadcrumbs, peran, ...props }: AppLayoutProps) => (
    <AppLayoutTemplate peran={peran} breadcrumbs={breadcrumbs} {...props}>
        {children}
    </AppLayoutTemplate>
);

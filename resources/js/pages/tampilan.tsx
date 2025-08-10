import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { Peran, type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { cn } from '@/lib/utils';

import { LucideIcon, Monitor, Moon, Sun } from 'lucide-react';
import { Appearance, useAppearance } from '@/hooks/use-appearance';
import HeadingSmall from '@/components/heading-small';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tampilan',
        href: '/tampilan',
    },
];

export default function Tampilan(peran : Peran[]) {
    const { appearance, updateAppearance } = useAppearance();

    const tabs: { value: Appearance; icon: LucideIcon; label: string }[] = [
        { value: 'light', icon: Sun, label: 'Light' },
        { value: 'dark', icon: Moon, label: 'Dark' },
        { value: 'system', icon: Monitor, label: 'System' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs} peran={peran}>
            <Head title="Tampilan" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">
                        <HeadingSmall title="Pengaturan Tampilam" description="Perbarui pengaturan tampilan akun Anda" />
                        <div className={cn('inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800')}>
                            {tabs.map(({ value, icon: Icon, label }) => (
                                <button
                                    key={value}
                                    onClick={() => updateAppearance(value)}
                                    className={cn(
                                        'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                                        appearance === value
                                            ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                                            : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                                    )}
                                >
                                    <Icon className="-ml-1 h-4 w-4" />
                                    <span className="ml-1.5 text-sm">{label}</span>
                                </button>
                            ))}
                        </div>
                    </section>
                </div>

            </div>
        </AppLayout>
    );
}

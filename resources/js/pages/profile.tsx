import AppLayout from '@/layouts/app-layout';
import { SharedData, type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { toast } from "sonner";
import { Link, PencilIcon, PhoneCall } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useApi } from '@/lib/API/useApi';
import { useState } from 'react';
import { useLoading } from '@/props/LoadingContext';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile',
        href: '/lopi/profile',
    },
];

export default function Profile({
    calendar_id,
    no_whatsapp
}: React.PropsWithChildren<{
    calendar_id: string | null
    no_whatsapp: string | null
}>) {

    const { auth } = usePage<SharedData>().props;
    const user = auth.user

    const [calendarId, setCalendarId] = useState(calendar_id);
    const axiosClient = useApi();
    const {setIsLoading} = useLoading();

    const handleIntegrasiCalendar = () => {
        setIsLoading(true)
        axiosClient.post(route('profile-create-calender'))
            .then(function (response) {
                setCalendarId(response.data as string)
                setIsLoading(false)
                toast.success("Berhasil memperbarui data.");
            })
            .catch(function (error) {
                setIsLoading(false)
                toast.success("Gagal memperbarui data.");
            });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profil" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">
                        <Heading title="Profile" description="Perbarui pengaturan user" />
                        <div className="w-full bg-white rounded-3xl shadow-sm border border-slate-200 p-6 md:p-8 transition-all">

                            <div className="flex items-center gap-4 mb-8">
                                <div className="text-left">
                                    <h1 className="text-xl font-bold text-slate-800 tracking-tight">{user.nama}</h1>
                                    <p className="text-sm font-medium text-slate-500 mt-0.5">{user.jabatan}</p>
                                </div>
                            </div>

                            <div className="space-y-3">

                                <div className="group flex items-center justify-between p-3.5 bg-slate-50 rounded-xl hover:bg-slate-100 transition-colors border border-transparent hover:border-slate-200">
                                    <div className="flex items-center gap-3 flex-1">
                                        <div className="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center shadow-sm">
                                            <i className="ph-fill ph-whatsapp-logo text-lg"></i>
                                            <PhoneCall size={16}></PhoneCall>
                                        </div>
                                        <div className="text-left">
                                            <p className="text-[11px] font-medium text-slate-500 mb-0.5">WhatsApp</p>
                                            <p className="text-sm font-semibold text-slate-800"> {no_whatsapp ? no_whatsapp : '-'}</p>
                                        </div>
                                    </div>
                                    <a href='https://wa.me/6282195195502' target='_blank' className="p-2 ml-2 text-slate-400 hover:text-orange-500 hover:bg-orange-50 rounded-lg transition-all outline-none focus:ring-2 focus:ring-orange-100" title="Edit Nomor WhatsApp">
                                        <PencilIcon size={16}></PencilIcon>
                                    </a>
                                </div>

                            </div>

                            <div className="mt-8 pt-6 border-t border-slate-100 text-left">
                                <h2 className="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-3">Sinkronisasi Kalender</h2>
                                <div className="flex flex-col sm:flex-row gap-2.5">
                                    <Button onClick={() => {handleIntegrasiCalendar()}} disabled={calendarId != ''} variant="default">
                                        <i className="ph ph-calendar-plus text-lg"></i>
                                        <span>Integrasi</span>
                                    </Button>
                                    <Button variant="outline" onClick={() => {navigator.clipboard.writeText(calendarId as string); toast.success("Copy calender id.");}}  title="Salin Kode Kalender">
                                        <i className="ph ph-copy text-lg text-slate-400"></i>
                                        <span>Copy Code</span>
                                    </Button>
                                </div>
                                <p className="text-[11px] text-slate-400 mt-2.5 leading-relaxed">Hubungkan jadwal secara otomatis atau salin kode unik Anda untuk setup manual.</p>
                            </div>

                        </div>
                    </section>
                </div>

            </div>
        </AppLayout>
    );
}

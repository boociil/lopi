import AppLayout from "@/layouts/app-layout";
import {
    type User,
    type BreadcrumbItem,
} from "@/types";
import { Head, Link } from "@inertiajs/react";
import Heading from "@/components/heading";

import { PinjamRuangCalender } from "@/props/pinjam-ruang/calender";
import { jadwal } from "@/props/pinjam-ruang/types";

import { X, Info } from "lucide-react";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { useLocalStorageWithTimeout } from "@/hooks/use-storage";


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Pemakaian Ruang",
        href: "/lopi/pinjam/ruang",
    },
];

export default function Index({
    jadwal_data,
    ruang,
    have_ruang_peran,
     user,
}: React.PropsWithChildren<{
    jadwal_data: jadwal[];
    ruang: string[];
    have_ruang_peran: boolean;
    user: User[];
}>) {

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pemakaian Ruang" />
                <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                    <div className="w-full">
                        <Heading
                            title="Pemakaian Ruang"
                            description="Jadwal Pemakaian Ruang Sulawesi Barat."
                        />
                        <PinjamRuangCalender
                            jadwal_data={jadwal_data}
                            ruang={ruang}
                            have_ruang_peran={have_ruang_peran}
                            user={user}>
                        </PinjamRuangCalender>
                    </div>
                </div>

        </AppLayout>
    );
}

export function InfoTopCard() {
  const [isVisible, setIsVisible] = useLocalStorageWithTimeout<boolean>('ruang_info_1', true, 3 * 3600000);

  if (!isVisible) return null;

  return (
    <Card className="border-blue-200 bg-blue-50 dark:border-blue-900 dark:bg-blue-950/40 gap-1 mx-1">
      <CardHeader className="flex flex-row items-start justify-between space-y-0 -pb-1">
        <div className="flex items-center gap-2">
          <Info className="h-5 w-5 text-blue-600 dark:text-blue-400" />
          <CardTitle className="text-sm font-semibold text-blue-900 dark:text-blue-100">
            Pemberitahuan !!!
          </CardTitle>
        </div>
        <Button
          variant="ghost"
          size="icon"
          className="-mr-2 -mt-2 h-8 w-8 text-blue-600 hover:bg-blue-200 dark:text-blue-400 dark:hover:bg-blue-900"
          onClick={() => setIsVisible(false)}
        >
          <X className="h-4 w-4" />
          <span className="sr-only">Dismiss information</span>
        </Button>
      </CardHeader>
      <CardContent className="text-sm text-blue-800 dark:text-blue-300">
        Penambahan peminjaman zoom dapat dilakukan <Link className="underline" href={route('jadwal-index')}>disini</Link>
      </CardContent>
    </Card>
  );
}

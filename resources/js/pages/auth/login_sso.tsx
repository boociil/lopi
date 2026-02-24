import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

import {
  Card,
  CardContent,
  CardHeader,
} from "@/components/ui/card"

import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"

type LoginForm = {
    tahun: string;
};

interface LoginProps {
    tahun_array: [];
    tahun_default: string;
}

export default function Login({ tahun_array, tahun_default }: LoginProps) {
    const { data, setData, processing, errors, reset } = useForm<Required<LoginForm>>({
        tahun: tahun_default,
    });

    return (
         <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-sidebar md:p-10">
            <Head title="Log in" />
            <div className="w-full max-w-sm">
                <Card className="flex flex-col">
                    <CardHeader>
                        <div className="flex flex-col items-center gap-4">
                            <div className="flex flex-col items-center gap-2 font-medium">
                                <div className="mb-1 flex h-24 w-24 items-center justify-center rounded-md">
                                    <img className="object-cover  h-24 w-24  rounded" src="public/img/logo.png"></img>
                                </div>
                                <span className="sr-only">Lopi</span>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <form className="flex flex-col gap-6" action="/lopi/sso">
                            <div className="grid gap-6">
                                <div className="grid gap-2">
                                    <Label htmlFor="tahun">Tahun</Label>
                                    <Select
                                        name='tahun'
                                        value={tahun_default}
                                        onValueChange={(e) => setData('tahun', e)}
                                        >
                                        <SelectTrigger id="tahun">
                                            <SelectValue placeholder="Tahun" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectLabel>Tahun Aktif</SelectLabel>
                                                {tahun_array.map((item) => (
                                                    <SelectItem key={item} value={item}>{item}</SelectItem>
                                                ))}
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.tahun} />
                                </div>



                                <Button type="submit" className="mt-4 w-full focus-visible:ring-ring bg-primary text-primary-foreground hover:bg-primary/90" tabIndex={4} disabled={processing}>
                                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                    Log in
                                </Button>
                            </div>

                        </form>
                    </CardContent>
                    {status && <div className="mb-4 text-center text-sm font-medium text-green-600">{status}</div>}
                </Card>
            </div>
        </div>
    );
}

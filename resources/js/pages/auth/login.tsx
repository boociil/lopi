import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
    username: string;
    password: string;
    tahun_active: string;
};

interface LoginProps {
    tahun_array: [];
    tahun_default: string;
    status: string;
}

export default function Login({ tahun_array, tahun_default, status }: LoginProps) {
    const { data, setData, post, processing, errors, reset } = useForm<Required<LoginForm>>({
        username: '',
        password: '',
        tahun_active: tahun_default,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
         <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-background md:p-10">
            <Head title="Login" >
                <link id="favicon" rel="icon" href="{{ asset('public/img/favicon.ico') }}" type="image/x-icon" />
            </Head>
            <div className="w-full max-w-sm">
                <Card className="bg-accent flex flex-col gap-8 p-6">
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
                        <form className="flex flex-col gap-6" onSubmit={submit}>
                            <div className="grid gap-6">
                                <div className="grid gap-2">
                                    <Label htmlFor="username">Username</Label>
                                    <Input
                                        id="username"
                                        type="text"
                                        required
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="username"
                                        value={data.username}
                                        onChange={(e) => setData('username', e.target.value)}
                                        placeholder="Username"
                                    />
                                    <InputError message={errors.username} />
                                </div>
                                <div className="grid gap-2">
                                    <div className="flex items-center">
                                        <Label htmlFor="password">Password</Label>
                                    </div>
                                    <Input
                                        id="password"
                                        type="password"
                                        required
                                        tabIndex={2}
                                        autoComplete="current-password"
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        placeholder="Password"
                                    />
                                    <InputError message={errors.password} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="tahun">Tahun</Label>
                                    <Select
                                        name='tahun'
                                        value={tahun_default}
                                        onValueChange={(e) => setData('tahun_active', e)}
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
                                    <InputError message={errors.tahun_active} />
                                </div>



                                <Button type="submit" className="mt-4 w-full" tabIndex={4} disabled={processing}>
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

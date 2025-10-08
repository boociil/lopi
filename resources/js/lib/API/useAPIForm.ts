import { useApi } from '@/lib/API/useApi';
import { FormDataConvertible } from '@inertiajs/core';
import { InertiaFormProps, useForm } from '@inertiajs/react';
import { AxiosProgressEvent } from 'axios';
import { cloneDeep } from 'lodash-es'

const api = useApi();

interface FormOptions {
    onBefore?: () => void;
    onStart?: () => void;
    onProgress?: (event: AxiosProgressEvent) => void;
    onSuccess?: (data: any) => void;
    onError?: (error: {
        status: number;
    }) => void;
    onFinish?: () => void;
}

export type FormDataType = Record<string, FormDataConvertible>;
export type InertiaForm<TForm extends FormDataType> = TForm & InertiaFormProps<TForm>;

export function useApiForm<TForm extends FormDataType>(initialData?: TForm | (() => TForm)): InertiaForm<TForm> {
    const form = useForm(initialData || {});

    let transform = (data: TForm) => data;
    let recentlySuccessfulTimeoutId: ReturnType<typeof setTimeout> | null = null;

    const overriders = {
        transform: (receiver: any) => (callback: (data: TForm) => any) => {
            transform = callback;
            return receiver;
        },
        submit:
            () =>
            (method: string, url: string, options: FormOptions = {}) => {
                form.wasSuccessful = false;
                form.recentlySuccessful = false;
                form.clearErrors();
                if (recentlySuccessfulTimeoutId) clearTimeout(recentlySuccessfulTimeoutId);
                if (options.onBefore) {
                    options.onBefore();
                }

                form.processing = true;
                if (options.onStart) {
                    options.onStart();
                }

                const data = transform(form.data as any);
                // @ts-expect-error Error in accessing method using square brackets
                api[method](url, data, {
                    headers: {
                        'Content-Type': hasFiles(data) ? 'multipart/form-data' : 'application/json',
                    },
                    onUploadProgress: (event: any) => {
                        form.progress = event;
                        if (options.onProgress) {
                            options.onProgress(event);
                        }
                    },
                })
                    .then((response: any) => {
                        form.processing = false;
                        form.progress = null;
                        form.clearErrors();
                        form.wasSuccessful = true;
                        form.recentlySuccessful = true;
                        recentlySuccessfulTimeoutId = setTimeout(() => (form.recentlySuccessful = false), 2000);

                        if (options.onSuccess) {
                            options.onSuccess(response.data);
                        }

                        form.setDefaults(cloneDeep(form.data));
                        form.isDirty = false;
                    })
                    .catch((error: any) => {
                        form.processing = false;
                        form.progress = null;

                        form.clearErrors();
                        if (error.response?.status === 422) {
                            Object.keys(error.response.data.errors).forEach((key) => {
                                // @ts-expect-error types mismatch
                                form.setError(key, error.response.data.errors[key][0]);
                            });
                        }

                        if (options.onError) {
                            options.onError(error);
                        }
                    })
                    .finally(() => {
                        form.processing = false;
                        form.progress = null;

                        if (options.onFinish) {
                            options.onFinish();
                        }
                    });
            },
    };

    return new Proxy(form, {
        get: (target, prop, receiver) => {
            if (Object.keys(overriders).indexOf(prop.toString()) < 0) {
                // @ts-expect-error Using prop
                return target[prop];
            }

            return overriders[prop as keyof typeof overriders](receiver);
        },
    }) as any;
}

function hasFiles(data: any): boolean {
    return data instanceof FormData;
}

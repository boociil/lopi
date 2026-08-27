import { useState, useEffect } from 'react';

interface StorageItem<T> {
    value: T;
    expiry: number;
}


export function useLocalStorageWithTimeout<T>(
    key: string,
    initialValue: T,
    ttlInMs: number
): [T, (value: T) => void] {

    const [value, setValue] = useState<T>(() => {
        try {
            const itemStr = localStorage.getItem(key);
            if (!itemStr) return initialValue;

            const item: StorageItem<T> = JSON.parse(itemStr);
            const now = new Date();

            if (now.getTime() > item.expiry) {
                localStorage.removeItem(key);
                return initialValue;
            }
            return item.value;
        } catch (error) {
            console.error('Error reading localStorage key:', key, error);
            return initialValue;
        }
    });

    useEffect(() => {
        try {
            const now = new Date();
            const item: StorageItem<T> = {
                value: value,
                expiry: now.getTime() + ttlInMs,
            };
            localStorage.setItem(key, JSON.stringify(item));
        } catch (error) {
            console.error('Error setting localStorage key:', key, error);
        }
    }, [key, value, ttlInMs]);

    return [value, setValue];
}

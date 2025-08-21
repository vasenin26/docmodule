import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

const storages: {[key: string]: any} = {};

export function getStupidStore(key: string) {
    console.log('getStupidStore', key);

    if (!storages[key]) {
        storages[key] = {};
    }

    return storages[key];
}



export function formatDate(dateString: string){
    return new Date(dateString).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

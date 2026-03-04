import { InertiaLinkProps } from '@inertiajs/react';
import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function isSameUrl(
    url1: NonNullable<InertiaLinkProps['href']>,
    url2: NonNullable<InertiaLinkProps['href']>,
) {
    return resolveUrl(url1) === resolveUrl(url2);
}

export function resolveUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

export function formatCurrency(value: any, currency: string = 'USD') {
    const val = typeof value === 'string' ? parseFloat(value) : value;
    if (val === null || val === undefined || isNaN(val)) return '--';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
    }).format(val);
}

export function formatNumber(value: any, options: Intl.NumberFormatOptions = {}) {
    const val = typeof value === 'string' ? parseFloat(value) : value;
    if (val === null || val === undefined || isNaN(val)) return '--';
    return new Intl.NumberFormat('en-US', options).format(val);
}

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
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(val);
}

export function formatMoney(
    value: any,
    currency: string = 'USD',
    language: string = 'vi',
) {
    const val = typeof value === 'string' ? parseFloat(value) : value;
    if (val === null || val === undefined || isNaN(val)) return '-';

    const normalizedCurrency = (currency || 'USD').toUpperCase();
    const isVietnamese = language.toLowerCase().startsWith('vi');
    const numberOptions: Intl.NumberFormatOptions = {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    };

    if (isVietnamese) {
        return `${new Intl.NumberFormat('vi-VN', numberOptions).format(val)} ${normalizedCurrency}`;
    }

    const symbols: Record<string, string> = {
        USD: '$',
        USDT: '$',
        VND: 'đ',
    };
    const suffix = symbols[normalizedCurrency] ?? ` ${normalizedCurrency}`;

    return `${new Intl.NumberFormat('en-US', numberOptions).format(val)}${suffix}`;
}

export function formatNumber(
    value: any,
    options: Intl.NumberFormatOptions = {},
) {
    const val = typeof value === 'string' ? parseFloat(value) : value;
    if (val === null || val === undefined || isNaN(val)) return '--';
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
        ...options,
    }).format(val);
}

export function formatDateForQuery(date?: Date | null): string | undefined {
    if (!date) {
        return undefined;
    }

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

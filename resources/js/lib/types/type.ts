import { JSX } from 'react';
import { _UserRole } from '@/lib/types/constants';

export interface IBreadcrumbItem {
    title: string;
    href?: string;
}

export interface IUser {
    id: number;
    username: string;
    phone: string | null;
    disabled: boolean;
    referral_code: string;
    role: _UserRole;
    created_at: string;
    updated_at: string;
    deleted_at: string;
}
export interface IMenu {
    title: string;
    is_menu?: boolean;
    url?: string;
    icon?: JSX.Element;
    active?: boolean;
    can_show?: boolean;
    items?: { title: string; url: string; active?: boolean; can_show?: boolean }[];
}

export type BaseSearchRequest<TFilter> = {
    filter: TFilter;
    sort_by?: string;
    direction?: 'asc' | 'desc';
}

type PaginatorLink = {
    url: string | null;
    label: string;
    active: boolean;
    page: number | null;
}
export type LaravelPaginator<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        next: string | null;
        prev: string | null;
    };
    meta: {
        links: PaginatorLink[];
        current_page: number;
        from: number;
        last_page: number;
        per_page: number;
        to: number;
        total: number;
    }
}

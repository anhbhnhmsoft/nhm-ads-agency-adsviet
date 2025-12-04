import { JSX } from 'react';
import { _UserRole, _PlatformType } from '@/lib/types/constants';

export interface IBreadcrumbItem {
    title: string;
    href?: string;
}

export interface IUser {
    id: string;
    name: string;
    username: string;
    email?: string | null;
    email_verified_at?: string | null;
    phone: string | null;
    telegram_id?: string | null;
    whatsapp_id?: string | null;
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

export type PlatformSetting = {
    id: number;
    platform: _PlatformType;
    config: Record<string, any>;
    disabled: boolean;
}

export type PlatformSettingListPagination = LaravelPaginator<PlatformSetting>;

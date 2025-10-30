import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';


export interface BreadcrumbItem {
    title: string;
    href: string;
}
export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface ISharedData {
    name: string;
    auth: {
        user: IUser;
    };
    flash: {
        success: string | null;
        error: string | null;
        warning: string | null;
        info: string | null;
    };
    current_route: string;
    sidebarOpen: boolean;
}

export interface IUser {
    id: number;
    username: string;
    email: string;
    created_at: string;
    updated_at: string;
    deleted_at: string;
}

// ------------------------------------------------------------
// 1. Mở rộng PageProps của Inertia
declare module '@inertiajs/core' {
    // Tất cả usePage().props đều có ISharedData
    type PageProps = ISharedData;
}

// ------------------------------------------------------------
// 2. Định nghĩa kiểu cho Page Props tổng thể (bao gồm cả Page-Specific Props)
// Tận dụng cấu trúc Generic của PageProps<T>
export type InertiaPageProps<T extends Record<string, unknown> = Record<string, unknown>> = PageProps<T>;

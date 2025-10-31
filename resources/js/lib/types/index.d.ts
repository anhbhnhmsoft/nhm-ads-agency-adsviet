import { IUser } from '@/lib/types/type';

export interface ISharedData {
    name: string;
    auth: IUser | null;
    flash: {
        success: string | null;
        error: string | null;
        warning: string | null;
        info: string | null;
    };
    logo_path: string;
    current_route: string;
    sidebarOpen: boolean;
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

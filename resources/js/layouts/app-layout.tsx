import { type ReactNode, useEffect } from 'react';
import { AppSidebar } from '@/components/layout/app-sidebar';
import { AppContent } from '@/components/app-content';
import { AppSidebarHeader } from '@/components/layout/app-sidebar-header';
import { AppShell } from '@/components/layout/app-shell';
import { ThemeProvider } from '@/components/theme-provider';
import { usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import { Toaster } from '@/components/ui/sonner';
import LoadingGlobal from '@/components/loading-global';
import { IBreadcrumbItem } from '@/lib/types/type';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: IBreadcrumbItem[];
}

export default ({ children, breadcrumbs}: AppLayoutProps) => {
    const { flash, logo_path } = usePage().props;
    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success, { duration: 3000 });
        }
        if (flash?.error) {
            toast.error(flash.error, { duration: 3000 });
        }
        if (flash?.info) {
            toast(flash.info, { duration: 3000 });
        }
        if (flash?.warning) {
            toast.warning(flash.warning, { duration: 3000 });
        }
    }, [flash]);

    return(
        <ThemeProvider defaultTheme={"light"}>
            <AppShell variant="sidebar">
                <AppSidebar />
                <AppContent variant="sidebar" className="overflow-x-hidden">
                    <AppSidebarHeader breadcrumbs={breadcrumbs} />
                    <main className="flex flex-1 flex-col gap-4 p-4">{children}</main>
                </AppContent>
            </AppShell>
            <Toaster expand visibleToasts={3} position="top-right" />
            <LoadingGlobal />
        </ThemeProvider>
    );
}

import { type BreadcrumbItem } from '@/lib/types';
import { type ReactNode, useEffect } from 'react';
import { AppSidebar } from '@/components/layout/app-sidebar';
import { AppContent } from '@/components/app-content';
import { AppSidebarHeader } from '@/components/layout/app-sidebar-header';
import { AppShell } from '@/components/layout/app-shell';
import { ThemeProvider } from '@/components/theme-provider';
import { usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import LoadingGlobal from '@/components/loading-global';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

export default ({ children, breadcrumbs}: AppLayoutProps) => {
    const { flash } = usePage().props;

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
                    {children}
                </AppContent>
            </AppShell>
            <LoadingGlobal />
        </ThemeProvider>
    );
}

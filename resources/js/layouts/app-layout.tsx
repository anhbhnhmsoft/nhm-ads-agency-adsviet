import { type BreadcrumbItem } from '@/lib/types';
import { type ReactNode } from 'react';
import { AppSidebar } from '@/components/layout/app-sidebar';
import { AppContent } from '@/components/app-content';
import { AppSidebarHeader } from '@/components/layout/app-sidebar-header';
import { AppShell } from '@/components/layout/app-shell';
import { ThemeProvider } from '@/components/theme-provider';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

export default ({ children, breadcrumbs}: AppLayoutProps) => (
    <ThemeProvider defaultTheme={"light"}>
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
        </AppShell>
    </ThemeProvider>

);

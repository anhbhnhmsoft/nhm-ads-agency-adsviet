import { NavMain } from '@/components/layout/nav-main';
import { NavUser } from '@/components/layout/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
} from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/react';

export function AppSidebar() {
    const { logo_path } = usePage().props as { logo_path?: string };
    return (
        <Sidebar collapsible="icon" variant="inset">
            {/*Header sidebar*/}
            <SidebarHeader className="items-center overflow-hidden">
                <div className="flex w-full items-center justify-center overflow-hidden px-2 py-4 group-data-[collapsible=icon]:px-0 group-data-[collapsible=icon]:py-2">
                    <img
                        src={`${logo_path}`}
                        alt="Adviet Agency"
                        className="h-24 w-auto max-w-[9.5rem] object-contain transition-all duration-200 ease-linear group-data-[collapsible=icon]:h-10 group-data-[collapsible=icon]:max-w-10"
                    />
                </div>
                <div className="w-full px-2 pb-2 text-center text-sm font-bold tracking-[0.2em] text-sidebar-foreground group-data-[collapsible=icon]:hidden">
                    <span className="block truncate">ADVIET</span>
                    <span className="block truncate">AGENCY</span>
                </div>
            </SidebarHeader>

            {/*Menu sidebar*/}
            <SidebarContent>
                <NavMain />
            </SidebarContent>

            {/*Footer sidebar*/}
            <SidebarFooter>
                {/* Hoàn thiện sau */}
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

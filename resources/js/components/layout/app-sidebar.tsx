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
            <SidebarHeader>
                <div className="flex items-center justify-center">
                    <img src={`${logo_path}`} alt="logo" className="w-15 h-15" />
                </div>
                <div className="px-3 py-3 text-lg font-bold tracking-wide text-center">
                    ADVIET AGENCY
                </div>
            </SidebarHeader>

            {/*Menu sidebar*/}
            <SidebarContent>
                <NavMain  />
            </SidebarContent>

            {/*Footer sidebar*/}
            <SidebarFooter>
                {/* Hoàn thiện sau */}
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

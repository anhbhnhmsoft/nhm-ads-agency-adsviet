import { NavMain } from '@/components/layout/nav-main';
import { NavUser } from '@/components/layout/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
} from '@/components/ui/sidebar';

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            {/*Header sidebar*/}
            <SidebarHeader>
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

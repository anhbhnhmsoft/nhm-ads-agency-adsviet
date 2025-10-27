import { NavMain } from '@/components/layout/nav-main';
import { NavUser } from '@/components/layout/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/lib/types';
import { LayoutGrid } from 'lucide-react';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            {/*Header sidebar*/}
            <SidebarHeader>{/* Hoàn thiện sau */}</SidebarHeader>

            {/*Menu sidebar*/}
            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            {/*Footer sidebar*/}
            <SidebarFooter>
                {/* Hoàn thiện sau */}
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

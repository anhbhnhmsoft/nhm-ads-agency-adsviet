import { UserInfo } from '@/components/layout/user-info';
import { UserMenuContent } from '@/components/layout/user-menu-content';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useIsMobile } from '@/hooks/use-mobile';
import { IPreviewContext, IUser } from '@/lib/types/type';
import { usePage } from '@inertiajs/react';
import { ChevronsUpDown } from 'lucide-react';

export function NavUser() {
    const { auth, auth_actor, preview_context } = usePage().props as {
        auth: IUser | null;
        auth_actor?: IUser | null;
        preview_context?: IPreviewContext | null;
    };
    const { state } = useSidebar();
    const isMobile = useIsMobile();
    const displayUser = preview_context?.is_applied
        ? auth
        : preview_context?.is_active && auth_actor
          ? auth_actor
          : auth;

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="group text-sidebar-foreground hover:bg-sidebar-accent hover:text-white data-[state=open]:bg-sidebar-accent data-[state=open]:text-white"
                            data-test="sidebar-menu-button"
                        >
                            <UserInfo user={displayUser} />
                            <ChevronsUpDown className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="end"
                        side={
                            isMobile
                                ? 'bottom'
                                : state === 'collapsed'
                                  ? 'left'
                                  : 'bottom'
                        }
                    >
                        <UserMenuContent user={displayUser} />
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}

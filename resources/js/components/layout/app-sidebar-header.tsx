import { SidebarTrigger } from '@/components/ui/sidebar';
import { type IBreadcrumbItem as BreadcrumbItemType } from '@/lib/types/type';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator
} from '@/components/ui/breadcrumb';
import { Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Separator } from '@/components/ui/separator';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const {t} = useTranslation();
    return (
        <header className="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Separator orientation="vertical" className="mr-2 data-[orientation=vertical]:h-4" />
                <Breadcrumb>
                    <BreadcrumbList>
                        {breadcrumbs.map((breadcrumb, index) => (
                            <div className="inline-flex items-center gap-2" key={index}>
                                <BreadcrumbItem>
                                    {!breadcrumb.href ? (
                                        <BreadcrumbPage>{t(breadcrumb.title)}</BreadcrumbPage>
                                    ) : (
                                        <BreadcrumbPage className={"hover:opacity-60"}>
                                            <Link href={breadcrumb.href}>{t(breadcrumb.title)}</Link>
                                        </BreadcrumbPage>
                                    )}
                                </BreadcrumbItem>
                                {index < breadcrumbs.length - 1 && <BreadcrumbSeparator className="hidden md:block" />}
                            </div>
                        ))}
                    </BreadcrumbList>
                </Breadcrumb>
            </div>
        </header>
    );
}

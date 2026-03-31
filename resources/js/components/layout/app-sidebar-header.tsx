import { SidebarTrigger } from '@/components/ui/sidebar';
import { type IBreadcrumbItem as BreadcrumbItemType } from '@/lib/types/type';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator
} from '@/components/ui/breadcrumb';
import { Link, router, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Separator } from '@/components/ui/separator';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useTransition } from 'react';
import { _PlatformType } from '@/lib/types/constants';
import { platform_settings_switch } from '@/routes';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const {t} = useTranslation();
    const { locale, locales, meta_settings, google_settings } = usePage().props as {
        locale?: string;
        locales?: { code: string; label: string }[];
        meta_settings?: { current_id: string; list: { id: string; name: string }[] };
        google_settings?: { current_id: string; list: { id: string; name: string }[] };
    };
    const [isPending, startTransition] = useTransition();

    const handleLocaleChange = (value: string) => {
        if (value === locale) {
            return;
        }
        startTransition(() => {
            router.post(
                '/locale',
                { locale: value },
                {
                    preserveScroll: true,
                    // Không preserve state để Inertia reload lại props (bao gồm locale & dữ liệu backend)
                    preserveState: false,
                },
            );
        });
    };

    const handleMetaChange = (id: string) => {
        if (id === meta_settings?.current_id) return;
        router.post(platform_settings_switch.url(), { platform: _PlatformType.META, id }, { preserveState: false });
    };

    const handleGoogleChange = (id: string) => {
        if (id === google_settings?.current_id) return;
        router.post(platform_settings_switch.url(), { platform: _PlatformType.GOOGLE, id }, { preserveState: false });
    };

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
            <div className="ml-auto flex items-center gap-4">
                {meta_settings && meta_settings.list.length > 0 && (
                    <Select value={meta_settings.current_id ?? ''} onValueChange={handleMetaChange}>
                        <SelectTrigger className="w-[180px] bg-blue-50/50 border-blue-200 text-blue-700 font-medium">
                            <SelectValue placeholder="Select Meta BM" />
                        </SelectTrigger>
                        <SelectContent>
                            {meta_settings.list.map((item) => (
                                <SelectItem key={item.id} value={item.id}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}

                {google_settings && google_settings.list.length > 0 && (
                    <Select value={google_settings.current_id ?? ''} onValueChange={handleGoogleChange}>
                        <SelectTrigger className="w-[180px] bg-green-50/50 border-green-200 text-green-700 font-medium">
                            <SelectValue placeholder="Select Google MCC" />
                        </SelectTrigger>
                        <SelectContent>
                            {google_settings.list.map((item) => (
                                <SelectItem key={item.id} value={item.id}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}

                <div className="h-4 w-px bg-sidebar-border/50" />

                <Select value={locale ?? 'vi'} onValueChange={handleLocaleChange} disabled={isPending}>
                    <SelectTrigger className="w-[140px]">
                        <SelectValue placeholder="Language" />
                    </SelectTrigger>
                    <SelectContent>
                        {(locales ?? []).map((item) => (
                            <SelectItem key={item.code} value={item.code}>
                                {item.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
        </header>
    );
}

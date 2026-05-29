import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { _PlatformType } from '@/lib/types/constants';
import { type IBreadcrumbItem as BreadcrumbItemType } from '@/lib/types/type';
import { platform_settings_switch } from '@/routes';
import { Link, router, usePage } from '@inertiajs/react';
import { useTransition } from 'react';
import { useTranslation } from 'react-i18next';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { t } = useTranslation();
    const { locale, locales, meta_settings, google_settings } = usePage()
        .props as {
        locale?: string;
        locales?: { code: string; label: string }[];
        meta_settings?: {
            current_id: string;
            list: { id: string; name: string }[];
        };
        google_settings?: {
            current_id: string;
            list: { id: string; name: string }[];
        };
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
        router.post(
            platform_settings_switch.url(),
            { platform: _PlatformType.META, id },
            { preserveState: false },
        );
    };

    const handleGoogleChange = (id: string) => {
        if (id === google_settings?.current_id) return;
        router.post(
            platform_settings_switch.url(),
            { platform: _PlatformType.GOOGLE, id },
            { preserveState: false },
        );
    };

    const getSettingName = (name: string, prefix: 'BM' | 'MCC') =>
        name.replace(new RegExp(`^${prefix}\\s*-\\s*`, 'i'), '').trim();
    const selectedMetaSetting = meta_settings?.list.find(
        (item) => item.id === meta_settings.current_id,
    );
    const selectedGoogleSetting = google_settings?.list.find(
        (item) => item.id === google_settings.current_id,
    );

    return (
        <header className="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Separator
                    orientation="vertical"
                    className="mr-2 data-[orientation=vertical]:h-4"
                />
                <Breadcrumb>
                    <BreadcrumbList>
                        {breadcrumbs.map((breadcrumb, index) => (
                            <div
                                className="inline-flex items-center gap-2"
                                key={index}
                            >
                                <BreadcrumbItem>
                                    {!breadcrumb.href ? (
                                        <BreadcrumbPage>
                                            {t(breadcrumb.title)}
                                        </BreadcrumbPage>
                                    ) : (
                                        <BreadcrumbPage
                                            className={'hover:opacity-60'}
                                        >
                                            <Link href={breadcrumb.href}>
                                                {t(breadcrumb.title)}
                                            </Link>
                                        </BreadcrumbPage>
                                    )}
                                </BreadcrumbItem>
                                {index < breadcrumbs.length - 1 && (
                                    <BreadcrumbSeparator className="hidden md:block" />
                                )}
                            </div>
                        ))}
                    </BreadcrumbList>
                </Breadcrumb>
            </div>
            <div className="ml-auto flex items-center gap-4">
                {meta_settings && meta_settings.list.length > 0 && (
                    <Select
                        value={meta_settings.current_id ?? ''}
                        onValueChange={handleMetaChange}
                    >
                        <SelectTrigger
                            title={selectedMetaSetting?.name}
                            className="h-10 w-[210px] min-w-0 gap-2 border-blue-200 bg-blue-50/60 px-3 text-left text-blue-700"
                        >
                            <span className="flex min-w-0 flex-1 items-center gap-2">
                                <span className="shrink-0 rounded bg-blue-100 px-1.5 py-0.5 text-[10px] leading-none font-semibold text-blue-700">
                                    BM
                                </span>
                                <span className="min-w-0 flex-1 truncate text-sm font-medium">
                                    {selectedMetaSetting
                                        ? getSettingName(
                                              selectedMetaSetting.name,
                                              'BM',
                                          )
                                        : 'Select Meta BM'}
                                </span>
                            </span>
                        </SelectTrigger>
                        <SelectContent>
                            {meta_settings.list.map((item) => (
                                <SelectItem key={item.id} value={item.id}>
                                    <span
                                        className="block max-w-[280px] truncate"
                                        title={item.name}
                                    >
                                        {getSettingName(item.name, 'BM')}
                                    </span>
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}

                {google_settings && google_settings.list.length > 0 && (
                    <Select
                        value={google_settings.current_id ?? ''}
                        onValueChange={handleGoogleChange}
                    >
                        <SelectTrigger
                            title={selectedGoogleSetting?.name}
                            className="h-10 w-[210px] min-w-0 gap-2 border-green-200 bg-green-50/60 px-3 text-left text-green-700"
                        >
                            <span className="flex min-w-0 flex-1 items-center gap-2">
                                <span className="shrink-0 rounded bg-green-100 px-1.5 py-0.5 text-[10px] leading-none font-semibold text-green-700">
                                    MCC
                                </span>
                                <span className="min-w-0 flex-1 truncate text-sm font-medium">
                                    {selectedGoogleSetting
                                        ? getSettingName(
                                              selectedGoogleSetting.name,
                                              'MCC',
                                          )
                                        : 'Select Google MCC'}
                                </span>
                            </span>
                        </SelectTrigger>
                        <SelectContent>
                            {google_settings.list.map((item) => (
                                <SelectItem key={item.id} value={item.id}>
                                    <span
                                        className="block max-w-[280px] truncate"
                                        title={item.name}
                                    >
                                        {getSettingName(item.name, 'MCC')}
                                    </span>
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}

                <div className="h-4 w-px bg-sidebar-border/50" />

                <Select
                    value={locale ?? 'en'}
                    onValueChange={handleLocaleChange}
                    disabled={isPending}
                >
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

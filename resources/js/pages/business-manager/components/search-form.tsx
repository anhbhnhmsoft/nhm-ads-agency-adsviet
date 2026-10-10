import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { DateRangePicker } from '@/components/ui/date-range-picker';
import { Field, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { InertiaPageProps } from '@/lib/types';
import type { _PlatformType as PlatformTypeEnum } from '@/lib/types/constants';
import { _PlatformType, _UserRole } from '@/lib/types/constants';
import { cn, formatDateForQuery } from '@/lib/utils';
import { usePage } from '@inertiajs/react';
import { ChevronDown, Download, Loader2, RotateCcw, Search } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { DateRange } from 'react-day-picker';
import { useTranslation } from 'react-i18next';

type ChildManagerOption = {
    id: string;
    name: string;
    parent_id: string;
    platform?: PlatformTypeEnum;
};

type CustomerOption = {
    id: string;
    name: string;
};

type PageProps = {
    childManagers?: {
        meta?: ChildManagerOption[];
        google?: ChildManagerOption[];
    };
    customers?: CustomerOption[];
};

type SearchQuery = {
    keyword?: string;
    manager_id?: string;
    platform?: PlatformTypeEnum;
    start_date?: string;
    end_date?: string;
    child_manager_id?: string;
    customer_id?: string;
    has_spend?: string;
};

type Props = {
    query: SearchQuery;
    setQuery: (query: Partial<SearchQuery>) => void;
    handleSearch: () => void;
    handleReset?: () => void;
    handleExport?: () => void;
    exportLoading?: boolean;
};

const BusinessManagerSearchForm = ({
    query,
    setQuery,
    handleSearch,
    handleReset,
    handleExport,
    exportLoading,
}: Props) => {
    const { t } = useTranslation();
    const { props } = usePage<InertiaPageProps<PageProps>>();

    const childManagers = props.childManagers;
    const customers = props.customers ?? [];

    const currentUserRole = Number(
        (props as any)?.auth?.role ??
            (props as any)?.auth?.user?.role ??
            (props as any)?.auth?.role_id ??
            (props as any)?.auth?.user?.role_id,
    );
    const isCustomerOrAgency =
        currentUserRole === _UserRole.CUSTOMER ||
        currentUserRole === _UserRole.AGENCY;

    const platformValue = query.platform
        ? query.platform.toString()
        : undefined;

    const platformChildOptions: ChildManagerOption[] = useMemo(() => {
        if (!childManagers) {
            return [];
        }

        const metaOptions = (childManagers.meta ?? []).map((item) => ({
            ...item,
            platform: _PlatformType.META as PlatformTypeEnum,
        }));
        const googleOptions = (childManagers.google ?? []).map((item) => ({
            ...item,
            platform: _PlatformType.GOOGLE as PlatformTypeEnum,
        }));

        if (query.platform === _PlatformType.META) {
            return metaOptions;
        }
        if (query.platform === _PlatformType.GOOGLE) {
            return googleOptions;
        }
        return [...metaOptions, ...googleOptions];
    }, [childManagers, query.platform]);

    useEffect(() => {
        if (
            query.child_manager_id ||
            !query.manager_id ||
            platformChildOptions.length === 0
        ) {
            return;
        }

        const matchedChild = platformChildOptions.find(
            (item) => item.id === query.manager_id,
        );
        if (matchedChild) {
            setQuery({
                child_manager_id: matchedChild.id,
                platform: query.platform ?? matchedChild.platform,
            });
        }
    }, [
        query.child_manager_id,
        query.manager_id,
        platformChildOptions,
        setQuery,
    ]);

    const [childManagerOpen, setChildManagerOpen] = useState(false);
    const [childManagerSearch, setChildManagerSearch] = useState('');

    useEffect(() => {
        if (!childManagerOpen) {
            setChildManagerSearch('');
        }
    }, [childManagerOpen]);

    const selectedChild = useMemo(() => {
        if (!query.child_manager_id) return null;
        return platformChildOptions.find(
            (item) => item.id === query.child_manager_id,
        );
    }, [platformChildOptions, query.child_manager_id]);

    const filteredChildOptions = useMemo(() => {
        if (!childManagerSearch.trim()) {
            return platformChildOptions;
        }
        const term = childManagerSearch.toLowerCase().trim();
        return platformChildOptions.filter(
            (item) =>
                item.name.toLowerCase().includes(term) ||
                item.id.toLowerCase().includes(term),
        );
    }, [platformChildOptions, childManagerSearch]);

    const dateRange: DateRange | undefined = useMemo(() => {
        if (query.start_date && query.end_date) {
            return {
                from: new Date(query.start_date),
                to: new Date(query.end_date),
            };
        }
        return undefined;
    }, [query.start_date, query.end_date]);

    const handleDateRangeChange = (date: DateRange | undefined) => {
        if (date?.from && date?.to) {
            setQuery({
                start_date: formatDateForQuery(date.from),
                end_date: formatDateForQuery(date.to),
            });
        } else {
            setQuery({
                start_date: undefined,
                end_date: undefined,
            });
        }
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('common.search')}</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Field>
                        <FieldLabel htmlFor="keyword">
                            {t('common.keyword')}
                        </FieldLabel>
                        <Input
                            id="keyword"
                            autoComplete="off"
                            placeholder={t('common.keyword')}
                            value={query.keyword || ''}
                            onChange={(e) => {
                                setQuery({ keyword: e.target.value });
                            }}
                        />
                    </Field>
                    {!isCustomerOrAgency && customers.length > 0 && (
                        <Field>
                            <FieldLabel htmlFor="customer">
                                {t('service_management.customer', { defaultValue: 'Khách hàng' })}
                            </FieldLabel>
                            <Select
                                value={query.customer_id || 'all'}
                                onValueChange={(value) => {
                                    setQuery({
                                        customer_id:
                                            value === 'all' ? undefined : value,
                                    });
                                }}
                            >
                                <SelectTrigger id="customer">
                                    <SelectValue
                                        placeholder={t('common.all', {
                                            defaultValue: 'Tất cả',
                                        })}
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        {t('common.all', { defaultValue: 'Tất cả' })}
                                    </SelectItem>
                                    {customers.map((c) => (
                                        <SelectItem key={c.id} value={c.id}>
                                            {c.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </Field>
                    )}
                    <Field>
                        <FieldLabel htmlFor="platform">
                            {t('common.platform', { defaultValue: 'Nền tảng' })}
                        </FieldLabel>
                        <Select
                            value={platformValue || 'all'}
                            onValueChange={(value) => {
                                setQuery({
                                    platform:
                                        value && value !== 'all'
                                            ? (Number(value) as PlatformTypeEnum)
                                            : undefined,
                                    child_manager_id: undefined,
                                });
                            }}
                        >
                            <SelectTrigger id="platform">
                                <SelectValue
                                    placeholder={t('common.all', {
                                        defaultValue: 'Tất cả',
                                    })}
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    {t('common.all', { defaultValue: 'Tất cả' })}
                                </SelectItem>
                                <SelectItem
                                    value={_PlatformType.META.toString()}
                                >
                                    {t('enum.PlatformType.META', {
                                        defaultValue: 'Meta Ads',
                                    })}
                                </SelectItem>
                                <SelectItem
                                    value={_PlatformType.GOOGLE.toString()}
                                >
                                    {t('enum.PlatformType.GOOGLE', {
                                        defaultValue: 'Google Ads',
                                    })}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </Field>
                    {!isCustomerOrAgency && (
                        <Field>
                            <FieldLabel htmlFor="child-manager">
                                {t('business_manager.filter.child_manager', {
                                    defaultValue: 'BM/MCC con',
                                })}
                            </FieldLabel>
                            <Popover
                                open={childManagerOpen}
                                onOpenChange={setChildManagerOpen}
                            >
                                <PopoverTrigger asChild>
                                    <button
                                        type="button"
                                        id="child-manager"
                                        role="combobox"
                                        aria-expanded={childManagerOpen}
                                        disabled={platformChildOptions.length === 0}
                                        className={cn(
                                            'border-input data-[placeholder]:text-muted-foreground [&_svg:not([class*="text-"])]:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive flex h-9 w-full items-center justify-between rounded-md border bg-white px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 cursor-pointer',
                                            !selectedChild && 'text-muted-foreground',
                                        )}
                                    >
                                        <span className="truncate text-left">
                                            {selectedChild
                                                ? `${selectedChild.name} (${selectedChild.id})`
                                                : platformChildOptions.length > 0
                                                  ? t('common.all', { defaultValue: 'Tất cả' })
                                                  : t(
                                                        'business_manager.filter.child_manager_disabled',
                                                        { defaultValue: 'Chưa có BM/MCC con' },
                                                    )}
                                        </span>
                                        <ChevronDown className="h-4 w-4 shrink-0 opacity-50" />
                                    </button>
                                </PopoverTrigger>
                                <PopoverContent
                                    className="w-[var(--radix-popover-trigger-width)] min-w-[320px] p-0"
                                    align="start"
                                >
                                    <div className="flex items-center border-b px-3">
                                        <Search className="mr-2 h-4 w-4 shrink-0 opacity-50" />
                                        <Input
                                            value={childManagerSearch}
                                            onChange={(e) => setChildManagerSearch(e.target.value)}
                                            placeholder={t('common.search', {
                                                defaultValue: 'Tìm kiếm BM/MCC...',
                                            })}
                                            className="flex h-9 w-full rounded-md border-none bg-transparent px-0 py-2 text-sm outline-none focus-visible:ring-0 focus-visible:ring-offset-0"
                                            autoFocus
                                        />
                                    </div>
                                    <div className="max-h-[260px] overflow-y-auto p-1">
                                        {(!childManagerSearch.trim() ||
                                            'tất cả'.includes(childManagerSearch.toLowerCase()) ||
                                            'all'.includes(childManagerSearch.toLowerCase())) && (
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    setQuery({ child_manager_id: undefined });
                                                    setChildManagerOpen(false);
                                                }}
                                                className={cn(
                                                    'relative flex w-full cursor-pointer items-center rounded-sm px-2 py-1.5 text-left text-sm transition-colors outline-none hover:bg-accent hover:text-accent-foreground',
                                                    !query.child_manager_id &&
                                                        'bg-accent font-medium text-accent-foreground',
                                                )}
                                            >
                                                <span className="truncate">
                                                    {t('common.all', { defaultValue: 'Tất cả' })}
                                                </span>
                                            </button>
                                        )}
                                        {filteredChildOptions.length === 0 && childManagerSearch.trim() ? (
                                            <div className="py-4 text-center text-sm text-muted-foreground">
                                                {t('common.no_results', {
                                                    defaultValue: 'Không tìm thấy kết quả',
                                                })}
                                            </div>
                                        ) : (
                                            filteredChildOptions.map((item) => {
                                                const isSelected = query.child_manager_id === item.id;
                                                return (
                                                    <button
                                                        key={item.id}
                                                        type="button"
                                                        onClick={() => {
                                                            setQuery({
                                                                child_manager_id: item.id,
                                                                platform: query.platform ?? item.platform,
                                                            });
                                                            setChildManagerOpen(false);
                                                        }}
                                                        className={cn(
                                                            'relative flex w-full cursor-pointer items-center rounded-sm px-2 py-1.5 text-left text-sm transition-colors outline-none hover:bg-accent hover:text-accent-foreground',
                                                            isSelected &&
                                                                'bg-accent font-medium text-accent-foreground',
                                                        )}
                                                    >
                                                        <span className="truncate">
                                                            {item.name} ({item.id})
                                                        </span>
                                                    </button>
                                                );
                                            })
                                        )}
                                    </div>
                                </PopoverContent>
                            </Popover>
                        </Field>
                    )}
                    <Field>
                        <FieldLabel>
                            {t('business_manager.filter.period', {
                                defaultValue: 'Khoảng thời gian',
                            })}
                        </FieldLabel>
                        <DateRangePicker
                            className="w-full"
                            date={dateRange}
                            onDateChange={handleDateRangeChange}
                        />
                    </Field>
                    <Field>
                        <FieldLabel htmlFor="has_spend">
                            {t('service_management.filter.spend_status', {
                                defaultValue: 'Lọc chi tiêu',
                            })}
                        </FieldLabel>
                        <Select
                            value={query.has_spend || 'all'}
                            onValueChange={(value) => {
                                setQuery({
                                    has_spend:
                                        value === 'all' ? undefined : value,
                                });
                            }}
                        >
                            <SelectTrigger id="has_spend">
                                <SelectValue
                                    placeholder={t(
                                        'service_management.filter.all_spend',
                                        {
                                            defaultValue: 'Tất cả',
                                        },
                                    )}
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    {t('service_management.filter.all_spend', {
                                        defaultValue: 'Tất cả tài khoản',
                                    })}
                                </SelectItem>
                                <SelectItem value="has_spend">
                                    {t('service_management.filter.has_spend', {
                                        defaultValue: 'Có chi tiêu (> 0$)',
                                    })}
                                </SelectItem>
                                <SelectItem value="no_spend">
                                    {t('service_management.filter.no_spend', {
                                        defaultValue: 'Chưa chi tiêu (= 0$)',
                                    })}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </Field>
                </div>
            </CardContent>
            <CardFooter>
                <CardAction className="space-y-2 space-x-2">
                    <Button
                        className="cursor-pointer"
                        onClick={() => handleSearch()}
                    >
                        <Search />
                        {t('common.search', { defaultValue: 'Search' })}
                    </Button>
                    {handleReset && (
                        <Button
                            className="cursor-pointer"
                            variant="outline"
                            onClick={() => handleReset()}
                        >
                            <RotateCcw />
                            {t('common.reset', { defaultValue: 'Reset' })}
                        </Button>
                    )}
                    {handleExport && (
                        <Button
                            className="cursor-pointer"
                            variant="outline"
                            onClick={() => handleExport()}
                            disabled={exportLoading}
                        >
                            {exportLoading ? (
                                <Loader2 className="animate-spin" />
                            ) : (
                                <Download />
                            )}
                            {t('service_management.download', {
                                defaultValue: 'Tải xuống',
                            })}
                        </Button>
                    )}
                </CardAction>
            </CardFooter>
        </Card>
    );
};

export default BusinessManagerSearchForm;

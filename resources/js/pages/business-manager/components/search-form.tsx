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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { InertiaPageProps } from '@/lib/types';
import type { _PlatformType as PlatformTypeEnum } from '@/lib/types/constants';
import { _PlatformType } from '@/lib/types/constants';
import { formatDateForQuery } from '@/lib/utils';
import { usePage } from '@inertiajs/react';
import { RotateCcw, Search } from 'lucide-react';
import { useEffect, useMemo } from 'react';
import { DateRange } from 'react-day-picker';
import { useTranslation } from 'react-i18next';

type ChildManagerOption = {
    id: string;
    name: string;
    parent_id: string;
    platform?: PlatformTypeEnum;
};

type PageProps = {
    childManagers?: {
        meta?: ChildManagerOption[];
        google?: ChildManagerOption[];
    };
};

type SearchQuery = {
    keyword?: string;
    manager_id?: string;
    platform?: PlatformTypeEnum;
    start_date?: string;
    end_date?: string;
    child_manager_id?: string;
};

type Props = {
    query: SearchQuery;
    setQuery: (query: Partial<SearchQuery>) => void;
    handleSearch: () => void;
    handleReset?: () => void;
};

const BusinessManagerSearchForm = ({
    query,
    setQuery,
    handleSearch,
    handleReset,
}: Props) => {
    const { t } = useTranslation();
    const { props } = usePage<InertiaPageProps<PageProps>>();

    const childManagers = props.childManagers;

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
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
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
                    <Field>
                        <FieldLabel htmlFor="platform">
                            {t('common.platform', { defaultValue: 'Nền tảng' })}
                        </FieldLabel>
                        <Select
                            value={platformValue}
                            onValueChange={(value) => {
                                setQuery({
                                    platform: value
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
                    <Field>
                        <FieldLabel htmlFor="child-manager">
                            {t('business_manager.filter.child_manager', {
                                defaultValue: 'BM/MCC con',
                            })}
                        </FieldLabel>
                        <Select
                            value={query.child_manager_id || undefined}
                            onValueChange={(value) => {
                                const selectedChild = platformChildOptions.find(
                                    (item) => item.id === value,
                                );
                                setQuery({
                                    child_manager_id: value || undefined,
                                    platform:
                                        query.platform ??
                                        selectedChild?.platform,
                                });
                            }}
                            disabled={platformChildOptions.length === 0}
                        >
                            <SelectTrigger id="child-manager">
                                <SelectValue
                                    placeholder={
                                        platformChildOptions.length > 0
                                            ? t(
                                                  'business_manager.filter.child_manager_placeholder',
                                                  {
                                                      defaultValue:
                                                          'Chọn BM/MCC con',
                                                  },
                                              )
                                            : t(
                                                  'business_manager.filter.child_manager_disabled',
                                                  {
                                                      defaultValue:
                                                          'Chưa có BM/MCC con',
                                                  },
                                              )
                                    }
                                />
                            </SelectTrigger>
                            <SelectContent>
                                {platformChildOptions.map((item) => (
                                    <SelectItem key={item.id} value={item.id}>
                                        {item.name} ({item.id})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>
                    <Field>
                        <FieldLabel>
                            {t('business_manager.filter.period', {
                                defaultValue: 'Khoảng thời gian',
                            })}
                        </FieldLabel>
                        <DateRangePicker
                            date={dateRange}
                            onDateChange={handleDateRangeChange}
                        />
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
                </CardAction>
            </CardFooter>
        </Card>
    );
};

export default BusinessManagerSearchForm;

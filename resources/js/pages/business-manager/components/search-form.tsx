import { useTranslation } from 'react-i18next';
import { useSearchBusinessManager } from '@/pages/business-manager/hooks/use-search';
import { Card, CardAction, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Field, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Search } from 'lucide-react';
import { _PlatformType } from '@/lib/types/constants';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useMemo } from 'react';

const BusinessManagerSearchForm = () => {
    const { t } = useTranslation();
    const { query, setQuery, handleSearch } = useSearchBusinessManager();

    const platformValue = query.platform 
        ? query.platform.toString() 
        : undefined;

    const periodValue = query.period || undefined;

    // Tính toán date range dựa trên period
    const { startDate, endDate } = useMemo(() => {
        if (!query.period) {
            return { startDate: query.start_date || '', endDate: query.end_date || '' };
        }

        const today = new Date();
        let start: Date;
        let end: Date = new Date(today);

        switch (query.period) {
            case 'day':
                start = new Date(today);
                end = new Date(today);
                break;
            case 'week':
                const dayOfWeek = today.getDay();
                const diff = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
                start = new Date(today);
                start.setDate(today.getDate() + diff);
                start.setHours(0, 0, 0, 0);
                end.setHours(23, 59, 59, 999);
                break;
            case 'month':
                start = new Date(today.getFullYear(), today.getMonth(), 1);
                end = new Date(today.getFullYear(), today.getMonth() + 1, 0, 23, 59, 59, 999);
                break;
            default:
                return { startDate: query.start_date || '', endDate: query.end_date || '' };
        }

        return {
            startDate: start.toISOString().split('T')[0],
            endDate: end.toISOString().split('T')[0],
        };
    }, [query.period, query.start_date, query.end_date]);

    const handlePeriodChange = (period: string) => {
        if (!period) {
            setQuery({ 
                period: undefined,
                date: undefined,
                start_date: undefined,
                end_date: undefined,
            });
            return;
        }

        const today = new Date();
        let start: Date;
        let end: Date = new Date(today);

        switch (period) {
            case 'day':
                start = new Date(today);
                end = new Date(today);
                setQuery({ 
                    period: 'day' as const,
                    date: today.toISOString().split('T')[0],
                    start_date: undefined,
                    end_date: undefined,
                });
                return;
            case 'week':
                const dayOfWeek = today.getDay();
                const diff = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
                start = new Date(today);
                start.setDate(today.getDate() + diff);
                start.setHours(0, 0, 0, 0);
                end.setHours(23, 59, 59, 999);
                break;
            case 'month':
                start = new Date(today.getFullYear(), today.getMonth(), 1);
                end = new Date(today.getFullYear(), today.getMonth() + 1, 0, 23, 59, 59, 999);
                break;
            default:
                return;
        }

        setQuery({ 
            period: period as 'week' | 'month',
            date: undefined,
            start_date: start.toISOString().split('T')[0],
            end_date: end.toISOString().split('T')[0],
        });
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
                                    platform: value ? (Number(value) as _PlatformType) : undefined
                                });
                            }}
                        >
                            <SelectTrigger id="platform">
                                <SelectValue placeholder={t('common.all', { defaultValue: 'Tất cả' })} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={_PlatformType.META.toString()}>
                                    {t('enum.PlatformType.META', { defaultValue: 'Meta Ads' })}
                                </SelectItem>
                                <SelectItem value={_PlatformType.GOOGLE.toString()}>
                                    {t('enum.PlatformType.GOOGLE', { defaultValue: 'Google Ads' })}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </Field>
                    <Field>
                        <FieldLabel htmlFor="period">
                            {t('business_manager.filter.period', { defaultValue: 'Khoảng thời gian' })}
                        </FieldLabel>
                        <Select
                            value={periodValue}
                            onValueChange={handlePeriodChange}
                        >
                            <SelectTrigger id="period">
                                <SelectValue placeholder={t('common.all', { defaultValue: 'Tất cả' })} />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="day">{t('business_manager.filter.day', { defaultValue: 'Ngày' })}</SelectItem>
                                <SelectItem value="week">{t('business_manager.filter.week', { defaultValue: 'Tuần' })}</SelectItem>
                                <SelectItem value="month">{t('business_manager.filter.month', { defaultValue: 'Tháng' })}</SelectItem>
                            </SelectContent>
                        </Select>
                    </Field>
                    {query.period === 'day' && (
                        <Field>
                            <FieldLabel htmlFor="date">
                                {t('business_manager.filter.date', { defaultValue: 'Ngày' })}
                            </FieldLabel>
                            <Input
                                id="date"
                                type="date"
                                value={query.date || new Date().toISOString().split('T')[0]}
                                onChange={(e) => {
                                    setQuery({ date: e.target.value });
                                }}
                            />
                        </Field>
                    )}
                    {(query.period === 'week' || query.period === 'month') && (
                        <>
                            <Field>
                                <FieldLabel htmlFor="start_date">
                                    {t('business_manager.filter.start_date', { defaultValue: 'Từ ngày' })}
                                </FieldLabel>
                                <Input
                                    id="start_date"
                                    type="date"
                                    value={query.start_date || startDate}
                                    onChange={(e) => {
                                        setQuery({ start_date: e.target.value });
                                    }}
                                />
                            </Field>
                            <Field>
                                <FieldLabel htmlFor="end_date">
                                    {t('business_manager.filter.end_date', { defaultValue: 'Đến ngày' })}
                                </FieldLabel>
                                <Input
                                    id="end_date"
                                    type="date"
                                    value={query.end_date || endDate}
                                    onChange={(e) => {
                                        setQuery({ end_date: e.target.value });
                                    }}
                                />
                            </Field>
                        </>
                    )}
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
                </CardAction>
            </CardFooter>
        </Card>
    );
}

export default BusinessManagerSearchForm;


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
import { DateRangePicker } from '@/components/ui/date-range-picker';
import { DateRange } from 'react-day-picker';
import { useMemo } from 'react';

const BusinessManagerSearchForm = () => {
    const { t } = useTranslation();
    const { query, setQuery, handleSearch } = useSearchBusinessManager();

    const platformValue = query.platform 
        ? query.platform.toString() 
        : undefined;

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
                start_date: date.from.toISOString().split('T')[0],
                end_date: date.to.toISOString().split('T')[0],
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
                        <FieldLabel>
                            {t('business_manager.filter.period', { defaultValue: 'Khoảng thời gian' })}
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
                </CardAction>
            </CardFooter>
        </Card>
    );
}

export default BusinessManagerSearchForm;


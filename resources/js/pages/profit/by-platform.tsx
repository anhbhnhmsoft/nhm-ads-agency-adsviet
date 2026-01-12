import { useState, useMemo } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DateRangePicker } from '@/components/ui/date-range-picker';
import { DateRange } from 'react-day-picker';
import { TrendingUp, TrendingDown, DollarSign } from 'lucide-react';
import { profit_by_platform } from '@/routes';

type ProfitData = {
    platform: number;
    platform_name: string;
    revenue: string;
    cost: string;
    profit: string;
    profit_margin: string;
};

type Props = {
    profitData: ProfitData[];
    error?: string | null;
    startDate: string;
    endDate: string;
    selectedPlatform?: number | null;
};

export default function ProfitByPlatform({ profitData, error, startDate, endDate, selectedPlatform }: Props) {
    const { t } = useTranslation();
    const [dateRange, setDateRange] = useState<DateRange | undefined>({
        from: startDate ? new Date(startDate) : undefined,
        to: endDate ? new Date(endDate) : undefined,
    });
    const [localPlatform, setLocalPlatform] = useState<string>(selectedPlatform?.toString() || 'all');

    const formatCurrency = (value: string | number) => {
        const num = typeof value === 'string' ? parseFloat(value) : value;
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(num);
    };

    const totalRevenue = useMemo(() => {
        return profitData.reduce((sum, item) => sum + parseFloat(item.revenue), 0);
    }, [profitData]);

    const totalCost = useMemo(() => {
        return profitData.reduce((sum, item) => sum + parseFloat(item.cost), 0);
    }, [profitData]);

    const totalProfit = totalRevenue - totalCost;
    const totalProfitMargin = totalRevenue > 0 ? (totalProfit / totalRevenue) * 100 : 0;

    const handleDateChange = (date: DateRange | undefined) => {
        setDateRange(date);
        router.get(
            profit_by_platform().url,
            {
                start_date: date?.from?.toISOString().split('T')[0],
                end_date: date?.to?.toISOString().split('T')[0],
                platform: localPlatform !== 'all' ? parseInt(localPlatform) : null,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handlePlatformChange = (value: string) => {
        setLocalPlatform(value);
        router.get(
            profit_by_platform().url,
            {
                start_date: dateRange?.from?.toISOString().split('T')[0],
                end_date: dateRange?.to?.toISOString().split('T')[0],
                platform: value !== 'all' ? parseInt(value) : null,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    return (
        <AppLayout breadcrumbs={[{ title: t('profit.by_platform.title', { defaultValue: 'Lợi nhuận theo nền tảng' }) }]}>
            <Head title={t('profit.by_platform.title', { defaultValue: 'Lợi nhuận theo nền tảng' })} />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold">{t('profit.by_platform.title', { defaultValue: 'Lợi nhuận theo nền tảng' })}</h1>
                </div>

                {error && (
                    <Card className="border-red-200 bg-red-50">
                        <CardContent className="pt-6">
                            <p className="text-red-600">{error}</p>
                        </CardContent>
                    </Card>
                )}

                {/* Filters */}
                <div className="flex flex-wrap gap-4 items-end">
                    <div className="flex-1 min-w-[200px]">
                        <label className="text-sm font-medium mb-2 block">
                            {t('profit.date_range', { defaultValue: 'Khoảng thời gian' })}
                        </label>
                        <DateRangePicker date={dateRange} onDateChange={handleDateChange} />
                    </div>
                    <div className="w-[200px]">
                        <label className="text-sm font-medium mb-2 block">
                            {t('profit.platform', { defaultValue: 'Nền tảng' })}
                        </label>
                        <Select value={localPlatform} onValueChange={handlePlatformChange}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">{t('profit.all_platforms', { defaultValue: 'Tất cả' })}</SelectItem>
                                <SelectItem value="1">{t('profit.meta_ads', { defaultValue: 'Facebook Ads' })}</SelectItem>
                                <SelectItem value="2">{t('profit.google_ads', { defaultValue: 'Google Ads' })}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {/* Summary Cards */}
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('profit.total_revenue', { defaultValue: 'Tổng doanh thu' })}</CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">{formatCurrency(totalRevenue)}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('profit.total_cost', { defaultValue: 'Tổng chi phí' })}</CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-600">{formatCurrency(Math.abs(totalCost))}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('profit.total_profit', { defaultValue: 'Tổng lợi nhuận' })}</CardTitle>
                            {totalProfit >= 0 ? (
                                <TrendingUp className="h-4 w-4 text-green-600" />
                            ) : (
                                <TrendingDown className="h-4 w-4 text-red-600" />
                            )}
                        </CardHeader>
                        <CardContent>
                            <div className={`text-2xl font-bold ${totalProfit >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                {totalProfit >= 0 ? '+' : ''}
                                {formatCurrency(totalProfit)}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">{t('profit.profit_margin', { defaultValue: 'Tỷ suất lợi nhuận' })}</CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className={`text-2xl font-bold ${totalProfitMargin >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                {totalProfitMargin >= 0 ? '+' : ''}
                                {totalProfitMargin.toFixed(2)}%
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Platform List */}
                {profitData.length > 0 ? (
                    <div className="grid gap-4 md:grid-cols-2">
                        {profitData.map((platformProfit) => {
                            const profit = parseFloat(platformProfit.profit);
                            const margin = parseFloat(platformProfit.profit_margin);
                            return (
                                <Card key={platformProfit.platform}>
                                    <CardHeader>
                                        <CardTitle className="text-lg font-semibold text-[#4285f4]">
                                            {platformProfit.platform_name}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-3">
                                            <div className="flex justify-between items-center">
                                                <span className="text-sm text-muted-foreground">
                                                    {t('profit.revenue', { defaultValue: 'Doanh thu' })}:
                                                </span>
                                                <span className="font-semibold text-green-600">
                                                    {formatCurrency(platformProfit.revenue)}
                                                </span>
                                            </div>
                                            <div className="flex justify-between items-center">
                                                <span className="text-sm text-muted-foreground">
                                                    {t('profit.cost', { defaultValue: 'Chi phí' })}:
                                                </span>
                                                <span className="font-semibold text-red-600">
                                                    {formatCurrency(Math.abs(parseFloat(platformProfit.cost)))}
                                                </span>
                                            </div>
                                            <div className="pt-2 border-t">
                                                <div className="flex justify-between items-center mb-1">
                                                    <span className="text-sm font-semibold">
                                                        {t('profit.profit', { defaultValue: 'Lợi nhuận' })}:
                                                    </span>
                                                    <span className={`text-lg font-bold ${profit >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                                        {profit >= 0 ? '+' : ''}
                                                        {formatCurrency(profit)}
                                                    </span>
                                                </div>
                                                <div className="flex justify-between items-center">
                                                    <span className="text-sm text-muted-foreground">
                                                        {t('profit.profit_margin', { defaultValue: 'Tỷ suất lợi nhuận' })}:
                                                    </span>
                                                    <span className={`font-semibold ${margin >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                                        {margin >= 0 ? '+' : ''}
                                                        {margin.toFixed(2)}%
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                ) : (
                    <Card>
                        <CardContent className="py-8 text-center text-muted-foreground">
                            {t('profit.no_data', { defaultValue: 'Không có dữ liệu' })}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}


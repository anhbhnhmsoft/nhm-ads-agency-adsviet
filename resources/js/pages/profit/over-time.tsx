import { useState, useMemo } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DateRangePicker } from '@/components/ui/date-range-picker';
import { DateRange } from 'react-day-picker';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, BarChart, Bar } from 'recharts';
import { TrendingUp, TrendingDown, DollarSign, Calendar, Info } from 'lucide-react';
import { Tooltip as UITooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { formatDateForQuery } from '@/lib/utils';

type ProfitDataPoint = {
    period: string;
    revenue: string;
    cost: string;
    profit: string;
    profit_margin: string;
};

type Props = {
    profitData: ProfitDataPoint[];
    error?: string | null;
    startDate: string | null;
    endDate: string | null;
    groupBy: 'day' | 'week' | 'month';
    selectedPlatform?: number | null;
};

export default function ProfitOverTime({ profitData, error, startDate, endDate, groupBy, selectedPlatform }: Props) {
    const { t } = useTranslation();
    const [dateRange, setDateRange] = useState<DateRange | undefined>({
        from: startDate ? new Date(startDate) : undefined,
        to: endDate ? new Date(endDate) : undefined,
    });
    const [localGroupBy, setLocalGroupBy] = useState<'day' | 'week' | 'month'>(groupBy);
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

    const formatPeriod = (period: string) => {
        if (groupBy === 'month') {
            // Format: YYYY-MM -> Tháng MM/YYYY
            const [year, month] = period.split('-');
            return `Tháng ${month}/${year}`;
        } else if (groupBy === 'week') {
            // Format: YYYY-WW -> Tuần WW/YYYY
            const [year, week] = period.split('-');
            return `Tuần ${week}/${year}`;
        } else {
            // Format: YYYY-MM-DD -> DD/MM/YYYY
            const [year, month, day] = period.split('-');
            return `${day}/${month}/${year}`;
        }
    };

    const chartData = useMemo(() => {
        return profitData.map((item) => ({
            period: formatPeriod(item.period),
            revenue: parseFloat(item.revenue),
            cost: parseFloat(item.cost),
            profit: parseFloat(item.profit),
            profitMargin: parseFloat(item.profit_margin),
        }));
    }, [profitData, groupBy]);

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
            '/profit/over-time',
            {
                start_date: formatDateForQuery(date?.from),
                end_date: formatDateForQuery(date?.to),
                group_by: localGroupBy,
                platform: localPlatform !== 'all' ? parseInt(localPlatform) : null,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleGroupByChange = (value: 'day' | 'week' | 'month') => {
        setLocalGroupBy(value);
        router.get(
            '/profit/over-time',
            {
                start_date: formatDateForQuery(dateRange?.from),
                end_date: formatDateForQuery(dateRange?.to),
                group_by: value,
                platform: localPlatform !== 'all' ? parseInt(localPlatform) : null,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handlePlatformChange = (value: string) => {
        setLocalPlatform(value);
        router.get(
            '/profit/over-time',
            {
                start_date: formatDateForQuery(dateRange?.from),
                end_date: formatDateForQuery(dateRange?.to),
                group_by: localGroupBy,
                platform: value !== 'all' ? parseInt(value) : null,
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    return (
        <AppLayout breadcrumbs={[{ title: t('profit.over_time.title', { defaultValue: 'Lợi nhuận tổng theo thời gian' }) }]}>
            <Head title={t('profit.over_time.title', { defaultValue: 'Lợi nhuận tổng theo thời gian' })} />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold flex items-center gap-2">
                        {t('profit.over_time.title', { defaultValue: 'Lợi nhuận tổng theo thời gian' })}
                        <TooltipProvider delayDuration={0}>
                            <UITooltip>
                                <TooltipTrigger asChild>
                                    <Info className="h-5 w-5 text-muted-foreground hover:text-primary cursor-help transition-colors" />
                                </TooltipTrigger>
                                <TooltipContent className="max-w-[350px] p-3 text-sm shadow-md bg-popover text-popover-foreground border" side="bottom" align="start">
                                    <div className="space-y-3">
                                        <div>
                                            <div className="font-semibold text-primary mb-1">Cách tính Doanh thu:</div>
                                            <div className="text-muted-foreground leading-relaxed">
                                                Phí mở TK + Tiền nạp + (Tiền nạp * Phí dịch vụ %)
                                            </div>
                                        </div>
                                        <div>
                                            <div className="font-semibold text-primary mb-1">Cách tính Chi phí:</div>
                                            <div className="text-muted-foreground leading-relaxed">
                                                Phí mở TK bên NCC + (Tiền nạp * Phí NCC %)
                                            </div>
                                        </div>
                                        <div className="pt-2 border-t font-medium text-foreground">
                                            Lợi nhuận = Doanh thu - Chi phí
                                        </div>
                                    </div>
                                </TooltipContent>
                            </UITooltip>
                        </TooltipProvider>
                    </h1>
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
                            {t('profit.group_by', { defaultValue: 'Nhóm theo' })}
                        </label>
                        <Select value={localGroupBy} onValueChange={handleGroupByChange}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="day">{t('profit.group_by_day', { defaultValue: 'Theo ngày' })}</SelectItem>
                                <SelectItem value="week">{t('profit.group_by_week', { defaultValue: 'Theo tuần' })}</SelectItem>
                                <SelectItem value="month">{t('profit.group_by_month', { defaultValue: 'Theo tháng' })}</SelectItem>
                            </SelectContent>
                        </Select>
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
                            <div className="text-2xl font-bold text-red-600">{formatCurrency(totalCost)}</div>
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

                {/* Charts */}
                {chartData.length > 0 ? (
                    <div className="grid gap-4 md:grid-cols-1">
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('profit.profit_chart', { defaultValue: 'Biểu đồ lợi nhuận theo thời gian' })}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ResponsiveContainer width="100%" height={400}>
                                    <LineChart data={chartData}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="period" />
                                        <YAxis />
                                        <Tooltip
                                            formatter={(value: number, _name: string, props: any) => {
                                                const dataKey = props?.dataKey as string | undefined;
                                                if (dataKey === 'profitMargin') {
                                                    return [`${value.toFixed(2)}%`, t('profit.profit_margin', { defaultValue: 'Tỷ suất lợi nhuận' })];
                                                }
                                                let label = t('profit.profit', { defaultValue: 'Lợi nhuận' });
                                                if (dataKey === 'revenue') {
                                                    label = t('profit.revenue', { defaultValue: 'Doanh thu' });
                                                } else if (dataKey === 'cost') {
                                                    label = t('profit.cost', { defaultValue: 'Chi phí' });
                                                }
                                                return [formatCurrency(value), label];
                                            }}
                                        />
                                        <Legend />
                                        <Line type="monotone" dataKey="revenue" stroke="#22c55e" strokeWidth={2} name={t('profit.revenue', { defaultValue: 'Doanh thu' })} />
                                        <Line type="monotone" dataKey="cost" stroke="#ef4444" strokeWidth={2} name={t('profit.cost', { defaultValue: 'Chi phí' })} />
                                        <Line type="monotone" dataKey="profit" stroke="#4285f4" strokeWidth={2} name={t('profit.profit', { defaultValue: 'Lợi nhuận' })} />
                                    </LineChart>
                                </ResponsiveContainer>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>{t('profit.profit_margin_chart', { defaultValue: 'Biểu đồ tỷ suất lợi nhuận' })}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ResponsiveContainer width="100%" height={300}>
                                    <BarChart data={chartData}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="period" />
                                        <YAxis />
                                        <Tooltip
                                            formatter={(value: number) => [`${value.toFixed(2)}%`, t('profit.profit_margin', { defaultValue: 'Tỷ suất lợi nhuận' })]}
                                        />
                                        <Bar dataKey="profitMargin" fill="#4285f4" name={t('profit.profit_margin', { defaultValue: 'Tỷ suất lợi nhuận' })} />
                                    </BarChart>
                                </ResponsiveContainer>
                            </CardContent>
                        </Card>
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

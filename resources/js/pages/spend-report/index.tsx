import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { IBreadcrumbItem } from '@/lib/types/type';
import { spend_report_index } from '@/routes';
import { Head, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useMemo, useState, useEffect } from 'react';
import { Bar, BarChart, Cell, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { TrendingUp, TrendingDown, AlertCircle } from 'lucide-react';
import type { SpendReportPageProps, DatePresetOption, ChartDataPoint } from './types/type';

// Màu sắc cho biểu đồ tròn
const PIE_COLORS = ['#c8f542', '#f5a742', '#4287f5', '#42f5a7', '#f542a7', '#a742f5'];

export default function SpendReportIndex({
    reportData,
    insightData,
    selectedPlatform,
    selectedDatePreset,
    error,
}: SpendReportPageProps) {
    const { t } = useTranslation();
    const [platform, setPlatform] = useState(selectedPlatform);
    const [datePreset, setDatePreset] = useState(selectedDatePreset);

    useEffect(() => {
        setPlatform(selectedPlatform);
    }, [selectedPlatform]);

    useEffect(() => {
        setDatePreset(selectedDatePreset);
    }, [selectedDatePreset]);

    const currencyFormatter = useMemo(
        () =>
            new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }),
        []
    );

    const formatCurrency = (value: number | string) => {
        const num = typeof value === 'string' ? parseFloat(value) : value;
        return currencyFormatter.format(num);
    };

    const breadcrumbs: IBreadcrumbItem[] = useMemo(
        () => [
            {
                title: t('spend_report.title', { defaultValue: 'Báo cáo chi tiêu' }),
                href: spend_report_index().url,
            },
        ],
        [t]
    );

    const datePresetOptions: DatePresetOption[] = [
        { value: 'last_7d', label: t('spend_report.preset_7d', { defaultValue: '7 ngày qua' }) },
        { value: 'last_14d', label: t('spend_report.preset_14d', { defaultValue: '14 ngày qua' }) },
        { value: 'last_28d', label: t('spend_report.preset_28d', { defaultValue: '28 ngày qua' }) },
        { value: 'last_30d', label: t('spend_report.preset_30d', { defaultValue: '30 ngày qua' }) },
        { value: 'last_90d', label: t('spend_report.preset_90d', { defaultValue: '90 ngày qua' }) },
    ];

    const handlePlatformChange = (value: string) => {
        setPlatform(value);
        router.get(
            spend_report_index().url,
            { platform: value, date_preset: datePreset },
            { preserveState: true, preserveScroll: true }
        );
    };

    const handleDatePresetChange = (value: string) => {
        setDatePreset(value);
        router.get(
            spend_report_index().url,
            { platform, date_preset: value },
            { preserveState: true, preserveScroll: true }
        );
    };

    // Tính % thay đổi so với kỳ trước (giả lập)
    const percentChange = useMemo(() => {
        if (!insightData?.chart || insightData.chart.length < 2) return 0;
        const midPoint = Math.floor(insightData.chart.length / 2);
        const firstHalf = insightData.chart.slice(0, midPoint).reduce((acc, item) => acc + item.value, 0);
        const secondHalf = insightData.chart.slice(midPoint).reduce((acc, item) => acc + item.value, 0);
        if (firstHalf === 0) return secondHalf > 0 ? 100 : 0;
        return ((secondHalf - firstHalf) / firstHalf) * 100;
    }, [insightData]);

    // Format ngày cho biểu đồ
    const formatChartDate = (dateStr: string) => {
        const date = new Date(dateStr);
        return `${date.getDate()}/${date.getMonth() + 1}`;
    };

    // Dữ liệu cho biểu đồ tròn
    const pieData = useMemo(() => {
        if (!reportData?.account_spend || reportData.account_spend.length === 0) {
            return [];
        }
        const total = reportData.account_spend.reduce((acc, item) => acc + item.amount_spent, 0);
        return reportData.account_spend.map((item, index) => {
            const displayName =
                item.account_name && item.account_name.trim().length > 0
                    ? item.account_name
                    : item.account_id || t('spend_report.unknown_account', { defaultValue: 'Tài khoản chưa xác định' });
            return {
                name: displayName,
                value: item.amount_spent,
                percent: total > 0 ? Math.round((item.amount_spent / total) * 100) : 0,
                color: PIE_COLORS[index % PIE_COLORS.length],
            };
        });
    }, [reportData, t]);

    // Dữ liệu cho biểu đồ cột
    const barData = useMemo(() => {
        if (!insightData?.chart) return [];
        return insightData.chart.map((item: ChartDataPoint) => ({
            date: formatChartDate(item.date),
            value: item.value,
        }));
    }, [insightData]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('spend_report.title', { defaultValue: 'Báo cáo chi tiêu' })} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6">
                {/* Header */}
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">{t('spend_report.title', { defaultValue: 'Báo cáo chi tiêu' })}</h1>
                        <p className="text-muted-foreground">
                            {t('spend_report.subtitle', { defaultValue: 'Theo dõi chi tiêu quảng cáo của bạn' })}
                        </p>
                    </div>
                    <div className="flex gap-3">
                        <div className="flex flex-col gap-1.5">
                            <Label className="text-xs text-muted-foreground">
                                {t('spend_report.platform', { defaultValue: 'Nền tảng' })}
                            </Label>
                            <Select value={platform} onValueChange={handlePlatformChange}>
                                <SelectTrigger className="w-[140px]">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="meta">Meta Ads</SelectItem>
                                    <SelectItem value="google_ads">Google Ads</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                {error && (
                    <Card className="border-red-500 bg-red-50 dark:bg-red-950/20">
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-2 text-red-600">
                                <AlertCircle className="h-5 w-5" />
                                <span>{error}</span>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Tổng chi tiêu Card */}
                <Card className="bg-white text-slate-900">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base font-medium text-muted-foreground">
                            {t('spend_report.total_spend', { defaultValue: 'Tổng chi tiêu' })}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-3xl font-bold text-slate-900">
                            {formatCurrency(reportData?.total_spend ?? 0)}
                        </div>
                        <div className="mt-2 text-sm text-muted-foreground">
                            {t('spend_report.today_spend', { defaultValue: 'Chi tiêu hôm nay' })}
                        </div>
                        <div className="text-lg font-semibold text-slate-900">
                            {formatCurrency(reportData?.today_spend ?? 0)}
                        </div>
                    </CardContent>
                </Card>

                {/* Biểu đồ chi tiêu */}
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <div>
                            <CardTitle className="text-lg font-semibold">
                                {t('spend_report.chart_title', { defaultValue: 'Biểu đồ chi tiêu' })}
                            </CardTitle>
                            <CardDescription className="flex items-center gap-1 mt-1">
                                {percentChange !== 0 && (
                                    <>
                                        {percentChange > 0 ? (
                                            <TrendingUp className="h-4 w-4 text-green-500" />
                                        ) : (
                                            <TrendingDown className="h-4 w-4 text-red-500" />
                                        )}
                                        <span className={percentChange > 0 ? 'text-green-600' : 'text-red-600'}>
                                            {percentChange > 0 ? '+' : ''}
                                            {percentChange.toFixed(0)}%
                                        </span>
                                        <span className="text-muted-foreground ml-1">
                                            {t('spend_report.vs_previous', { defaultValue: 'so với hôm qua' })}
                                        </span>
                                    </>
                                )}
                            </CardDescription>
                        </div>
                        <Select value={datePreset} onValueChange={handleDatePresetChange}>
                            <SelectTrigger className="w-[140px]">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {datePresetOptions.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </CardHeader>
                    <CardContent>
                        {barData.length > 0 ? (
                            <ResponsiveContainer width="100%" height={280}>
                                <BarChart data={barData} margin={{ top: 10, right: 10, left: 0, bottom: 0 }}>
                                    <XAxis
                                        dataKey="date"
                                        axisLine={false}
                                        tickLine={false}
                                        tick={{ fontSize: 12, fill: '#888' }}
                                    />
                                    <YAxis
                                        axisLine={false}
                                        tickLine={false}
                                        tick={{ fontSize: 12, fill: '#888' }}
                                        tickFormatter={(value) => `$${value}`}
                                    />
                                    <Tooltip
                                        formatter={(value: number) => [formatCurrency(value), 'Chi tiêu']}
                                        labelFormatter={(label) => `Ngày ${label}`}
                                        contentStyle={{
                                            backgroundColor: '#1f2937',
                                            border: 'none',
                                            borderRadius: '8px',
                                            color: '#fff',
                                        }}
                                        labelStyle={{ color: '#fff' }}
                                        itemStyle={{ color: '#fff' }}
                                    />
                                    <Bar dataKey="value" radius={[4, 4, 0, 0]}>
                                        {barData.map((entry, index) => (
                                            <Cell
                                                key={`cell-${index}`}
                                                fill={index === barData.length - 1 ? '#111827' : '#9ca3af'}
                                            />
                                        ))}
                                    </Bar>
                                </BarChart>
                            </ResponsiveContainer>
                        ) : (
                            <div className="flex h-[280px] items-center justify-center text-muted-foreground">
                                {t('spend_report.no_chart_data', { defaultValue: 'Chưa có dữ liệu biểu đồ' })}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Phân bổ ngân sách */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg font-semibold">
                            {t('spend_report.budget_allocation', { defaultValue: 'Phân bổ ngân sách' })}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {pieData.length > 0 ? (
                            <div className="flex flex-col items-center gap-6 md:flex-row md:justify-around">
                                <div className="h-[200px] w-[200px]">
                                    <ResponsiveContainer width="100%" height="100%">
                                        <PieChart>
                                            <Pie
                                                data={pieData}
                                                cx="50%"
                                                cy="50%"
                                                innerRadius={50}
                                                outerRadius={80}
                                                paddingAngle={2}
                                                dataKey="value"
                                            >
                                                {pieData.map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={entry.color} />
                                                ))}
                                            </Pie>
                                            <Tooltip
                                                formatter={(value: number) => [formatCurrency(value), 'Chi tiêu']}
                                                contentStyle={{
                                                    backgroundColor: '#1f2937',
                                                    border: 'none',
                                                    borderRadius: '8px',
                                                    color: '#fff',
                                                }}
                                                labelStyle={{ color: '#fff' }}
                                                itemStyle={{ color: '#fff' }}
                                            />
                                        </PieChart>
                                    </ResponsiveContainer>
                                </div>
                                <div className="flex flex-col gap-3">
                                    {pieData.map((item, index) => (
                                        <div key={index} className="flex items-center gap-3">
                                            <div
                                                className="h-3 w-3 rounded-full"
                                                style={{ backgroundColor: item.color }}
                                            />
                                            <span className="min-w-[60px] font-medium">{item.name}</span>
                                            <span className="text-muted-foreground">{item.percent}%</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ) : (
                            <div className="flex h-[200px] items-center justify-center text-muted-foreground">
                                {t('spend_report.no_allocation_data', { defaultValue: 'Chưa có dữ liệu phân bổ' })}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}


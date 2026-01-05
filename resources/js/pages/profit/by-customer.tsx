import { useState, useMemo } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { DataTable } from '@/components/table/data-table';
import { ColumnDef } from '@tanstack/react-table';
import { Calendar, TrendingUp, TrendingDown, DollarSign } from 'lucide-react';
import { profit_by_customer } from '@/routes';

type ProfitData = {
    customer_id: string;
    customer_name: string;
    customer_email: string;
    revenue: string;
    cost: string;
    profit: string;
    profit_margin: string;
    platform_stats: {
        meta: {
            revenue: string;
            cost: string;
            profit: string;
        };
        google: {
            revenue: string;
            cost: string;
            profit: string;
        };
    };
};

type Props = {
    profitData: ProfitData[];
    error?: string | null;
    startDate: string;
    endDate: string;
    selectedCustomerId?: number | null;
};

export default function ProfitByCustomer({ profitData, error, startDate, endDate, selectedCustomerId }: Props) {
    const { t } = useTranslation();
    const [localStartDate, setLocalStartDate] = useState(startDate);
    const [localEndDate, setLocalEndDate] = useState(endDate);

    const formatCurrency = (value: string | number) => {
        const num = typeof value === 'string' ? parseFloat(value) : value;
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(num);
    };

    const columns: ColumnDef<ProfitData>[] = useMemo(
        () => [
            {
                accessorKey: 'customer_name',
                header: t('profit.customer_name', { defaultValue: 'Khách hàng' }),
                cell: ({ row }) => {
                    return (
                        <div>
                            <div className="font-medium">{row.original.customer_name}</div>
                            <div className="text-sm text-muted-foreground">{row.original.customer_email}</div>
                        </div>
                    );
                },
            },
            {
                accessorKey: 'revenue',
                header: t('profit.revenue', { defaultValue: 'Doanh thu' }),
                cell: ({ row }) => {
                    return <span className="font-medium text-green-600">{formatCurrency(row.original.revenue)}</span>;
                },
            },
            {
                accessorKey: 'cost',
                header: t('profit.cost', { defaultValue: 'Chi phí' }),
                cell: ({ row }) => {
                    return <span className="font-medium text-red-600">{formatCurrency(Math.abs(parseFloat(row.original.cost)))}</span>;
                },
            },
            {
                accessorKey: 'profit',
                header: t('profit.profit', { defaultValue: 'Lợi nhuận' }),
                cell: ({ row }) => {
                    const profit = parseFloat(row.original.profit);
                    const isPositive = profit >= 0;
                    return (
                        <span className={`font-bold ${isPositive ? 'text-green-600' : 'text-red-600'}`}>
                            {isPositive ? '+' : ''}
                            {formatCurrency(row.original.profit)}
                        </span>
                    );
                },
            },
            {
                accessorKey: 'profit_margin',
                header: t('profit.profit_margin', { defaultValue: 'Tỷ suất lợi nhuận (%)' }),
                cell: ({ row }) => {
                    const margin = parseFloat(row.original.profit_margin);
                    const isPositive = margin >= 0;
                    return (
                        <span className={`font-medium ${isPositive ? 'text-green-600' : 'text-red-600'}`}>
                            {isPositive ? '+' : ''}
                            {margin.toFixed(2)}%
                        </span>
                    );
                },
            },
            {
                id: 'platform_stats',
                header: t('profit.platform_breakdown', { defaultValue: 'Chi tiết theo nền tảng' }),
                cell: ({ row }) => {
                    const stats = row.original.platform_stats;
                    return (
                        <div className="space-y-1 text-sm">
                            <div>
                                <span className="font-medium">Facebook:</span>{' '}
                                <span className={parseFloat(stats.meta.profit) >= 0 ? 'text-green-600' : 'text-red-600'}>
                                    {formatCurrency(stats.meta.profit)}
                                </span>
                            </div>
                            <div>
                                <span className="font-medium">Google:</span>{' '}
                                <span className={parseFloat(stats.google.profit) >= 0 ? 'text-green-600' : 'text-red-600'}>
                                    {formatCurrency(stats.google.profit)}
                                </span>
                            </div>
                        </div>
                    );
                },
            },
        ],
        [t]
    );

    const handleDateFilter = () => {
        router.get(profit_by_customer().url, {
            start_date: localStartDate,
            end_date: localEndDate,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Tính tổng
    const totals = useMemo(() => {
        const totalRevenue = profitData.reduce((sum, item) => sum + parseFloat(item.revenue), 0);
        const totalCost = profitData.reduce((sum, item) => sum + parseFloat(item.cost), 0);
        const totalProfit = totalRevenue - totalCost;
        const totalMargin = totalRevenue > 0 ? (totalProfit / totalRevenue) * 100 : 0;

        return {
            revenue: totalRevenue,
            cost: totalCost,
            profit: totalProfit,
            margin: totalMargin,
        };
    }, [profitData]);

    return (
        <AppLayout>
            <Head title={t('profit.by_customer_title', { defaultValue: 'Thống kê lợi nhuận theo khách hàng' })} />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold">
                        {t('profit.by_customer_title', { defaultValue: 'Thống kê lợi nhuận theo khách hàng' })}
                    </h1>
                    <p className="text-muted-foreground mt-1">
                        {t('profit.by_customer_description', { defaultValue: 'Xem lợi nhuận chi tiết theo từng khách hàng' })}
                    </p>
                </div>

                {/* Date Filter */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Calendar className="h-5 w-5" />
                            {t('profit.filter_date_range', { defaultValue: 'Lọc theo khoảng thời gian' })}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-col sm:flex-row gap-4">
                            <div className="space-y-2 flex-1">
                                <Label htmlFor="start_date">
                                    {t('profit.start_date', { defaultValue: 'Từ ngày' })}
                                </Label>
                                <Input
                                    id="start_date"
                                    type="date"
                                    value={localStartDate}
                                    onChange={(e) => setLocalStartDate(e.target.value)}
                                />
                            </div>
                            <div className="space-y-2 flex-1">
                                <Label htmlFor="end_date">
                                    {t('profit.end_date', { defaultValue: 'Đến ngày' })}
                                </Label>
                                <Input
                                    id="end_date"
                                    type="date"
                                    value={localEndDate}
                                    onChange={(e) => setLocalEndDate(e.target.value)}
                                />
                            </div>
                            <div className="flex items-end">
                                <Button onClick={handleDateFilter}>
                                    {t('common.filter', { defaultValue: 'Lọc' })}
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Summary Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm text-muted-foreground flex items-center gap-2">
                                <DollarSign className="h-4 w-4" />
                                {t('profit.total_revenue', { defaultValue: 'Tổng doanh thu' })}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">
                                {formatCurrency(totals.revenue)}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm text-muted-foreground flex items-center gap-2">
                                <TrendingDown className="h-4 w-4" />
                                {t('profit.total_cost', { defaultValue: 'Tổng chi phí' })}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-600">
                                {formatCurrency(Math.abs(totals.cost))}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm text-muted-foreground flex items-center gap-2">
                                <TrendingUp className="h-4 w-4" />
                                {t('profit.total_profit', { defaultValue: 'Tổng lợi nhuận' })}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className={`text-2xl font-bold ${totals.profit >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                {totals.profit >= 0 ? '+' : ''}
                                {formatCurrency(totals.profit)}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm text-muted-foreground">
                                {t('profit.total_margin', { defaultValue: 'Tỷ suất lợi nhuận' })}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className={`text-2xl font-bold ${totals.margin >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                                {totals.margin >= 0 ? '+' : ''}
                                {totals.margin.toFixed(2)}%
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Error Message */}
                {error && (
                    <Card className="border-red-500 bg-red-50">
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-2 text-red-600">{error}</div>
                        </CardContent>
                    </Card>
                )}

                {/* Profit Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>
                            {t('profit.customer_list', { defaultValue: 'Danh sách khách hàng' })}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {profitData.length === 0 ? (
                            <div className="text-center py-8 text-muted-foreground">
                                {t('profit.no_data', { defaultValue: 'Chưa có dữ liệu' })}
                            </div>
                        ) : (
                            <DataTable
                                columns={columns}
                                paginator={{
                                    data: profitData,
                                    links: {
                                        first: null,
                                        last: null,
                                        next: null,
                                        prev: null,
                                    },
                                    meta: {
                                        links: [],
                                        current_page: 1,
                                        from: 1,
                                        last_page: 1,
                                        per_page: profitData.length || 1,
                                        to: profitData.length,
                                        total: profitData.length,
                                    },
                                }}
                            />
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}


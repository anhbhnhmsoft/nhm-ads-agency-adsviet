import { useState, useMemo } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DateRangePicker } from '@/components/ui/date-range-picker';
import { DateRange } from 'react-day-picker';
import { DataTable } from '@/components/table/data-table';
import { ColumnDef } from '@tanstack/react-table';
import { TrendingUp, TrendingDown, DollarSign } from 'lucide-react';

type ProfitData = {
    bm_mcc_id: string;
    platform: number;
    platform_name: string;
    user: {
        id: number;
        name: string;
        email: string;
    } | null;
    user_count: number;
    service_user_count: number;
    revenue: string;
    cost: string;
    profit: string;
    profit_margin: string;
};

type Props = {
    profitData: ProfitData[];
    error?: string | null;
    startDate: string | null;
    endDate: string | null;
};

export default function ProfitByBmMcc({ profitData, error, startDate, endDate }: Props) {
    const { t } = useTranslation();
    const [dateRange, setDateRange] = useState<DateRange | undefined>({
        from: startDate ? new Date(startDate) : undefined,
        to: endDate ? new Date(endDate) : undefined,
    });

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
            '/profit/by-bm-mcc',
            {
                start_date: date?.from?.toISOString().split('T')[0],
                end_date: date?.to?.toISOString().split('T')[0],
            },
            { preserveState: true, preserveScroll: true }
        );
    };

    const columns: ColumnDef<ProfitData>[] = useMemo(
        () => [
            {
                accessorKey: 'bm_mcc_id',
                header: t('profit.bm_mcc_id', { defaultValue: 'BM/MCC ID' }),
                cell: ({ row }) => {
                    return <div className="font-medium">{row.original.bm_mcc_id}</div>;
                },
            },
            {
                accessorKey: 'platform_name',
                header: t('profit.platform', { defaultValue: 'Nền tảng' }),
                cell: ({ row }) => {
                    return <div>{row.original.platform_name}</div>;
                },
            },
            {
                accessorKey: 'user',
                header: t('profit.user', { defaultValue: 'Khách hàng' }),
                cell: ({ row }) => {
                    const user = row.original.user;
                    if (!user) {
                        return <span className="text-muted-foreground">-</span>;
                    }
                    return (
                        <div>
                            <div className="font-medium">{user.name}</div>
                            <div className="text-sm text-muted-foreground">{user.email}</div>
                        </div>
                    );
                },
            },
            {
                accessorKey: 'user_count',
                header: t('profit.user_count', { defaultValue: 'Số khách hàng' }),
                cell: ({ row }) => {
                    return <div>{row.original.user_count}</div>;
                },
            },
            {
                accessorKey: 'service_user_count',
                header: t('profit.service_user_count', { defaultValue: 'Số service user' }),
                cell: ({ row }) => {
                    return <div>{row.original.service_user_count}</div>;
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
                    return <span className="font-medium text-red-600">{formatCurrency(row.original.cost)}</span>;
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
                header: t('profit.profit_margin', { defaultValue: 'Tỷ suất lợi nhuận' }),
                cell: ({ row }) => {
                    const margin = parseFloat(row.original.profit_margin);
                    const isPositive = margin >= 0;
                    return (
                        <span className={`font-semibold ${isPositive ? 'text-green-600' : 'text-red-600'}`}>
                            {isPositive ? '+' : ''}
                            {margin.toFixed(2)}%
                        </span>
                    );
                },
            },
        ],
        [t]
    );

    return (
        <AppLayout breadcrumbs={[{ title: t('profit.by_bm_mcc.title', { defaultValue: 'Lợi nhuận theo BM/MCC' }) }]}>
            <Head title={t('profit.by_bm_mcc.title', { defaultValue: 'Lợi nhuận theo BM/MCC' })} />
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold">{t('profit.by_bm_mcc.title', { defaultValue: 'Lợi nhuận theo BM/MCC' })}</h1>
                </div>

                {error && (
                    <Card className="border-red-200 bg-red-50">
                        <CardContent className="pt-6">
                            <p className="text-red-600">{error}</p>
                        </CardContent>
                    </Card>
                )}

                {/* Date Range Filter */}
                <div className="flex justify-end">
                    <DateRangePicker date={dateRange} onDateChange={handleDateChange} />
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

                {/* Data Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>{t('profit.bm_mcc_list', { defaultValue: 'Danh sách BM/MCC' })}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {profitData.length > 0 ? (
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
                                        per_page: profitData.length,
                                        to: profitData.length,
                                        total: profitData.length,
                                    },
                                }}
                            />
                        ) : (
                            <div className="py-8 text-center text-muted-foreground">
                                {t('profit.no_data', { defaultValue: 'Không có dữ liệu' })}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

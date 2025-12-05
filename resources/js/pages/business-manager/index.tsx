import { ReactNode, useMemo, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { useTranslation } from 'react-i18next';
import { ColumnDef } from '@tanstack/react-table';
import { DataTable } from '@/components/table/data-table';
import BusinessManagerSearchForm from '@/pages/business-manager/components/search-form';
import type { BusinessManagerItem, BusinessManagerPagination } from '@/pages/business-manager/types/type';
import { _PlatformType } from '@/lib/types/constants';
import { Avatar, AvatarImage } from '@/components/ui/avatar';
import GoogleIcon from '@/images/google_icon.png';
import FacebookIcon from '@/images/facebook_icon.png';
import { Button } from '@/components/ui/button';
import { Eye } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import axios from 'axios';

type Props = {
    paginator: BusinessManagerPagination;
};

const BusinessManagerIndex = ({ paginator }: Props) => {
    const { t } = useTranslation();
    const [selectedBM, setSelectedBM] = useState<BusinessManagerItem | null>(null);
    const [detailDialogOpen, setDetailDialogOpen] = useState(false);
    const [accounts, setAccounts] = useState<any[]>([]);
    const [loadingAccounts, setLoadingAccounts] = useState(false);

    const handleViewDetails = async (item: BusinessManagerItem) => {
        setSelectedBM(item);
        setDetailDialogOpen(true);
        setLoadingAccounts(true);
        
        try {
            const response = await axios.get(`/business-managers/${item.id}/accounts`, {
                params: {
                    platform: item.platform,
                },
            });
            
            if (response.data.success) {
                setAccounts(response.data.data || []);
            } else {
                setAccounts([]);
            }
        } catch (error) {
            console.error('Error loading accounts:', error);
            setAccounts([]);
        } finally {
            setLoadingAccounts(false);
        }
    };

    const columns: ColumnDef<BusinessManagerItem>[] = useMemo(
        () => [
            {
                accessorKey: 'name',
                header: t('business_manager.table.account_name', { defaultValue: 'Tên tài khoản' }),
            },
            {
                accessorKey: 'id',
                header: t('business_manager.table.account_id', { defaultValue: 'ID tk' }),
            },
            {
                accessorKey: 'platform',
                header: t('business_manager.table.account_info', { defaultValue: 'Thông tin tài khoản' }),
                cell: ({ row }) => {
                    const platform = row.original.platform;
                    const totalAccounts = row.original.total_accounts;
                    const activeAccounts = row.original.active_accounts;
                    const disabledAccounts = row.original.disabled_accounts;
                    
                    return (
                        <div className="flex items-center gap-2">
                            {platform === _PlatformType.GOOGLE && (
                                <Avatar className="h-6 w-6">
                                    <AvatarImage src={GoogleIcon} />
                                </Avatar>
                            )}
                            {platform === _PlatformType.META && (
                                <Avatar className="h-6 w-6">
                                    <AvatarImage src={FacebookIcon} />
                                </Avatar>
                            )}
                            <div className="text-sm">
                                <div>Tổng: {totalAccounts}</div>
                                <div className="text-green-600">Active: {activeAccounts}</div>
                                <div className="text-red-600">Disabled: {disabledAccounts}</div>
                            </div>
                        </div>
                    );
                },
            },
            {
                accessorKey: 'owner_name',
                header: t('business_manager.table.owner', { defaultValue: 'Người sở hữu tài khoản' }),
            },
            {
                accessorKey: 'total_spend',
                header: t('business_manager.table.spend', { defaultValue: 'Chi tiêu' }),
                cell: ({ row }) => {
                    const spend = parseFloat(row.original.total_spend || '0');
                    return (
                        <span className="font-medium">
                            {spend.toLocaleString('vi-VN', { 
                                minimumFractionDigits: 2, 
                                maximumFractionDigits: 2 
                            })} {row.original.accounts?.[0]?.currency || 'USD'}
                        </span>
                    );
                },
            },
            {
                accessorKey: 'total_balance',
                header: t('business_manager.table.balance', { defaultValue: 'Số dư' }),
                cell: ({ row }) => {
                    const balance = parseFloat(row.original.total_balance || '0');
                    return (
                        <span className="font-medium">
                            {balance.toLocaleString('vi-VN', { 
                                minimumFractionDigits: 2, 
                                maximumFractionDigits: 2 
                            })} {row.original.accounts?.[0]?.currency || 'USD'}
                        </span>
                    );
                },
            },
            {
                id: 'actions',
                header: t('common.action', { defaultValue: 'Hành động' }),
                cell: ({ row }) => {
                    return (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handleViewDetails(row.original)}
                        >
                            <Eye className="h-4 w-4 mr-1" />
                            {t('common.view', { defaultValue: 'Xem chi tiết' })}
                        </Button>
                    );
                },
            },
        ],
        [t]
    );

    const accountColumns: ColumnDef<any>[] = useMemo(
        () => [
            {
                accessorKey: 'account_name',
                header: t('business_manager.detail.acc_name', { defaultValue: 'Acc name' }),
            },
            {
                accessorKey: 'spend_cap',
                header: t('business_manager.detail.limit', { defaultValue: 'Limit' }),
                cell: ({ row }) => {
                    const limit = row.original.spend_cap;
                    return limit ? parseFloat(limit).toLocaleString('vi-VN') : '-';
                },
            },
            {
                accessorKey: 'amount_spent',
                header: t('business_manager.detail.spend', { defaultValue: 'Spend' }),
                cell: ({ row }) => {
                    const spend = parseFloat(row.original.amount_spent || '0');
                    return (
                        <span className="font-medium">
                            {spend.toLocaleString('vi-VN', { 
                                minimumFractionDigits: 2, 
                                maximumFractionDigits: 2 
                            })}
                        </span>
                    );
                },
            },
            {
                accessorKey: 'total_campaigns',
                header: t('business_manager.detail.total_campaign', { defaultValue: 'Total campaign' }),
                cell: ({ row }) => {
                    return row.original.total_campaigns || 0;
                },
            },
        ],
        [t]
    );

    return (
        <div>
            <h1 className="text-xl font-semibold mb-4">
                {t('business_manager.title', { defaultValue: 'Quản lý Business Manager / MCC' })}
            </h1>
            
            <BusinessManagerSearchForm />

            <Card className="mt-4">
                <CardHeader>
                    <CardTitle>
                        {t('business_manager.table_title', { defaultValue: 'Danh sách Business Manager / MCC' })}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <DataTable columns={columns} paginator={paginator} />
                </CardContent>
            </Card>

            {/* Dialog chi tiết BM/BCC */}
            <Dialog open={detailDialogOpen} onOpenChange={setDetailDialogOpen}>
                <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>
                            {t('business_manager.detail.title', { defaultValue: 'Chi tiết BM/BCC' })}: {selectedBM?.name}
                        </DialogTitle>
                        <DialogDescription>
                            {t('business_manager.detail.description', { defaultValue: 'Danh sách tài khoản quảng cáo' })}
                        </DialogDescription>
                    </DialogHeader>
                    
                    {loadingAccounts ? (
                        <div className="text-center py-8">
                            {t('common.loading', { defaultValue: 'Đang tải...' })}
                        </div>
                    ) : (
                        <div className="mt-4">
                            {accounts.length === 0 ? (
                                <div className="text-center py-8 text-muted-foreground">
                                    {t('business_manager.detail.no_accounts', { defaultValue: 'Chưa có tài khoản nào' })}
                                </div>
                            ) : (
                                <DataTable 
                                    columns={accountColumns} 
                                    paginator={{
                                        data: accounts,
                                        current_page: 1,
                                        last_page: 1,
                                        per_page: accounts.length,
                                        total: accounts.length,
                                    }} 
                                />
                            )}
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </div>
    );
};

BusinessManagerIndex.layout = (page: ReactNode) => (
    <AppLayout breadcrumbs={[{ title: 'business_manager.title' }]} children={page} />
);

export default BusinessManagerIndex;


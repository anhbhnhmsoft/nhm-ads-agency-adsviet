import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { _PlatformType } from '@/lib/types/constants';
import { cn } from '@/lib/utils';
import type { ServiceOrder } from '@/pages/service-order/types/type';
import axios from 'axios';
import {
    AlertCircle,
    CheckCircle2,
    CreditCard,
    Layers,
    Loader2,
    Plus,
    Search,
} from 'lucide-react';
import React, { useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';

interface BmAccountItem {
    id: string;
    account_id: string;
    account_name: string;
    account_status: number | null;
    currency: string;
    service_user_id: string | null;
    owner_name: string | null;
}

interface AssignAccountsDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    order: ServiceOrder | null;
    onSuccess: () => void;
}

export const AssignAccountsDialog: React.FC<AssignAccountsDialogProps> = ({
    open,
    onOpenChange,
    order,
    onSuccess,
}) => {
    if (!order) return null;

    const isMeta = order.package?.platform === _PlatformType.META;
    const isGoogle = order.package?.platform === _PlatformType.GOOGLE;

    // Lấy BM ID từ config đơn hàng nếu có
    const config = order.config_account || {};
    const orderBmId = useMemo(() => {
        if (config.bm_id) return String(config.bm_id);
        if (config.child_bm_id) return String(config.child_bm_id);
        const accs = order.assigned_accounts || [];
        for (const a of accs) {
            if (a.business_manager_id) return String(a.business_manager_id);
        }
        return '';
    }, [config, order.assigned_accounts]);

    const [activeTab, setActiveTab] = useState<'manual' | 'bm'>(
        orderBmId ? 'bm' : 'manual',
    );
    const [manualInput, setManualInput] = useState<string>('');
    const [bmAccounts, setBmAccounts] = useState<BmAccountItem[]>([]);
    const [loadingBmAccounts, setLoadingBmAccounts] = useState<boolean>(false);
    const [searchAccountQuery, setSearchAccountQuery] = useState<string>('');
    const [selectedBmAccountIds, setSelectedBmAccountIds] = useState<string[]>([]);
    const [submitting, setSubmitting] = useState<boolean>(false);

    // Danh sách account IDs đã gán trong đơn hiện tại để đánh dấu/loại trừ
    const existingOrderAccountIds = useMemo(() => {
        const set = new Set<string>();
        const accs = order.assigned_accounts || [];
        for (const a of accs) {
            if (a.account_id) {
                const clean = a.account_id.replace(/^act_/, '');
                set.add(clean);
                set.add(a.account_id);
                set.add(`act_${clean}`);
            }
        }
        return set;
    }, [order.assigned_accounts]);

    // Reset state khi mở dialog
    useEffect(() => {
        if (open) {
            setManualInput('');
            setSelectedBmAccountIds([]);
            setSearchAccountQuery('');
            if (orderBmId) {
                setActiveTab('bm');
                fetchBmAccounts(orderBmId);
            } else {
                setActiveTab('manual');
            }
        }
    }, [open, orderBmId]);

    const fetchBmAccounts = async (bmId: string) => {
        setLoadingBmAccounts(true);
        try {
            const platformParam = order.package?.platform;
            const res = await axios.get(`/business-managers/${bmId}/accounts`, {
                params: platformParam ? { platform: platformParam } : undefined,
            });
            if (res.data?.success && Array.isArray(res.data?.data)) {
                setBmAccounts(res.data.data);
            } else {
                setBmAccounts([]);
            }
        } catch (err) {
            console.error('Error fetching BM accounts:', err);
            setBmAccounts([]);
        } finally {
            setLoadingBmAccounts(false);
        }
    };

    // Lọc danh sách account trong BM theo từ khóa
    const filteredBmAccounts = useMemo(() => {
        let list = bmAccounts;
        if (searchAccountQuery.trim()) {
            const q = searchAccountQuery.toLowerCase().trim();
            list = list.filter((a) => {
                const name = (a.account_name || '').toLowerCase();
                const aid = (a.account_id || a.id || '').toLowerCase();
                return name.includes(q) || aid.includes(q);
            });
        }
        return list;
    }, [bmAccounts, searchAccountQuery]);

    // Parse danh sách Account ID từ ô nhập tay
    const parsedManualIds = useMemo(() => {
        return manualInput
            .split(/[\s,;\n]+/)
            .map((s) => s.trim())
            .filter((s) => s.length > 0);
    }, [manualInput]);

    const totalSelectedCount =
        activeTab === 'bm'
            ? selectedBmAccountIds.length
            : parsedManualIds.length;

    const handleToggleBmAccount = (accId: string) => {
        setSelectedBmAccountIds((prev) => {
            if (prev.includes(accId)) {
                return prev.filter((id) => id !== accId);
            } else {
                return [...prev, accId];
            }
        });
    };

    const handleSelectAllFilteredBm = () => {
        const selectableIds = filteredBmAccounts
            .filter((a) => !existingOrderAccountIds.has(a.account_id?.replace(/^act_/, '') || ''))
            .map((a) => a.account_id || a.id);
        
        const allSelected = selectableIds.every((id) => selectedBmAccountIds.includes(id));
        if (allSelected) {
            setSelectedBmAccountIds((prev) => prev.filter((id) => !selectableIds.includes(id)));
        } else {
            setSelectedBmAccountIds((prev) => Array.from(new Set([...prev, ...selectableIds])));
        }
    };

    const handleSubmit = async () => {
        const targetIds = activeTab === 'bm' ? selectedBmAccountIds : parsedManualIds;

        if (targetIds.length === 0) {
            toast.error('Vui lòng chọn hoặc nhập ít nhất 1 tài khoản quảng cáo để gán.');
            return;
        }

        setSubmitting(true);
        try {
            const response = await axios.post(`/service-orders/${order.id}/assign-accounts`, {
                account_ids: targetIds,
                bm_id: orderBmId || undefined,
            });

            if (response.data?.success) {
                toast.success(
                    response.data?.message ||
                        `Đã gán thành công ${targetIds.length} tài khoản vào đơn hàng!`,
                );
                onOpenChange(false);
                onSuccess();
            } else {
                toast.error(response.data?.message || 'Không thể gán tài khoản.');
            }
        } catch (err: any) {
            toast.error(
                err.response?.data?.message ||
                    'Có lỗi xảy ra khi gán tài khoản vào đơn hàng.',
            );
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-2xl sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2 text-lg font-semibold">
                        <Plus className="h-5 w-5 text-primary" />
                        <span>Gán thêm tài khoản quảng cáo</span>
                    </DialogTitle>
                    <DialogDescription className="text-xs text-muted-foreground">
                        Đơn hàng #{order.id} • Gói:{' '}
                        <span className="font-medium text-foreground">
                            {order.package?.name || '-'}
                        </span>
                    </DialogDescription>
                </DialogHeader>

                <Tabs
                    value={activeTab}
                    onValueChange={(val) => setActiveTab(val as 'manual' | 'bm')}
                    className="w-full"
                >
                    <TabsList className="grid w-full grid-cols-2">
                        <TabsTrigger value="bm" disabled={!orderBmId}>
                            Chọn từ BM ({orderBmId || 'Chưa có BM'})
                        </TabsTrigger>
                        <TabsTrigger value="manual">
                            Dán Account ID (Nhập tay)
                        </TabsTrigger>
                    </TabsList>

                    {/* TAB 1: CHỌN TỪ BM */}
                    <TabsContent value="bm" className="mt-4 space-y-3">
                        <div className="flex items-center gap-2">
                            <div className="relative flex-1">
                                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Tìm theo tên hoặc Account ID..."
                                    value={searchAccountQuery}
                                    onChange={(e) => setSearchAccountQuery(e.target.value)}
                                    className="pl-8 text-xs"
                                />
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={handleSelectAllFilteredBm}
                                disabled={filteredBmAccounts.length === 0}
                                className="h-9 shrink-0 text-xs"
                            >
                                Chọn tất cả ({filteredBmAccounts.length})
                            </Button>
                        </div>

                        <ScrollArea className="h-[280px] rounded-md border p-2">
                            {loadingBmAccounts ? (
                                <div className="flex h-40 flex-col items-center justify-center gap-2 text-xs text-muted-foreground">
                                    <Loader2 className="h-6 w-6 animate-spin text-primary" />
                                    <span>Đang tải danh sách tài khoản từ BM...</span>
                                </div>
                            ) : filteredBmAccounts.length === 0 ? (
                                <div className="flex h-40 flex-col items-center justify-center gap-1 text-center text-xs text-muted-foreground">
                                    <AlertCircle className="h-6 w-6 text-muted-foreground/60" />
                                    <span>Không tìm thấy tài khoản nào trong BM này.</span>
                                </div>
                            ) : (
                                <div className="space-y-1.5">
                                    {filteredBmAccounts.map((account) => {
                                        const cleanId = (account.account_id || account.id || '').replace(/^act_/, '');
                                        const isAlreadyAssignedInOrder = existingOrderAccountIds.has(cleanId);
                                        const isChecked = selectedBmAccountIds.includes(account.account_id || account.id);
                                        const hasOtherCustomer = account.owner_name && !isAlreadyAssignedInOrder;

                                        return (
                                            <div
                                                key={account.id || account.account_id}
                                                onClick={() => {
                                                    if (!isAlreadyAssignedInOrder) {
                                                        handleToggleBmAccount(account.account_id || account.id);
                                                    }
                                                }}
                                                className={cn(
                                                    'flex cursor-pointer items-center justify-between gap-3 rounded-lg border p-2.5 transition-colors text-xs',
                                                    isChecked
                                                        ? 'border-primary/50 bg-primary/5'
                                                        : 'hover:bg-accent/40',
                                                    isAlreadyAssignedInOrder &&
                                                        'cursor-not-allowed opacity-50 bg-muted/30',
                                                )}
                                            >
                                                <div className="flex items-center gap-2.5 overflow-hidden">
                                                    <Checkbox
                                                        checked={isChecked || isAlreadyAssignedInOrder}
                                                        disabled={isAlreadyAssignedInOrder}
                                                        onCheckedChange={() =>
                                                            handleToggleBmAccount(account.account_id || account.id)
                                                        }
                                                    />
                                                    <div className="min-w-0">
                                                        <div className="flex items-center gap-2">
                                                            <span className="font-semibold text-foreground truncate">
                                                                {account.account_name || 'Tài khoản không tên'}
                                                            </span>
                                                            {isAlreadyAssignedInOrder && (
                                                                <Badge
                                                                    variant="secondary"
                                                                    className="text-[10px] py-0 px-1.5 bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                                                                >
                                                                    Đã có trong đơn
                                                                </Badge>
                                                            )}
                                                            {hasOtherCustomer && (
                                                                <Badge
                                                                    variant="destructive"
                                                                    className="text-[10px] py-0 px-1.5"
                                                                >
                                                                    Gán cho {account.owner_name}
                                                                </Badge>
                                                            )}
                                                        </div>
                                                        <div className="mt-0.5 flex items-center gap-2 font-mono text-[11px] text-muted-foreground">
                                                            <span>{account.account_id || account.id}</span>
                                                            {account.currency && (
                                                                <span>• {account.currency}</span>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </ScrollArea>
                    </TabsContent>

                    {/* TAB 2: NHẬP TAY / DÁN HÀNG LOẠT */}
                    <TabsContent value="manual" className="mt-4 space-y-3">
                        <div>
                            <Label className="text-xs font-semibold text-foreground">
                                Danh sách Account ID (Dán một hoặc nhiều ID):
                            </Label>
                            <p className="mt-0.5 text-[11px] text-muted-foreground">
                                {isMeta
                                    ? 'Nhập Account ID (ví dụ: act_1748534139775713 hoặc 1748534139775713), cách nhau bằng dấu phẩy, khoảng trắng hoặc xuống dòng.'
                                    : 'Nhập Customer ID của Google Ads (ví dụ: 123-456-7890 hoặc 1234567890).'}
                            </p>
                        </div>

                        <Textarea
                            rows={6}
                            value={manualInput}
                            onChange={(e) => setManualInput(e.target.value)}
                            placeholder={
                                isMeta
                                    ? 'act_1748534139775713\nact_1513219153895893\nact_1994149967934653'
                                    : '123-456-7890\n987-654-3210'
                            }
                            className="font-mono text-xs leading-relaxed"
                        />

                        {parsedManualIds.length > 0 && (
                            <div className="flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground">
                                <span>Đã nhận diện:</span>
                                <Badge variant="secondary" className="font-semibold text-foreground">
                                    {parsedManualIds.length} tài khoản
                                </Badge>
                            </div>
                        )}
                    </TabsContent>
                </Tabs>

                <DialogFooter className="mt-4 flex items-center justify-between gap-2 border-t pt-3 sm:justify-between">
                    <div className="text-xs text-muted-foreground">
                        Đã chọn:{' '}
                        <span className="font-semibold text-primary">
                            {totalSelectedCount} tài khoản
                        </span>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                            disabled={submitting}
                            className="h-8 text-xs"
                        >
                            Hủy
                        </Button>
                        <Button
                            type="button"
                            onClick={handleSubmit}
                            disabled={submitting || totalSelectedCount === 0}
                            className="h-8 text-xs"
                        >
                            {submitting ? (
                                <>
                                    <Loader2 className="mr-1 h-3.5 w-3.5 animate-spin" />
                                    <span>Đang gán...</span>
                                </>
                            ) : (
                                <>
                                    <Plus className="mr-1 h-3.5 w-3.5" />
                                    <span>Gán {totalSelectedCount > 0 ? `(${totalSelectedCount})` : ''}</span>
                                </>
                            )}
                        </Button>
                    </div>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
};

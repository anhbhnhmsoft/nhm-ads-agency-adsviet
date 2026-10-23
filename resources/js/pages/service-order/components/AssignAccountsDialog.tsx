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
    is_assigned?: boolean;
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

    // Chế độ Gán bảo hành (Đổi tài khoản die)
    const [isWarranty, setIsWarranty] = useState<boolean>(false);
    const [replaceAccountId, setReplaceAccountId] = useState<string>('');

    // Tùy chọn Quét toàn bộ (30 ngày) hoặc Bắt đầu quét từ hôm nay (2 ngày)
    const [isDeepSync, setIsDeepSync] = useState<boolean>(false);

    // Danh sách tài khoản hiện tại của đơn hàng để chọn bảo hành
    const currentOrderAccounts = useMemo(() => {
        const assigned = order.assigned_accounts || [];
        if (assigned.length > 0) {
            return assigned.map((a) => ({
                id: a.account_id || a.id,
                name: a.account_name ? `${a.account_name} (${a.account_id})` : (a.account_id || 'Tài khoản'),
            }));
        }
        const cfgAccs = order.config_account?.accounts || [];
        return cfgAccs.map((a: any, idx: number) => {
            const bm = Array.isArray(a.bm_ids) && a.bm_ids.length > 0 ? a.bm_ids[0] : '';
            const id = a.account_id || bm || `acc_${idx}`;
            const name = a.display_name ? `${a.display_name} (${id})` : (id || `Tài khoản #${idx + 1}`);
            return { id, name };
        });
    }, [order]);

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
        const cfgAccs = order.config_account?.accounts || [];
        for (const a of cfgAccs) {
            const rawId = (a as any).account_id;
            if (rawId) {
                const clean = String(rawId).replace(/^act_/, '');
                set.add(clean);
                set.add(rawId);
                set.add(`act_${clean}`);
            }
        }
        return set;
    }, [order.assigned_accounts, order.config_account]);

    // Reset state khi mở dialog
    useEffect(() => {
        if (open) {
            setManualInput('');
            setSelectedBmAccountIds([]);
            setSearchAccountQuery('');
            setIsWarranty(false);
            setReplaceAccountId('');
            setIsDeepSync(false);
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

    // Chọn tất cả: CHỈ CHỌN CÁC TÀI KHOẢN CHƯA GÁN (không bị gán cho khách khác và chưa có trong đơn)
    const handleSelectAllFilteredBm = () => {
        const selectableIds = filteredBmAccounts
            .filter((a) => {
                const clean = (a.account_id || a.id || '').replace(/^act_/, '');
                const isAlreadyInOrder = existingOrderAccountIds.has(clean);
                const isAssignedToOther = (a.is_assigned || !!a.service_user_id) && String(a.service_user_id) !== String(order.id);
                return !isAlreadyInOrder && !isAssignedToOther;
            })
            .map((a) => a.account_id || a.id);
        
        const allSelected = selectableIds.length > 0 && selectableIds.every((id) => selectedBmAccountIds.includes(id));
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
                is_warranty: isWarranty,
                replace_account_id: isWarranty && replaceAccountId ? replaceAccountId : undefined,
                sync_all: isDeepSync,
                sync_days: isDeepSync ? 30 : 2,
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
            <DialogContent className="max-w-2xl sm:max-w-2xl max-h-[90vh] flex flex-col p-6">
                <DialogHeader className="shrink-0">
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

                {/* BẢO HÀNH TOGGLE & SELECTION */}
                <div className="rounded-lg border bg-amber-50/60 dark:bg-amber-950/20 p-3 space-y-2 border-amber-200 dark:border-amber-900/50">
                    <div className="flex items-center justify-between">
                        <label
                            htmlFor="warranty-toggle"
                            className="flex items-center gap-2 text-xs font-semibold text-amber-900 dark:text-amber-200 cursor-pointer"
                        >
                            <Checkbox
                                id="warranty-toggle"
                                checked={isWarranty}
                                onCheckedChange={(val) => setIsWarranty(!!val)}
                            />
                            <span>🛡️ Gán bảo hành (Không tính phí mở tài khoản 0$)</span>
                        </label>
                        {isWarranty && (
                            <Badge variant="secondary" className="bg-amber-100 text-amber-900 text-[10px] px-2 py-0.5 border-amber-300">
                                Miễn phí mở tài khoản 0$
                            </Badge>
                        )}
                    </div>

                    {isWarranty && (
                        <div className="space-y-1.5 pt-1 border-t border-amber-200/60 dark:border-amber-900/40">
                            <Label className="text-[11px] text-amber-800 dark:text-amber-300">
                                Thay thế tài khoản cũ bị die (Tùy chọn):
                            </Label>
                            <select
                                value={replaceAccountId}
                                onChange={(e) => setReplaceAccountId(e.target.value)}
                                className="w-full text-xs rounded-md border border-amber-300 bg-background px-2.5 py-1.5 focus:outline-none focus:ring-1 focus:ring-amber-500"
                            >
                                <option value="">-- Không thay thế tài khoản cụ thể (Chỉ gán thêm bảo hành) --</option>
                                {currentOrderAccounts.map((acc) => (
                                    <option key={acc.id} value={acc.id}>
                                        {acc.name}
                                    </option>
                                ))}
                            </select>
                            <p className="text-[11px] text-amber-700 dark:text-amber-400">
                                💡 Hệ thống cam kết <strong>không tính phí mở tài khoản (0$)</strong>, bảo toàn lịch sử mốc chi tiêu đã thu của tài khoản cũ và chỉ tính phí chi tiêu phát sinh mới.
                            </p>
                        </div>
                    )}
                </div>

                {/* TÙY CHỌN QUÉT DỮ LIỆU KHI GÁN */}
                <div className="rounded-lg border bg-slate-50/70 dark:bg-slate-900/40 p-3 space-y-1.5 border-slate-200 dark:border-slate-800">
                    <div className="flex items-center justify-between">
                        <label
                            htmlFor="sync-deep-toggle"
                            className="flex items-center gap-2 text-xs font-semibold text-slate-800 dark:text-slate-200 cursor-pointer"
                        >
                            <Checkbox
                                id="sync-deep-toggle"
                                checked={isDeepSync}
                                onCheckedChange={(val) => setIsDeepSync(!!val)}
                            />
                            <span>🔄 Quét toàn bộ lịch sử 30 ngày ngay lập tức (Deep Sync)</span>
                        </label>
                        <Badge variant="outline" className="text-[10px] bg-background">
                            {isDeepSync ? 'Deep Sync (30 ngày)' : 'Bắt đầu quét từ hôm nay (Delta)'}
                        </Badge>
                    </div>
                    <p className="text-[11px] text-muted-foreground pl-6">
                        {isDeepSync
                            ? '⚡ Kích hoạt cào sâu toàn bộ 30 ngày chiến dịch & số liệu của tài khoản/BM này để lên báo cáo ngay.'
                            : '💡 Mặc định: Bắt đầu quét từ hôm nay (chỉ quét hôm nay & hôm qua): nhanh, nhẹ và chống rate limit. Lịch sử cũ sẽ được tự động cào bù vào lúc 02:00 sáng.'}
                    </p>
                </div>

                <Tabs
                    value={activeTab}
                    onValueChange={(val) => setActiveTab(val as 'manual' | 'bm')}
                    className="w-full flex-1 flex flex-col min-h-0"
                >
                    <TabsList className="grid w-full grid-cols-2 shrink-0">
                        <TabsTrigger value="bm" disabled={!orderBmId}>
                            Chọn từ BM ({orderBmId || 'Chưa có BM'})
                        </TabsTrigger>
                        <TabsTrigger value="manual">
                            Dán Account ID (Nhập tay)
                        </TabsTrigger>
                    </TabsList>

                    {/* TAB 1: CHỌN TỪ BM */}
                    <TabsContent value="bm" className="mt-3 space-y-3 flex-1 flex flex-col min-h-0">
                        <div className="flex items-center gap-2 shrink-0">
                            <div className="relative flex-1">
                                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Tìm theo tên hoặc Account ID..."
                                    value={searchAccountQuery}
                                    onChange={(e) => setSearchAccountQuery(e.target.value)}
                                    className="pl-8 text-xs h-9"
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
                                Chọn tất cả khả dụng
                            </Button>
                        </div>

                        <ScrollArea className="h-[260px] rounded-md border p-2">
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
                                        const isAssignedToOther = (account.is_assigned || !!account.service_user_id) && String(account.service_user_id) !== String(order.id);
                                        const isDisabled = isAlreadyAssignedInOrder || isAssignedToOther;
                                        const isChecked = selectedBmAccountIds.includes(account.account_id || account.id);

                                        return (
                                            <div
                                                key={account.id || account.account_id}
                                                onClick={() => {
                                                    if (!isDisabled) {
                                                        handleToggleBmAccount(account.account_id || account.id);
                                                    }
                                                }}
                                                className={cn(
                                                    'flex items-center justify-between gap-3 rounded-lg border p-2.5 transition-colors text-xs',
                                                    isChecked
                                                        ? 'border-primary/50 bg-primary/5'
                                                        : 'hover:bg-accent/40',
                                                    isDisabled
                                                        ? 'cursor-not-allowed opacity-60 bg-muted/40'
                                                        : 'cursor-pointer',
                                                )}
                                            >
                                                <div className="flex items-center gap-2.5 overflow-hidden">
                                                    <Checkbox
                                                        checked={isChecked || isAlreadyAssignedInOrder}
                                                        disabled={isDisabled}
                                                        onCheckedChange={() => {
                                                            if (!isDisabled) {
                                                                handleToggleBmAccount(account.account_id || account.id);
                                                            }
                                                        }}
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
                                                            {isAssignedToOther && (
                                                                <Badge
                                                                    variant="destructive"
                                                                    className="text-[10px] py-0 px-1.5 font-normal"
                                                                >
                                                                    Đã gán{account.owner_name ? ` (${account.owner_name})` : ''}
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

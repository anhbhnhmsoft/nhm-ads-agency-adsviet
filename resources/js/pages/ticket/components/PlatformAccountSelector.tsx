import { useTranslation } from 'react-i18next';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useMemo } from 'react';
import { _PlatformType } from '@/lib/types/constants';

export type AccountOption = {
    id: string;
    account_id: string;
    account_name: string;
    platform: number;
};

type PlatformAccountSelectorProps = {
    accounts: AccountOption[];
    selectedPlatform: string;
    selectedAccountId: string;
    onPlatformChange: (platform: string) => void;
    onAccountChange: (accountId: string) => void;
    platformError?: string;
    accountError?: string;
    accountLabel?: string;
    accountPlaceholder?: string;
    disabled?: boolean;
};

export const PlatformAccountSelector = ({
    accounts,
    selectedPlatform,
    selectedAccountId,
    onPlatformChange,
    onAccountChange,
    platformError,
    accountError,
    accountLabel,
    accountPlaceholder,
    disabled = false,
}: PlatformAccountSelectorProps) => {
    const { t } = useTranslation();

    const filteredAccounts = useMemo(() => {
        if (!selectedPlatform) {
            return [];
        }
        const platformNum = parseInt(selectedPlatform);
        return accounts.filter(acc => acc.platform === platformNum);
    }, [accounts, selectedPlatform]);

    return (
        <>
            <div className="space-y-2">
                <Label htmlFor="platform">
                    {t('ticket.transfer.platform', { defaultValue: 'Kênh quảng cáo' })}
                    <span className="text-red-500">*</span>
                </Label>
                <Select
                    value={selectedPlatform}
                    onValueChange={onPlatformChange}
                    disabled={disabled}
                >
                    <SelectTrigger id="platform">
                        <SelectValue placeholder={t('ticket.transfer.select_platform', { defaultValue: 'Chọn kênh quảng cáo' })} />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={String(_PlatformType.GOOGLE)}>
                            {t('enum.platform_type.google', { defaultValue: 'Google Ads' })}
                        </SelectItem>
                        <SelectItem value={String(_PlatformType.META)}>
                            {t('enum.platform_type.meta', { defaultValue: 'Meta Ads' })}
                        </SelectItem>
                    </SelectContent>
                </Select>
                {platformError && (
                    <p className="text-sm text-red-500">{platformError}</p>
                )}
            </div>

            {selectedPlatform && (
                <div className="space-y-2">
                    <Label htmlFor="account_id">
                        {accountLabel || t('ticket.transfer.select_account', { defaultValue: 'Chọn tài khoản' })}
                        <span className="text-red-500">*</span>
                    </Label>
                    <Select
                        value={selectedAccountId}
                        onValueChange={onAccountChange}
                        disabled={disabled || !selectedPlatform || filteredAccounts.length === 0}
                    >
                        <SelectTrigger id="account_id">
                            <SelectValue placeholder={accountPlaceholder || t('ticket.transfer.select_account', { defaultValue: 'Chọn tài khoản' })} />
                        </SelectTrigger>
                        <SelectContent>
                            {filteredAccounts.map((account) => (
                                <SelectItem key={account.id} value={account.account_id}>
                                    {account.account_name} ({account.account_id})
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {accountError && (
                        <p className="text-sm text-red-500">{accountError}</p>
                    )}
                    {selectedPlatform && filteredAccounts.length === 0 && (
                        <p className="text-sm text-yellow-600">
                            {t('ticket.transfer.no_accounts', { defaultValue: 'Không có tài khoản nào cho kênh quảng cáo này' })}
                        </p>
                    )}
                </div>
            )}
        </>
    );
};


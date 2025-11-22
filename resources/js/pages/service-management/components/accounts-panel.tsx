import { ScrollArea } from '@/components/ui/scroll-area';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Loader2, Radio } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import type { MetaAccount } from '@/pages/service-management/types/types';

type Props = {
    accounts: MetaAccount[];
    loading: boolean;
    error: string | null;
    selectedAccountId: string | null;
    loadingAccountId: string | null;
    onSelectAccount: (account: MetaAccount) => void;
};

const AccountsPanel = ({
    accounts,
    loading,
    error,
    selectedAccountId,
    loadingAccountId,
    onSelectAccount,
}: Props) => {
    const { t } = useTranslation();

    return (
        <div className="space-y-3">
            <h3 className="font-semibold flex items-center gap-2">
                <Radio className="h-4 w-4 text-primary" />
                {t('service_management.accounts')}
            </h3>
            {error && (
                <Alert variant="destructive">
                    <AlertTitle>{t('service_management.accounts_error_title')}</AlertTitle>
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}
            <ScrollArea className="h-64 rounded border p-2">
                {loading ? (
                    <div className="flex items-center justify-center py-10 text-muted-foreground">
                        <Loader2 className="h-4 w-4 animate-spin mr-2" />
                        {t('service_management.loading')}
                    </div>
                ) : accounts.length === 0 ? (
                    <p className="text-sm text-muted-foreground">{t('service_management.accounts_empty')}</p>
                ) : (
                    <div className="space-y-3">
                        {accounts.map((account) => (
                            <div
                                key={account.id}
                                className={`rounded border p-3 ${
                                    selectedAccountId === account.id ? 'border-primary' : ''
                                }`}
                            >
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="font-semibold">{account.account_name || account.account_id}</p>
                                        <p className="text-xs text-muted-foreground">ID: {account.account_id}</p>
                                    </div>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => onSelectAccount(account)}
                                        disabled={loadingAccountId === account.id}
                                    >
                                        {loadingAccountId === account.id && (
                                            <Loader2 className="h-4 w-4 animate-spin mr-2" />
                                        )}
                                        {t('service_management.load_campaigns')}
                                    </Button>
                                </div>
                                <div className="text-xs text-muted-foreground mt-2 space-y-1">
                                    {account.currency && (
                                        <div>
                                            {t('service_management.currency')}: {account.currency}
                                        </div>
                                    )}
                                    {account.balance && (
                                        <div>
                                            {t('service_management.balance')}: {account.balance}
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </ScrollArea>
        </div>
    );
};

export default AccountsPanel;


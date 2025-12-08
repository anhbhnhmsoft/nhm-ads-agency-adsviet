import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle } from 'lucide-react';
import type { TransferPageProps } from './types/type';
import { TransferTabs } from './transfer/components/TransferTabs';
import { TransferForm } from './transfer/components/TransferForm';
import { TransferList } from './transfer/components/TransferList';

const TransferIndex = ({ tickets, accounts, error }: TransferPageProps) => {
    const { t } = useTranslation();
    const [activeTab, setActiveTab] = useState<'create' | 'list'>('create');

    return (
        <div>
            <Head title={t('ticket.transfer.title', { defaultValue: 'Chuyển tiền' })} />
            
            <div className="mb-4">
                <h1 className="text-2xl font-semibold">
                    {t('ticket.transfer.title', { defaultValue: 'Chuyển tiền giữa các tài khoản' })}
                </h1>
            </div>

            <TransferTabs activeTab={activeTab} onTabChange={setActiveTab} />

            {error && (
                <Alert variant="destructive" className="mb-4">
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}

            {activeTab === 'create' && <TransferForm accounts={accounts} />}
            {activeTab === 'list' && <TransferList tickets={tickets} />}
        </div>
    );
};

TransferIndex.layout = (page: React.ReactNode) => (
    <AppLayout breadcrumbs={[{ title: 'ticket.transfer.title' }]} children={page} />
);

export default TransferIndex;


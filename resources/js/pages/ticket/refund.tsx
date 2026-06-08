import { Alert, AlertDescription } from '@/components/ui/alert';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { AlertCircle } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { RefundForm } from './refund/components/RefundForm';
import { RefundList } from './refund/components/RefundList';
import { RefundTabs } from './refund/components/RefundTabs';
import type { RefundPageProps } from './refund/types/type';

const RefundIndex = ({ tickets, accounts, error }: RefundPageProps) => {
    const { t } = useTranslation();
    const [activeTab, setActiveTab] = useState<'create' | 'list'>('create');

    return (
        <div>
            <Head
                title={t('ticket.refund.title', {
                    defaultValue: 'Thanh lý tài khoản',
                })}
            />

            <div className="mb-4">
                <h1 className="text-2xl font-semibold">
                    {t('ticket.refund.title', {
                        defaultValue: 'Thanh lý tài khoản',
                    })}
                </h1>
            </div>

            <RefundTabs activeTab={activeTab} onTabChange={setActiveTab} />

            {error && (
                <Alert variant="destructive" className="mb-4">
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}

            {activeTab === 'create' && <RefundForm accounts={accounts} />}
            {activeTab === 'list' && <RefundList tickets={tickets} />}
        </div>
    );
};

RefundIndex.layout = (page: React.ReactNode) => (
    <AppLayout
        breadcrumbs={[{ title: 'ticket.refund.title' }]}
        children={page}
    />
);

export default RefundIndex;

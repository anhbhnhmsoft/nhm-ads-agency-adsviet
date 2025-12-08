import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle } from 'lucide-react';
import type { AppealPageProps } from './appeal/types/type';
import { AppealTabs } from './appeal/components/AppealTabs';
import { AppealForm } from './appeal/components/AppealForm';
import { AppealList } from './appeal/components/AppealList';

const AppealIndex = ({ tickets, accounts, adminEmail, error }: AppealPageProps) => {
    const { t } = useTranslation();
    const [activeTab, setActiveTab] = useState<'create' | 'list'>('create');

    return (
        <div>
            <Head title={t('ticket.appeal.title', { defaultValue: 'Kháng tài khoản' })} />
            
            <div className="mb-4">
                <h1 className="text-2xl font-semibold">
                    {t('ticket.appeal.title', { defaultValue: 'Kháng tài khoản' })}
                </h1>
            </div>

            <AppealTabs activeTab={activeTab} onTabChange={setActiveTab} />

            {error && (
                <Alert variant="destructive" className="mb-4">
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}

            {activeTab === 'create' && <AppealForm accounts={accounts} adminEmail={adminEmail} />}
            {activeTab === 'list' && <AppealList tickets={tickets} />}
        </div>
    );
};

AppealIndex.layout = (page: React.ReactNode) => (
    <AppLayout breadcrumbs={[{ title: 'ticket.appeal.title' }]} children={page} />
);

export default AppealIndex;


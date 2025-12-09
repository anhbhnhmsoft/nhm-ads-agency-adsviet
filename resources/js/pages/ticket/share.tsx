import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle } from 'lucide-react';
import type { SharePageProps } from './share/types/type';
import { ShareTabs } from './share/components/ShareTabs';
import { ShareForm } from './share/components/ShareForm';
import { ShareList } from './share/components/ShareList';

const ShareIndex = ({ tickets, accounts, error }: SharePageProps) => {
    const { t } = useTranslation();
    const [activeTab, setActiveTab] = useState<'create' | 'list'>('create');

    return (
        <div>
            <Head title={t('ticket.share.title', { defaultValue: 'Share BM/BC/MCC' })} />
            
            <div className="mb-4">
                <h1 className="text-2xl font-semibold">
                    {t('ticket.share.title', { defaultValue: 'Share BM/BC/MCC' })}
                </h1>
            </div>

            <ShareTabs activeTab={activeTab} onTabChange={setActiveTab} />

            {error && (
                <Alert variant="destructive" className="mb-4">
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}

            {activeTab === 'create' && <ShareForm accounts={accounts} />}
            {activeTab === 'list' && <ShareList tickets={tickets} />}
        </div>
    );
};

ShareIndex.layout = (page: React.ReactNode) => (
    <AppLayout breadcrumbs={[{ title: 'ticket.share.title' }]} children={page} />
);

export default ShareIndex;


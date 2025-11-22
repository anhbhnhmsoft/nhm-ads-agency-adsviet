import { ScrollArea } from '@/components/ui/scroll-area';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Loader2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import type { MetaCampaign } from '@/pages/service-management/types/types';

type Props = {
    campaigns: MetaCampaign[];
    loading: boolean;
    error: string | null;
    selectedAccountId: string | null;
};

const CampaignsPanel = ({ campaigns, loading, error, selectedAccountId }: Props) => {
    const { t } = useTranslation();

    if (!selectedAccountId) {
        return (
            <div className="text-sm text-muted-foreground border rounded p-4">
                {t('service_management.select_account_hint')}
            </div>
        );
    }

    return (
        <div className="space-y-3">
            <h3 className="font-semibold">{t('service_management.campaigns')}</h3>
            {error && (
                <Alert variant="destructive">
                    <AlertTitle>{t('service_management.campaigns_error_title')}</AlertTitle>
                    <AlertDescription>{error}</AlertDescription>
                </Alert>
            )}
            <ScrollArea className="h-64 border rounded p-2">
                {loading ? (
                    <div className="flex items-center justify-center py-10 text-muted-foreground">
                        <Loader2 className="h-4 w-4 animate-spin mr-2" />
                        {t('service_management.loading')}
                    </div>
                ) : campaigns.length === 0 ? (
                    <p className="text-sm text-muted-foreground">{t('service_management.campaigns_empty')}</p>
                ) : (
                    <div className="space-y-3">
                        {campaigns.map((campaign) => (
                            <div key={campaign.id} className="rounded border p-3 space-y-2">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="font-semibold">{campaign.name || campaign.campaign_id}</p>
                                        <p className="text-xs text-muted-foreground">
                                            ID: {campaign.campaign_id}
                                        </p>
                                    </div>
                                    <Badge variant="outline">{campaign.effective_status}</Badge>
                                </div>
                                <div className="text-xs text-muted-foreground space-y-1">
                                    {campaign.objective && (
                                        <div>
                                            {t('service_management.objective')}: {campaign.objective}
                                        </div>
                                    )}
                                    {campaign.daily_budget && (
                                        <div>
                                            {t('service_management.daily_budget')}: {campaign.daily_budget}
                                        </div>
                                    )}
                                    <div>
                                        {t('service_management.start_time')}:{' '}
                                        {campaign.start_time
                                            ? new Date(campaign.start_time).toLocaleString()
                                            : '--'}
                                    </div>
                                    {campaign.stop_time && (
                                        <div>
                                            {t('service_management.stop_time')}:{' '}
                                            {new Date(campaign.stop_time).toLocaleString()}
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

export default CampaignsPanel;


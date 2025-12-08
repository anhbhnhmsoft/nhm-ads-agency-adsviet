import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useTranslation } from 'react-i18next';

type ShareTabsProps = {
    activeTab: 'create' | 'list';
    onTabChange: (tab: 'create' | 'list') => void;
};

export const ShareTabs = ({ activeTab, onTabChange }: ShareTabsProps) => {
    const { t } = useTranslation();

    return (
        <Tabs value={activeTab} onValueChange={(value) => onTabChange(value as 'create' | 'list')} className="mb-6">
            <TabsList>
                <TabsTrigger value="create">
                    {t('ticket.share.create_request', { defaultValue: 'Tạo yêu cầu' })}
                </TabsTrigger>
                <TabsTrigger value="list">
                    {t('ticket.share.in_progress', { defaultValue: 'Đang thực hiện' })}
                </TabsTrigger>
            </TabsList>
        </Tabs>
    );
};


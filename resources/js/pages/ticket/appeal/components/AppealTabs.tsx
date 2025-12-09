import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useTranslation } from 'react-i18next';

type AppealTabsProps = {
    activeTab: 'create' | 'list';
    onTabChange: (tab: 'create' | 'list') => void;
};

export const AppealTabs = ({ activeTab, onTabChange }: AppealTabsProps) => {
    const { t } = useTranslation();

    return (
        <Tabs value={activeTab} onValueChange={(value) => onTabChange(value as 'create' | 'list')} className="mb-6">
            <TabsList>
                <TabsTrigger value="create">
                    {t('ticket.appeal.create_request', { defaultValue: 'Tạo yêu cầu' })}
                </TabsTrigger>
                <TabsTrigger value="list">
                    {t('ticket.appeal.in_progress', { defaultValue: 'Đang thực hiện' })}
                </TabsTrigger>
            </TabsList>
        </Tabs>
    );
};


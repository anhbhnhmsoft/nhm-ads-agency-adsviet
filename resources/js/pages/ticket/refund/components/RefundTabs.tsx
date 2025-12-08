import { useTranslation } from 'react-i18next';

type RefundTabsProps = {
    activeTab: 'create' | 'list';
    onTabChange: (tab: 'create' | 'list') => void;
};

export const RefundTabs = ({ activeTab, onTabChange }: RefundTabsProps) => {
    const { t } = useTranslation();

    return (
        <div className="mb-4 flex gap-4 border-b">
            <button
                onClick={() => onTabChange('create')}
                className={`px-4 py-2 font-medium ${
                    activeTab === 'create'
                        ? 'border-b-2 border-primary text-primary'
                        : 'text-muted-foreground'
                }`}
            >
                {t('ticket.refund.create_request', { defaultValue: 'Tạo yêu cầu' })}
            </button>
            <button
                onClick={() => onTabChange('list')}
                className={`px-4 py-2 font-medium ${
                    activeTab === 'list'
                        ? 'border-b-2 border-primary text-primary'
                        : 'text-muted-foreground'
                }`}
            >
                {t('ticket.refund.in_progress', { defaultValue: 'Đang thực hiện' })}
            </button>
        </div>
    );
};


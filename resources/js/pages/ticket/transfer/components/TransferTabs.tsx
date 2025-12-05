import { useTranslation } from 'react-i18next';

type TransferTabsProps = {
    activeTab: 'create' | 'list';
    onTabChange: (tab: 'create' | 'list') => void;
};

export const TransferTabs = ({ activeTab, onTabChange }: TransferTabsProps) => {
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
                {t('ticket.transfer.create_request', { defaultValue: 'Tạo yêu cầu' })}
            </button>
            <button
                onClick={() => onTabChange('list')}
                className={`px-4 py-2 font-medium ${
                    activeTab === 'list'
                        ? 'border-b-2 border-primary text-primary'
                        : 'text-muted-foreground'
                }`}
            >
                {t('ticket.transfer.in_progress', { defaultValue: 'Đang thực hiện' })}
            </button>
        </div>
    );
};


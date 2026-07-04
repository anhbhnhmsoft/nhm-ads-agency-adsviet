import AppLayout from '@/layouts/app-layout';
import DashboardGuideCard from '@/pages/wallet/components/DashboardGuideCard';
import { Head } from '@inertiajs/react';
import { BookOpen } from 'lucide-react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

type Props = {
    content?: string | null;
};

const DashboardGuideIndex = ({ content = '' }: Props) => {
    const { t } = useTranslation();

    return (
        <div className="space-y-6">
            <Head
                title={t('dashboard_guide.title', {
                    defaultValue: 'Hướng dẫn sử dụng Dashboard',
                })}
            />
            <div>
                <h1 className="text-xl font-semibold">
                    {t('dashboard_guide.title', {
                        defaultValue: 'Hướng dẫn sử dụng Dashboard',
                    })}
                </h1>
                <p className="text-sm text-muted-foreground">
                    {t('dashboard_guide.customer_description', {
                        defaultValue:
                            'Xem hướng dẫn sử dụng hệ thống và các thao tác quan trọng.',
                    })}
                </p>
            </div>

            <DashboardGuideCard t={t} content={content} />

            {!content && (
                <div className="rounded-md border border-dashed p-6 text-sm text-muted-foreground">
                    <BookOpen className="mb-3 h-5 w-5" />
                    {t('dashboard_guide.empty_customer', {
                        defaultValue: 'Chưa có nội dung hướng dẫn.',
                    })}
                </div>
            )}
        </div>
    );
};

DashboardGuideIndex.layout = (page: ReactNode) => (
    <AppLayout
        breadcrumbs={[{ title: 'menu.dashboard_guide' }]}
        children={page}
    />
);

export default DashboardGuideIndex;

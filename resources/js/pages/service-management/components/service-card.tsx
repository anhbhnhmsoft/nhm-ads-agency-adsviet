import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { ServiceOrder } from '@/pages/service-order/types/type';
import { useTranslation } from 'react-i18next';
import type { ReactNode } from 'react';

type Props = {
    service: ServiceOrder;
    onView: (service: ServiceOrder) => void;
    renderConfigInfo: (service: ServiceOrder) => ReactNode;
};

const ServiceCard = ({ service, onView, renderConfigInfo }: Props) => {
    const { t } = useTranslation();
    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between">
                <div>
                    <CardTitle className="text-lg">{service.package?.name || 'Service'}</CardTitle>
                    <CardDescription>
                        {t('service_management.created_at', {
                            date: service.created_at ? new Date(service.created_at).toLocaleString() : '--',
                        })}
                    </CardDescription>
                </div>
                <Badge variant="secondary">{service.package?.platform_label}</Badge>
            </CardHeader>
            <CardContent className="space-y-4">
                {renderConfigInfo(service)}
                <div className="flex flex-wrap gap-3 text-sm text-muted-foreground">
                    <div>
                        <span className="font-medium text-foreground">{t('service_management.total_budget')}:</span>{' '}
                        {service.budget || 0} USDT
                    </div>
                    <div>
                        <span className="font-medium text-foreground">{t('service_management.topup_fee')}:</span>{' '}
                        {service.top_up_fee ?? 0}%
                    </div>
                </div>
                <Button onClick={() => onView(service)}>{t('service_management.view_campaigns')}</Button>
            </CardContent>
        </Card>
    );
};

export default ServiceCard;


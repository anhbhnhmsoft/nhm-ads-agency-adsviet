import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { sanitizeDashboardGuideHtml } from '@/lib/dashboard-guide';
import { BookOpen } from 'lucide-react';
import { useMemo } from 'react';

type Props = {
    t: (key: string, opts?: Record<string, any>) => string;
    content?: string | null;
};

const DashboardGuideCard = ({ t, content }: Props) => {
    const safeContent = useMemo(
        () => sanitizeDashboardGuideHtml(content || ''),
        [content],
    );

    if (safeContent === '') {
        return null;
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base sm:text-lg">
                    <BookOpen className="h-5 w-5 text-muted-foreground" />
                    {t('dashboard_guide.title', {
                        defaultValue: 'Hướng dẫn sử dụng Dashboard',
                    })}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    className="space-y-4 text-sm leading-6 [&_a]:text-primary [&_a]:underline [&_blockquote]:border-l-2 [&_blockquote]:pl-4 [&_blockquote]:text-muted-foreground [&_figcaption]:text-sm [&_figcaption]:text-muted-foreground [&_figure]:space-y-2 [&_h2]:text-lg [&_h2]:font-semibold [&_h3]:text-base [&_h3]:font-semibold [&_h4]:font-medium [&_iframe]:aspect-video [&_iframe]:w-full [&_iframe]:rounded-md [&_iframe]:border [&_iframe]:bg-black [&_img]:max-h-[520px] [&_img]:w-full [&_img]:rounded-md [&_img]:border [&_img]:object-contain [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:leading-6 [&_ul]:list-disc [&_ul]:pl-5"
                    dangerouslySetInnerHTML={{ __html: safeContent }}
                />
            </CardContent>
        </Card>
    );
};

export default DashboardGuideCard;

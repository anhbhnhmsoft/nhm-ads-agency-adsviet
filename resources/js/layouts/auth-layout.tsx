import LoadingGlobal from '@/components/loading-global';
import { ThemeProvider } from '@/components/theme-provider';
import { Card, CardContent } from '@/components/ui/card';
import { Toaster } from '@/components/ui/sonner';
import { usePage } from '@inertiajs/react';
import { ReactNode, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';

export default function AuthLayout({
    children,
    title = '',
    description = '',
}: {
    children: ReactNode;
    title?: string;
    description?: string;
}) {
    const { t } = useTranslation();
    const { flash } = usePage().props;
    const { logo_path } = usePage().props as { logo_path?: string };

    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success, { duration: 3000 });
        }
        if (flash?.error) {
            toast.error(flash.error, { duration: 3000 });
        }
        if (flash?.info) {
            toast(flash.info, { duration: 3000 });
        }
        if (flash?.warning) {
            toast.warning(flash.warning, { duration: 3000 });
        }
    }, [flash]);

    return (
        <ThemeProvider defaultTheme={'light'}>
            <div className="flex min-h-svh flex-col items-center justify-center bg-gradient-to-br from-slate-50 via-slate-100 to-blue-50 p-4 md:p-10">
                <div className="w-full max-w-md">
                    <Card className="overflow-hidden rounded-2xl border border-slate-100 shadow-xl bg-white/90 backdrop-blur-md">
                        <CardContent className="px-8 py-8">
                            <div className="mb-6 flex flex-col items-center justify-center gap-4">
                                {/* Temporarily hidden logo
                                <div className="flex items-center justify-center rounded-2xl bg-slate-900 p-4 shadow-lg ring-4 ring-slate-900/5">
                                    <img
                                        src={`${logo_path}`}
                                        alt="logo"
                                        className="h-10 w-auto object-contain"
                                    />
                                </div>
                                */}
                                <div className="space-y-1 text-center">
                                    <h1 className="text-2xl font-bold tracking-tight text-slate-900">
                                        {t(title)}
                                    </h1>
                                    {description && (
                                        <p className="text-sm text-slate-500">
                                            {t(description)}
                                        </p>
                                    )}
                                </div>
                            </div>
                            {children}
                        </CardContent>
                    </Card>
                </div>
            </div>
            <Toaster expand visibleToasts={3} position="top-center" />
            <LoadingGlobal />
        </ThemeProvider>
    );
}

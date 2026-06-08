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
            <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-[#e8f0fe] p-3 md:p-10">
                <div className="flex w-full max-w-md flex-col gap-6">
                    <div className="flex flex-col items-center justify-center gap-2">
                        <div className="flex items-center justify-center">
                            <img
                                src={`${logo_path}`}
                                alt="logo"
                                className="h-15 w-15"
                            />
                        </div>
                        <h1 className="text-center text-2xl font-bold">
                            {t(title)}
                        </h1>
                        <p className="text-center text-sm text-gray-500">
                            {t(description)}
                        </p>
                    </div>
                    <div className="flex flex-col gap-6">
                        <Card className="rounded-xl">
                            <CardContent className="px-8 py-7">
                                {children}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
            <Toaster expand visibleToasts={3} position="top-center" />
            <LoadingGlobal />
        </ThemeProvider>
    );
}

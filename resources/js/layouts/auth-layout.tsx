import { Card, CardContent } from '@/components/ui/card';
import { ReactNode, useEffect } from 'react';
import { ThemeProvider } from '@/components/theme-provider';
import { useTranslation } from 'react-i18next';
import { Shield } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { toast } from "sonner"
import { Toaster } from "@/components/ui/sonner"
import LoadingGlobal from '@/components/loading-global';

export default function AuthLayout({
    children,
    title = '',
    description = '',
}: {
    children: ReactNode;
    title?: string;
    description?: string;
}) {
    const {t} = useTranslation();
    const { flash } = usePage().props;

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
        <ThemeProvider defaultTheme={"light"}>
            <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-blue-100 p-6 md:p-10">
                <div className="flex w-full max-w-md flex-col gap-6">
                    <div className="flex flex-col gap-2 items-center justify-center">
                        <Shield className="w-12 h-12 text-blue-500" />
                        <h1 className="text-2xl font-bold text-center">{t(title)}</h1>
                        <p className="text-sm text-gray-500 text-center">{t(description)}</p>
                    </div>
                    <div className="flex flex-col gap-6">
                        <Card className="rounded-xl">
                            <CardContent className="px-10 py-8">
                                {children}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
            <Toaster expand visibleToasts={3} position="top-center"/>
            <LoadingGlobal />
        </ThemeProvider>
    );
}

import { Card, CardContent } from '@/components/ui/card';
import { ReactNode } from 'react';
import { ThemeProvider } from '@/components/theme-provider';
import { useTranslation } from 'react-i18next';
import { Shield } from 'lucide-react';

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

    return (
        <ThemeProvider defaultTheme={"light"}>
            <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-blue-100 p-6 md:p-10">
                <div className="flex w-full max-w-md flex-col gap-6">
                    <div className="flex flex-col gap-2 items-center justify-center">
                        <Shield className="w-12 h-12 text-blue-500" />
                        <h1 className="text-2xl font-bold">{t(title)}</h1>
                        <p className="text-sm text-gray-500">{t(description)}</p>
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
        </ThemeProvider>
    );
}

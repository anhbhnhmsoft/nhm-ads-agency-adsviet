import { useState, useEffect } from 'react';
import { router, usePage } from '@inertiajs/react';
import { user_list } from '@/routes';
import type { WalletData } from '@/pages/user/types/type';

export function useWallet(userId: string | null | undefined, enabled: boolean = true) {
    const { props } = usePage();
    const [wallet, setWallet] = useState<WalletData | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const fetchWallet = () => {
        if (!userId || !enabled) {
            setWallet(null);
            return;
        }

        setLoading(true);
        setError(null);

        router.get(
            user_list().url,
            {
                wallet_user_id: userId,
            },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['wallet'],
                onSuccess: (page) => {
                    const walletData = (page.props as any)?.wallet as WalletData | undefined;
                    const walletError = (page.props as any)?.walletError as string | undefined;

                    if (walletError) {
                        setError(walletError);
                        setWallet(null);
                    } else if (walletData) {
                        setWallet(walletData);
                        setError(null);
                    } else {
                        setError('Không thể lấy thông tin ví');
                        setWallet(null);
                    }
                    setLoading(false);
                },
                onError: (errors) => {
                    const errorMessage = errors?.walletError || errors?.error || errors?.message || 'Không thể lấy thông tin ví';
                    setError(errorMessage);
                    setWallet(null);
                    setLoading(false);
                },
                onFinish: () => {
                    setLoading(false);
                },
            }
        );
    };

    // Lấy wallet từ props nếu có (khi đã fetch từ trước)
    useEffect(() => {
        const walletFromProps = (props as any)?.wallet as WalletData | undefined;
        const walletErrorFromProps = (props as any)?.walletError as string | undefined;

        if (walletFromProps && !wallet) {
            setWallet(walletFromProps);
        }
        if (walletErrorFromProps && !error) {
            setError(walletErrorFromProps);
        }
    }, [props]);

    useEffect(() => {
        if (enabled && userId) {
            fetchWallet();
        }
    }, [userId, enabled]);

    return {
        wallet,
        loading,
        error,
        refetch: fetchWallet,
    };
}


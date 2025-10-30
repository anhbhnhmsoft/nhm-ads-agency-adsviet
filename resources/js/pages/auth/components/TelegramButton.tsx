import { FC, useEffect, useRef } from 'react';
import { TelegramUser } from '@/pages/auth/types/types';
import { router } from '@inertiajs/react';
import { auth_telegram } from '@/routes';

type Props = {
    bot_username: string;
};
declare global {
    interface Window {
        onTelegramAuth?: (user?: TelegramUser) => void;
    }
}
const TelegramButton: FC<Props> = ({ bot_username }) => {
    const containerRef = useRef<HTMLDivElement>(null);
    const scriptRef = useRef<HTMLScriptElement | null>(null);

    useEffect(() => {
        if (!bot_username || !containerRef.current) return;

        // Cleanup function sẽ được gọi trước khi effect chạy lại
        const cleanup = () => {
            // Xóa script nếu tồn tại
            if (scriptRef.current && scriptRef.current.parentNode) {
                scriptRef.current.parentNode.removeChild(scriptRef.current);
                scriptRef.current = null;
            }

            // Xóa callback
            delete window.onTelegramAuth;

            // Xóa iframe được tạo bởi Telegram widget
            if (containerRef.current) {
                const iframes = containerRef.current.querySelectorAll('iframe');
                iframes.forEach(iframe => iframe.remove());
            }
        };

        // Cleanup trước khi thêm script mới
        cleanup();

        // Tạo script mới
        const script = document.createElement('script');
        script.src = 'https://telegram.org/js/telegram-widget.js?22'; // Sửa typo và cập nhật version
        script.async = true;
        script.setAttribute('data-telegram-login', bot_username);
        script.setAttribute('data-size', 'large');
        script.setAttribute('data-userpic', 'false');
        script.setAttribute('data-request-access', 'write');
        script.setAttribute('data-radius', '8');
        script.setAttribute('data-onauth', 'onTelegramAuth(user)');

        // Lưu reference
        scriptRef.current = script;

        // Thêm script vào container
        containerRef.current.appendChild(script);

        // Định nghĩa callback
        window.onTelegramAuth = (user) => {
            if (!user) return;
            router.post(auth_telegram(), user);
        };

        // Cleanup khi component unmount hoặc bot_username thay đổi
        return cleanup;
    }, [bot_username]);

    return (
        <div className="flex items-center justify-center">
            <div ref={containerRef} />
        </div>
    );
};

export default TelegramButton;

import { Spinner } from '@/components/ui/spinner';
import systemStore from '@/layouts/store';

const LoadingGlobal = () => {
    const loading = systemStore((state) => state.loading);

    if (loading) {
        return (
            <div className="fixed top-0 left-0 z-50 flex h-full w-full items-center justify-center bg-black/20">
                <Spinner className="h-12 w-12" />
            </div>
        );
    }
    return null;
};

export default LoadingGlobal;

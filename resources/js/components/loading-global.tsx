import systemStore from '@/layouts/store';
import { Spinner } from "@/components/ui/spinner"

const LoadingGlobal = () => {
    const loading = systemStore((state) => state.loading);

    if (loading) {
        return (
            <div className="fixed top-0 left-0 w-full h-full bg-black/20 flex items-center justify-center z-50">
                <Spinner className="w-12 h-12" />
            </div>
        )
    }
    return null;
}

export default LoadingGlobal;

import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { router } from '@inertiajs/react';
import { useCallback } from 'react';
import { LaravelPaginator } from '@/lib/types/type';
import { useTranslation } from 'react-i18next';

interface DataTablePaginationProps<TData> {
    paginator: LaravelPaginator<TData>;
}

export function DataTablePagination<TData>({ paginator }: DataTablePaginationProps<TData>) {
    const {t} = useTranslation();
    // Hàm chung để điều hướng Inertia
    const navigate = useCallback((url: string | null) => {
        if (url) {
            // Giữ trạng thái của các bộ lọc/tìm kiếm khác
            router.visit(url, { preserveScroll: true, preserveState: true });
        }
    }, []);

    // Hàm xử lý khi người dùng thay đổi số lượng items/trang
    const handlePerPageChange = useCallback((value: string) => {
        const newPerPage = Number(value);
        // Lấy URL hiện tại để giữ lại các tham số tìm kiếm/lọc
        const currentUrl = window.location.href;
        const url = new URL(currentUrl);
        // 1. Cập nhật per_page
        url.searchParams.set('per_page', newPerPage.toString());
        // 2. Reset về trang 1
        url.searchParams.set('page', '1');
        // Gửi request Inertia
        router.visit(url.toString(), { preserveScroll: true, preserveState: true });
    }, []);

    // Chỉ giữ lại các link là số trang (loại bỏ "Previous" / "Next" bất kể ngôn ngữ / label)
    const pageLinks = paginator.meta.links.filter((link) => {
        if (link.page === null) return false;
        const label = (link.label || '').toString().toLowerCase();
        if (label.includes('pagination.previous') || label.includes('pagination.next')) return false;
        if (label.includes('previous') || label.includes('next')) return false;
        return true;
    });
    const firstPageUrl = paginator.meta.links.find((link) => link.page === 1)?.url || paginator.links.first;
    const lastPageUrl = paginator.meta.links.find((link) => link.page === paginator.meta.last_page)?.url || paginator.links.last;

    return (
        <div className="flex items-center justify-between px-2 py-3">
            {/* THÔNG TIN HÀNG ĐƯỢC CHỌN VÀ THỐNG KÊ */}
            <div className="hidden md:block flex-1 text-sm text-muted-foreground">
                {/* Chỉ hiển thị thông tin thống kê của Laravel Paginator */}
                {/* Thay thế thông tin thống kê bằng translation */}
                {t('common.pagination', {
                    start: paginator.meta.from || 0,
                    end: paginator.meta.to || 0,
                    total: paginator.meta.total,
                })}
            </div>

            <div className="flex items-center space-x-6 lg:space-x-8">
                {/* SỐ HÀNG MỖI TRANG (PER PAGE) */}
                <div className="hidden md:flex items-center space-x-2">
                    <p className="text-sm font-medium">{t('common.per_page')}</p>
                    <Select
                        value={paginator.meta.per_page.toString()}
                        onValueChange={handlePerPageChange}
                    >
                        <SelectTrigger className="h-8 w-[70px]">
                            <SelectValue placeholder={paginator.meta.per_page} />
                        </SelectTrigger>
                        <SelectContent side="top">
                            {[10, 20, 30, 40, 50].map((pageSize) => (
                                <SelectItem key={pageSize} value={`${pageSize}`}>
                                    {pageSize}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {/* SỐ TRANG HIỆN TẠI */}
                <div className="hidden md:flex w-[100px] items-center justify-center text-sm font-medium">
                    {/* Thay thế thông tin trang hiện tại bằng translation */}
                    {t('common.current_page_to', {
                        current_page: paginator.meta.current_page,
                        total_page: paginator.meta.last_page,
                    })}
                </div>

                {/* CÁC NÚT ĐIỀU HƯỚNG */}
                <div className="flex items-center space-x-2">
                    {/* Trang đầu (First Page) */}
                    <Button
                        variant="outline"
                        size="icon"
                        className="hidden size-8 lg:flex"
                        onClick={() => navigate(firstPageUrl)}
                        disabled={paginator.meta.current_page === 1}
                    >
                        <ChevronsLeft className='size-4' />
                    </Button>

                    {/* Trang trước (Previous Page) */}
                    <Button
                        variant="outline"
                        size="icon"
                        className="size-8"
                        onClick={() => navigate(paginator.links.prev)}
                        disabled={!paginator.links.prev}
                    >
                        <ChevronLeft className='size-4' />
                    </Button>

                    {/*Các trang */}
                    {pageLinks.map(link => (
                        <Button
                            key={link.label}
                            variant={link.active ? 'default' : 'outline'}
                            className="h-8 w-8 p-0"
                            onClick={() => navigate(link.url)}
                            disabled={!link.url}
                        >
                             <span dangerouslySetInnerHTML={{ __html: link.label }} />
                        </Button>
                    ))}

                    {/* Trang tiếp theo (Next Page) */}
                    <Button
                        variant="outline"
                        size="icon"
                        className="size-8"
                        onClick={() => navigate(paginator.links.next)}
                        disabled={!paginator.links.next}
                    >
                        <ChevronRight className='size-4' />
                    </Button>

                    {/* Trang cuối (Last Page) */}
                    <Button
                        variant="outline"
                        size="icon"
                        className="hidden size-8 lg:flex"
                        onClick={() => navigate(lastPageUrl)}
                        disabled={paginator.meta.current_page === paginator.meta.last_page}
                    >
                        <ChevronsRight className='size-4' />
                    </Button>
                </div>
            </div>
        </div>
    );
}

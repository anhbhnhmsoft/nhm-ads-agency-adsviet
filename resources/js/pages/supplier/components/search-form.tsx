import { useTranslation } from 'react-i18next';
import { useSearchSupplier } from '@/pages/supplier/hooks/use-search';
import { Card, CardAction, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Field, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Plus, Search } from 'lucide-react';
import { router } from '@inertiajs/react';
import { suppliers_create_view } from '@/routes';

const SupplierListSearchForm = () => {
    const { t } = useTranslation();
    const { query, setQuery, handleSearch } = useSearchSupplier();

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('common.search')}</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Field>
                        <FieldLabel htmlFor="keyword">
                            {t('common.keyword')}
                        </FieldLabel>
                        <Input
                            id="keyword"
                            autoComplete="off"
                            placeholder={t('common.keyword')}
                            value={query.keyword || ''}
                            onChange={(e) => {
                                setQuery({ keyword: e.target.value });
                            }}
                        />
                    </Field>
                </div>
            </CardContent>
            <CardFooter>
                <CardAction className="space-y-2 space-x-2">
                    <Button
                        className="cursor-pointer"
                        onClick={() => handleSearch()}
                    >
                        <Search />
                        {t('common.search', { defaultValue: 'Search' })}
                    </Button>
                    <Button
                        variant="outline"
                        className="cursor-pointer"
                        onClick={() => {
                            router.visit(suppliers_create_view().url);
                        }}
                    >
                        <Plus />
                        {t('supplier.create_btn', { defaultValue: 'Tạo nhà cung cấp' })}
                    </Button>
                </CardAction>
            </CardFooter>
        </Card>
    );
};

export default SupplierListSearchForm;


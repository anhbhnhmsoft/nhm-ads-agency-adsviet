import { useTranslation } from 'react-i18next';
import { useSearchServicePackage } from '@/pages/service-package/hooks/use-search';
import { Card, CardAction, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Field, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Plus, Search } from 'lucide-react';
import { router } from '@inertiajs/react';
import { service_packages_create_view } from '@/routes';

const ServicePackageListSearchForm = () => {
    const { t } = useTranslation();
    const { query, setQuery, handleSearch } = useSearchServicePackage()

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('common.search')}</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    className={
                        'grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4'
                    }
                >
                    <Field>
                        <FieldLabel htmlFor="name">
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
                <CardAction className={'space-y-2 space-x-2'}>
                    <Button
                        className={'cursor-pointer'}
                        onClick={() => handleSearch()}
                    >
                        <Search />
                        Tìm
                    </Button>
                    <Button
                        variant={"outline"}
                        className={'cursor-pointer'}
                        onClick={() => {
                            router.visit(
                                service_packages_create_view().url,
                            );
                        }}
                    >
                        <Plus />
                        {t('service_packages.create_btn')}
                    </Button>
                </CardAction>
            </CardFooter>
        </Card>
    );
}

export default ServicePackageListSearchForm;

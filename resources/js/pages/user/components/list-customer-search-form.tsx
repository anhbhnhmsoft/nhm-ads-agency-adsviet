import {
    Card,
    CardAction,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useSearchCustomerList } from '@/pages/user/hooks/use-search';
import { Search } from 'lucide-react';
import { Field, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';

const ListCustomerSearchForm = () => {
    const { t } = useTranslation();
    const { query, setQuery, handleSearch } = useSearchCustomerList();

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
                </CardAction>
            </CardFooter>
        </Card>
    );
};

export default ListCustomerSearchForm;

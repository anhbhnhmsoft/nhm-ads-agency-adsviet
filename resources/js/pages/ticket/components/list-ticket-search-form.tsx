import {
    Card,
    CardAction,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useSearchTicketList } from '@/pages/ticket/hooks/use-search-ticket';
import { Search } from 'lucide-react';
import { Field, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';

const ListTicketSearchForm = () => {
    const { t } = useTranslation();
    const { query, setQuery, handleSearch } = useSearchTicketList();

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('common.search')}</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Field>
                        <FieldLabel htmlFor="keyword">{t('common.keyword')}</FieldLabel>
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
                    <Button className="cursor-pointer" onClick={() => handleSearch()}>
                        <Search />
                        {t('common.search')}
                    </Button>
                </CardAction>
            </CardFooter>
        </Card>
    );
};

export default ListTicketSearchForm;


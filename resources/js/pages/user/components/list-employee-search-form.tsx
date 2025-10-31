import { Card, CardHeader, CardTitle, CardContent, CardFooter, CardAction } from '@/components/ui/card';
import { useSearchEmployeeList } from '@/pages/user/hooks/use-search';
import ControlPermission from '@/components/control-permission';
import { Plus, Search } from 'lucide-react';
import { _UserRole } from '@/lib/types/constants';
import { Field, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import {user_create_employee} from "@/routes"

const ListEmployeeSearchForm = () => {
    const {t} = useTranslation();
    const { query, setQuery, handleSearch } = useSearchEmployeeList();

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('common.search')}</CardTitle>
            </CardHeader>
            <CardContent>
                <div className={"grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"}>
                    <Field>
                        <FieldLabel htmlFor="name">{t('common.keyword')}</FieldLabel>
                        <Input
                            id="keyword"
                            autoComplete="off"
                            placeholder="name, id, username"
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
                    <ControlPermission
                        roles={[_UserRole.ADMIN, _UserRole.MANAGER]}
                        render={() => (
                            <Button className={'cursor-pointer'} onClick={() => handleSearch()}>
                                <Search />
                                Tìm
                            </Button>
                        )}
                    />
                    <ControlPermission
                        roles={[_UserRole.ADMIN, _UserRole.MANAGER]}
                        render={() => (
                            <Button variant={"outline"} className={'cursor-pointer'} onClick={() => {
                                router.visit(user_create_employee().url)
                            }}>
                                <Plus />
                                {t('common.add')}
                            </Button>
                        )}
                    />
                </CardAction>
            </CardFooter>
        </Card>
    );
}


export default ListEmployeeSearchForm;

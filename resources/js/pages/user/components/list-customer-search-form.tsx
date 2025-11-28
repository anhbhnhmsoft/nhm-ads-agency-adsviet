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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { CustomerListQuery, UserOption } from '@/pages/user/types/type';

type Props = {
    managers: UserOption[];
    employees: UserOption[];
    initialFilter?: CustomerListQuery['filter'];
    showManagerSelect?: boolean;
    showEmployeeSelect?: boolean;
};

const ListCustomerSearchForm = ({
    managers,
    employees,
    initialFilter,
    showManagerSelect = false,
    showEmployeeSelect = false,
}: Props) => {
    const { t } = useTranslation();
    const { query, setQuery, handleSearch } = useSearchCustomerList(initialFilter);

    const EMPTY_OPTION = 'all';
    const managerValue =
        typeof query.manager_id === 'number' && query.manager_id > 0 ? String(query.manager_id) : EMPTY_OPTION;
    const employeeValue =
        typeof query.employee_id === 'number' && query.employee_id > 0 ? String(query.employee_id) : EMPTY_OPTION;

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('common.search')}</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Field>
                        <FieldLabel htmlFor="name">{t('common.keyword')}</FieldLabel>
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
                    {showManagerSelect && (
                        <Field>
                            <FieldLabel>{t('user.filter_manager')}</FieldLabel>
                            <Select
                                value={managerValue}
                                onValueChange={(value) => {
                                    setQuery({
                                        manager_id: value === EMPTY_OPTION ? null : Number(value),
                                        employee_id: null,
                                    });
                                }}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder={t('user.select_manager_placeholder')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={EMPTY_OPTION}>{t('common.all')}</SelectItem>
                                    {managers.map((manager) => (
                                        <SelectItem key={manager.id} value={String(manager.id)}>
                                            {manager.username}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </Field>
                    )}
                    {showEmployeeSelect && (
                        <Field>
                            <FieldLabel>{t('user.filter_employee')}</FieldLabel>
                            <Select
                                value={employeeValue}
                                onValueChange={(value) => {
                                    setQuery({
                                        employee_id: value === EMPTY_OPTION ? null : Number(value),
                                    });
                                }}
                                disabled={!employees.length}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder={t('user.select_employee_placeholder')} />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={EMPTY_OPTION}>{t('common.all')}</SelectItem>
                                    {employees.map((employee) => (
                                        <SelectItem key={employee.id} value={String(employee.id)}>
                                            {employee.username}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </Field>
                    )}
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

export default ListCustomerSearchForm;

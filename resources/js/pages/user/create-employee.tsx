import useCheckRole from '@/hooks/use-check-role';
import AppLayout from '@/layouts/app-layout';
import { _UserRole } from '@/lib/types/constants';
import UserForm from '@/pages/user/components/UserForm';
import { useEntityForm } from '@/pages/user/hooks/use-entity-form';
import { Employee } from '@/pages/user/types/type';
import {
    user_employee_store,
    user_employee_update,
    user_list_employee,
} from '@/routes';
import { usePage } from '@inertiajs/react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

type Props = {
    employee?: Employee;
};

const CreateEmployee = ({ employee }: Props) => {
    const { t } = useTranslation();
    const { props } = usePage();
    const checkRole = useCheckRole(props.auth as any);
    const isAdmin = checkRole([_UserRole.ADMIN]);
    const isManager = checkRole([_UserRole.MANAGER]);
    const isEdit = !!employee?.id;
    const { form, handleSubmitForm } = useEntityForm({
        initial: employee,
        defaultRole: _UserRole.EMPLOYEE,
        storeUrl: user_employee_store().url,
        updateUrl: employee?.id
            ? user_employee_update({ id: employee.id }).url
            : undefined,
    });
    const { data, setData, processing, errors } = form;

    const roleOptions = [
        { value: _UserRole.MANAGER, label: t('enum.user_role.manager') },
        { value: _UserRole.EMPLOYEE, label: t('enum.user_role.employee') },
    ];

    return (
        <UserForm
            data={data as any}
            errors={errors as any}
            processing={processing}
            setData={setData}
            isEdit={isEdit}
            roleOptions={roleOptions}
            canEditRole={!(isEdit && isManager && !isAdmin)}
            onSubmit={handleSubmitForm}
            backHref={user_list_employee().url}
            title={
                isEdit
                    ? t('user.edit_employee')
                    : t('menu.user_create_employee')
            }
        />
    );
};

CreateEmployee.layout = (page: ReactNode) => (
    <AppLayout
        breadcrumbs={[{ title: 'menu.user_list_employee' }]}
        children={page}
    />
);

export default CreateEmployee;

import useCheckRole from '@/hooks/use-check-role';
import AppLayout from '@/layouts/app-layout';
import { _UserRole } from '@/lib/types/constants';
import UserForm from '@/pages/user/components/UserForm';
import { useEntityForm } from '@/pages/user/hooks/use-entity-form';
import { Employee } from '@/pages/user/types/type';
import { user_list, user_update } from '@/routes';
import { usePage } from '@inertiajs/react';
import { ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

type Props = {
    user?: Employee;
};

const CreateUser = ({ user }: Props) => {
    const { t } = useTranslation();
    const { props } = usePage();
    const checkRole = useCheckRole(props.auth as any);
    const isAdmin = checkRole([_UserRole.ADMIN]);
    const isManager = checkRole([_UserRole.MANAGER]);
    const isEdit = !!user?.id;
    const { form, handleSubmitForm } = useEntityForm({
        initial: user,
        defaultRole: _UserRole.CUSTOMER,
        updateUrl: user?.id ? user_update({ id: user.id }).url : undefined,
    });
    const { data, setData, processing, errors } = form;

    const roleOptions = [
        { value: _UserRole.CUSTOMER, label: t('enum.user_role.customer') },
        { value: _UserRole.AGENCY, label: t('enum.user_role.agency') },
    ];

    return (
        <UserForm
            data={data as any}
            errors={errors as any}
            processing={processing}
            setData={setData}
            isEdit={isEdit}
            roleOptions={roleOptions}
            canEditRole={isAdmin}
            onSubmit={handleSubmitForm}
            backHref={user_list().url}
            title={
                isEdit
                    ? t('user.edit_customer', {
                          defaultValue: 'Chỉnh sửa khách hàng',
                      })
                    : t('user.create_customer', {
                          defaultValue: 'Tạo khách hàng',
                      })
            }
        />
    );
};

CreateUser.layout = (page: ReactNode) => (
    <AppLayout
        breadcrumbs={[{ title: 'menu.user_list_customer' }]}
        children={page}
    />
);

export default CreateUser;

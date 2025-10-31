import { ReactNode } from 'react';
import AppLayout from '@/layouts/app-layout';
import ListEmployee from '@/pages/user/list-employee';

const CreateEmployee = () => {
    return (
        <div>
            <h1>Create Employee</h1>
        </div>
    );
}

ListEmployee.layout = (page: ReactNode) => (
    <AppLayout breadcrumbs={[{title: 'menu.user_list_employee'}]} children={page} />
);

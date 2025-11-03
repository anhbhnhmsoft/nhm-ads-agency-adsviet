import { useState, useEffect, useMemo } from 'react';
import { useTranslation } from 'react-i18next';
import { usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { router } from '@inertiajs/react';
import { user_get_employees_by_manager, user_assign_employee, user_unassign_employee } from '@/routes';
import { Check } from 'lucide-react';
import { Manager, EmployeeForAssignment } from '@/pages/user/types/type';

type Props = {
    managers: Manager[];
};

export default function AssignEmployee({ managers }: Props) {
    const { t } = useTranslation();
    const { url } = usePage();
    const [selectedManagerId, setSelectedManagerId] = useState<string>('');
    const [employees, setEmployees] = useState<EmployeeForAssignment[]>([]);
    const [loading, setLoading] = useState(false);

    const searchKeyword = useMemo(() => {
        const urlParams = new URLSearchParams(url.split('?')[1] || '');
        return urlParams.get('filter[keyword]') || '';
    }, [url]);

    const filteredEmployees = useMemo(() => {
        if (!searchKeyword.trim()) {
            return employees;
        }
        const keyword = searchKeyword.toLowerCase();
        return employees.filter(emp => 
            emp.name.toLowerCase().includes(keyword) || 
            emp.username.toLowerCase().includes(keyword)
        );
    }, [employees, searchKeyword]);

    useEffect(() => {
        if (selectedManagerId) {
            loadEmployees();
        } else {
            setEmployees([]);
        }
    }, [selectedManagerId]);

    const loadEmployees = async () => {
        if (!selectedManagerId) return;
        setLoading(true);
        try {
            const response = await fetch(user_get_employees_by_manager({ managerId: selectedManagerId }).url);
            const data = await response.json();
            if (data.success) {
                setEmployees(data.data || []);
            }
        } catch (error) {
            console.error('Error loading employees:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleToggleAssign = async (employeeId: number, assigned: boolean) => {
        if (!selectedManagerId) return;
        setLoading(true);
        try {
            const url = assigned 
                ? user_unassign_employee().url 
                : user_assign_employee().url;
            
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    manager_id: parseInt(selectedManagerId),
                    employee_id: employeeId,
                }),
            });

            const data = await response.json();
            if (data.success) {
                await loadEmployees();
                router.reload({ only: [] });
            }
        } catch (error) {
            console.error('Error toggling assign:', error);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="space-y-4">
            <div className="grid gap-2">
                <label>{t('user.select_manager', { defaultValue: 'Chọn quản lý' })}</label>
                <Select value={selectedManagerId} onValueChange={setSelectedManagerId}>
                    <SelectTrigger>
                        <SelectValue placeholder={t('user.select_manager_placeholder', { defaultValue: 'Chọn quản lý...' })} />
                    </SelectTrigger>
                    <SelectContent>
                        {managers.map((manager) => (
                            <SelectItem key={manager.id} value={manager.id.toString()}>
                                {manager.name} ({manager.username})
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            {selectedManagerId && (
                <div className="space-y-4">
                    <div className="space-y-2">
                        <h3 className="font-semibold">{t('user.employee_list', { defaultValue: 'Danh sách nhân viên' })}</h3>
                        {loading ? (
                            <div>{t('common.processing', { defaultValue: 'Đang xử lý...' })}</div>
                        ) : employees.length === 0 ? (
                            <div className="text-gray-500">{t('user.no_employees', { defaultValue: 'Không có nhân viên nào' })}</div>
                        ) : filteredEmployees.length === 0 ? (
                            <div className="text-gray-500">{t('user.no_employees_found', { defaultValue: 'Không tìm thấy nhân viên nào' })}</div>
                        ) : (
                            <div className="border rounded-md divide-y">
                                {filteredEmployees.map((employee) => (
                                    <div key={employee.id} className="p-3 flex items-center justify-between hover:bg-gray-50">
                                        <div className="flex items-center gap-3">
                                            {employee.assigned && (
                                                <Check className="size-4 text-green-500" />
                                            )}
                                            <div>
                                                <div className="font-medium">{employee.name}</div>
                                                <div className="text-sm text-gray-500">{employee.username}</div>
                                            </div>
                                        </div>
                                        <Button
                                            type="button"
                                            variant={employee.assigned ? 'outline' : 'default'}
                                            size="sm"
                                            onClick={() => handleToggleAssign(employee.id, employee.assigned)}
                                            disabled={loading}
                                        >
                                            {employee.assigned 
                                                ? t('user.unassign', { defaultValue: 'Hủy gán' })
                                                : t('user.assign', { defaultValue: 'Gán nhân viên' })
                                            }
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}


import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { UserOption } from '@/pages/service-package/types/type';
import { useMemo, useState } from 'react';
import { useTranslation } from 'react-i18next';

type Props = {
    users: UserOption[];
    value: string[];
    onChange: (value: string[]) => void;
};

const UserMultiSelect = ({ users, value, onChange }: Props) => {
    const { t } = useTranslation();
    const [keyword, setKeyword] = useState('');

    const selectedSet = useMemo(() => new Set(value), [value]);
    const filteredUsers = useMemo(() => {
        const search = keyword.trim().toLowerCase();
        if (!search) {
            return users;
        }

        return users.filter((user) => {
            const text = [user.name, user.username, user.email, user.label]
                .join(' ')
                .toLowerCase();

            return text.includes(search);
        });
    }, [keyword, users]);

    const toggleUser = (userId: string, checked: boolean) => {
        if (checked) {
            onChange([...Array.from(selectedSet), userId]);
            return;
        }

        onChange(value.filter((id) => id !== userId));
    };

    return (
        <div className="space-y-3 rounded-md border p-3">
            <Input
                value={keyword}
                onChange={(event) => setKeyword(event.target.value)}
                placeholder={t('service_packages.allowed_users_search')}
            />

            <div className="max-h-64 space-y-2 overflow-y-auto">
                {filteredUsers.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {t('service_packages.allowed_users_empty')}
                    </p>
                ) : (
                    filteredUsers.map((user) => {
                        const checked = selectedSet.has(user.id);
                        const label =
                            user.label ||
                            user.name ||
                            user.username ||
                            user.email;

                        return (
                            <Label
                                key={user.id}
                                className="flex cursor-pointer items-start gap-3 rounded-md border p-3 hover:bg-accent/50 has-aria-checked:border-[#4285f4] has-aria-checked:bg-blue-50"
                            >
                                <Checkbox
                                    checked={checked}
                                    onCheckedChange={(next) =>
                                        toggleUser(user.id, next === true)
                                    }
                                    className="data-[state=checked]:border-[#4285f4] data-[state=checked]:bg-[#4285f4] data-[state=checked]:text-white"
                                />
                                <span className="grid gap-1 font-normal">
                                    <span className="text-sm leading-none font-medium">
                                        {label}
                                    </span>
                                    {user.email && (
                                        <span className="text-xs text-muted-foreground">
                                            {user.email}
                                        </span>
                                    )}
                                </span>
                            </Label>
                        );
                    })
                )}
            </div>

            <p className="text-sm text-muted-foreground">
                {t('service_packages.allowed_users_selected', {
                    count: value.length,
                })}
            </p>
        </div>
    );
};

export default UserMultiSelect;

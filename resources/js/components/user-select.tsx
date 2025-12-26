import * as React from 'react';
import { Search, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { useTranslation } from 'react-i18next';

type UserOption = {
    id: string;
    name: string;
    username: string;
    email: string;
    label: string;
};

type UserSelectProps = {
    value: string[];
    onValueChange: (value: string[]) => void;
    options: UserOption[];
    placeholder?: string;
    id?: string;
};

const { t } = useTranslation();

export function UserSelect({
    value,
    onValueChange,
    options,
    placeholder = t('service_packages.postpay_users_placeholder', { defaultValue: 'Chọn người dùng được phép trả sau' }),
    id,
}: UserSelectProps) {
    const [searchQuery, setSearchQuery] = React.useState('');
    const [open, setOpen] = React.useState(false);

    const filteredOptions = React.useMemo(() => {
        if (!searchQuery.trim()) {
            return options;
        }
        const query = searchQuery.toLowerCase();
        return options.filter(
            (option) =>
                option.name.toLowerCase().includes(query) ||
                option.username.toLowerCase().includes(query) ||
                option.email.toLowerCase().includes(query) ||
                option.id.toLowerCase().includes(query)
        );
    }, [options, searchQuery]);

    const selectedUsers = React.useMemo(() => {
        return options.filter((opt) => value.includes(opt.id));
    }, [options, value]);

    const handleToggleUser = (userId: string) => {
        if (value.includes(userId)) {
            onValueChange(value.filter((id) => id !== userId));
        } else {
            onValueChange([...value, userId]);
        }
    };

    const handleSelectAll = () => {
        if (value.length === filteredOptions.length) {
            onValueChange([]);
        } else {
            onValueChange(filteredOptions.map((opt) => opt.id));
        }
    };

    const handleRemoveUser = (userId: string) => {
        onValueChange(value.filter((id) => id !== userId));
    };

    const isAllSelected = filteredOptions.length > 0 && value.length === filteredOptions.length;

    return (
        <div className="space-y-2">
            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <div
                        id={id}
                        className="flex min-h-9 w-full items-center gap-2 rounded-md border border-input bg-white px-3 py-2 text-sm shadow-xs cursor-pointer focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        {selectedUsers.length === 0 ? (
                            <span className="text-muted-foreground">{placeholder}</span>
                        ) : (
                            <div className="flex flex-wrap gap-1">
                                {selectedUsers.slice(0, 3).map((user) => (
                                    <Badge key={user.id} variant="secondary" className="text-xs">
                                        {user.name}
                                        <button
                                            type="button"
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                handleRemoveUser(user.id);
                                            }}
                                            className="ml-1 rounded-full hover:bg-muted"
                                        >
                                            <X className="h-3 w-3" />
                                        </button>
                                    </Badge>
                                ))}
                                {selectedUsers.length > 3 && (
                                    <Badge variant="secondary" className="text-xs">
                                        +{selectedUsers.length - 3} khác
                                    </Badge>
                                )}
                            </div>
                        )}
                    </div>
                </PopoverTrigger>
                <PopoverContent className="w-[var(--radix-popover-trigger-width)] p-0" align="start">
                    <div className="sticky top-0 z-10 bg-white border-b p-2">
                        <div className="relative">
                            <Search className="absolute left-2 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder="Tìm kiếm người dùng..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="pl-8 h-9"
                                onClick={(e) => e.stopPropagation()}
                                onKeyDown={(e) => {
                                    e.stopPropagation();
                                    if (e.key === 'Escape') {
                                        setOpen(false);
                                    }
                                }}
                            />
                        </div>
                        {filteredOptions.length > 0 && (
                            <div className="mt-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={handleSelectAll}
                                    className="w-full"
                                >
                                    {isAllSelected ? 'Bỏ chọn tất cả' : 'Chọn tất cả'}
                                </Button>
                            </div>
                        )}
                    </div>
                    <ScrollArea className="h-[300px]">
                        <div className="p-1">
                            {filteredOptions.length === 0 ? (
                                <div className="py-6 text-center text-sm text-muted-foreground">
                                    Không tìm thấy người dùng nào
                                </div>
                            ) : (
                                filteredOptions.map((option) => {
                                    const isSelected = value.includes(option.id);
                                    return (
                                        <div
                                            key={option.id}
                                            className="flex items-center space-x-2 rounded-sm px-2 py-1.5 hover:bg-accent cursor-pointer"
                                            onClick={() => handleToggleUser(option.id)}
                                        >
                                            <Checkbox
                                                checked={isSelected}
                                                onCheckedChange={() => handleToggleUser(option.id)}
                                                onClick={(e) => e.stopPropagation()}
                                            />
                                            <div className="flex-1">
                                                <div className="text-sm font-medium">{option.name}</div>
                                                <div className="text-xs text-muted-foreground">
                                                    {option.username} • {option.email}
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })
                            )}
                        </div>
                    </ScrollArea>
                </PopoverContent>
            </Popover>

            {/* Hiển thị danh sách users đã chọn */}
            {selectedUsers.length > 0 && (
                <div className="space-y-2">
                    <Label className="text-sm font-medium">Người dùng đã chọn ({selectedUsers.length}):</Label>
                    <div className="flex flex-wrap gap-2">
                        {selectedUsers.map((user) => (
                            <Badge key={user.id} variant="secondary" className="text-xs">
                                {user.name} ({user.username})
                                <button
                                    type="button"
                                    onClick={() => handleRemoveUser(user.id)}
                                    className="ml-1 rounded-full hover:bg-muted"
                                >
                                    <X className="h-3 w-3" />
                                </button>
                            </Badge>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}


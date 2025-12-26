import * as React from 'react';
import { Search } from 'lucide-react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';

type TimezoneOption = {
    value: string;
    label: string;
};

type TimezoneSelectProps = {
    value?: string;
    onValueChange: (value: string) => void;
    options: TimezoneOption[];
    placeholder?: string;
    id?: string;
};

export function TimezoneSelect({
    value,
    onValueChange,
    options,
    placeholder = 'Chọn múi giờ',
    id,
}: TimezoneSelectProps) {
    const [searchQuery, setSearchQuery] = React.useState('');
    const [open, setOpen] = React.useState(false);

    const filteredOptions = React.useMemo(() => {
        if (!searchQuery.trim()) {
            return options;
        }
        const query = searchQuery.toLowerCase();
        return options.filter(
            (option) =>
                option.label.toLowerCase().includes(query) ||
                option.value.toLowerCase().includes(query)
        );
    }, [options, searchQuery]);

    const selectedOption = React.useMemo(() => {
        return options.find((opt) => opt.value === value);
    }, [options, value]);

    return (
        <Select
            value={value}
            onValueChange={onValueChange}
            open={open}
            onOpenChange={setOpen}
        >
            <SelectTrigger id={id}>
                <SelectValue placeholder={placeholder}>
                    {selectedOption?.label || placeholder}
                </SelectValue>
            </SelectTrigger>
            <SelectContent className="p-0">
                <div className="sticky top-0 z-10 bg-white border-b p-2">
                    <div className="relative">
                        <Search className="absolute left-2 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            placeholder="Tìm kiếm múi giờ..."
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
                </div>
                <ScrollArea className="h-[300px]">
                    <div className="p-1">
                        {filteredOptions.length === 0 ? (
                            <div className="py-6 text-center text-sm text-muted-foreground">
                                Không tìm thấy múi giờ nào
                            </div>
                        ) : (
                            filteredOptions.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))
                        )}
                    </div>
                </ScrollArea>
            </SelectContent>
        </Select>
    );
}


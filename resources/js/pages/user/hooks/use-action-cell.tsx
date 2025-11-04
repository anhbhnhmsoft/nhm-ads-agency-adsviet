import { useCallback } from 'react';
import { Button } from '@/components/ui/button';
import { Pencil, Trash2, Info } from 'lucide-react';

type MinimalRow = { id: number; disabled?: boolean };

type UseActionCellParams<T extends MinimalRow> = {
    onToggle?: (row: T) => void;
    onEdit?: (row: T) => void;
    onDelete?: (row: T) => void;
    onView?: (row: T) => void;
    canDelete?: boolean;
    getToggleText?: (disabled: boolean) => string;
};

export function useActionCell<T extends MinimalRow>({
    onToggle,
    onEdit,
    onDelete,
    onView,
    canDelete = false,
    getToggleText,
}: UseActionCellParams<T>) {
    return useCallback(
        (row: T) => {
            const disabled = !!row.disabled;
            return (
                <div className="flex items-center justify-center gap-2">
                    {onToggle && (
                        <Button
                            type="button"
                            variant={disabled ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => onToggle(row)}
                        >
                            {getToggleText ? getToggleText(disabled) : disabled ? 'Active' : 'Disable'}
                        </Button>
                    )}
                   {onView && (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => onView(row)}
                        >
                            <Info className="size-4" />
                        </Button>
                    )}
                    {onEdit && (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => onEdit(row)}
                        >
                            <Pencil className="size-4" />
                        </Button>
                    )}
                    {canDelete && onDelete && (
                        <Button
                            type="button"
                            variant="destructive"
                            size="sm"
                            onClick={() => onDelete(row)}
                        >
                            <Trash2 className="size-4" />
                        </Button>
                    )}
                </div>
            );
        },
        [onToggle, onEdit, onDelete, onView, canDelete, getToggleText]
    );
}



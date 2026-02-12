
import { ColumnDef, flexRender, getCoreRowModel, useReactTable, RowSelectionState, OnChangeFn } from '@tanstack/react-table';
import { useState } from 'react';

import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { LaravelPaginator } from '@/lib/types/type';
import { DataTablePagination } from '@/components/table/pagination';
import { cn } from '@/lib/utils';
declare module '@tanstack/react-table' {
    // @ts-ignore - Extending existing interface
    interface ColumnMeta {
        headerClassName?: string;
        cellClassName?: string;
    }
}

interface DataTableProps<TData, TValue> {
    columns: ColumnDef<TData, TValue>[];
    paginator: LaravelPaginator<TData>;
    onRowClick?: (row: TData) => void;
    rowSelection?: RowSelectionState;
    onRowSelectionChange?: OnChangeFn<RowSelectionState>;
}

export function DataTable<TData, TValue>({ 
    columns, 
    paginator, 
    onRowClick,
    rowSelection,
    onRowSelectionChange,
}: DataTableProps<TData, TValue>) {
    const [internalRowSelection, setInternalRowSelection] = useState<RowSelectionState>({});
    
    const selectionState = rowSelection !== undefined ? rowSelection : internalRowSelection;

    const handleRowSelectionChange: OnChangeFn<RowSelectionState> = (updaterOrValue) => {
        if (onRowSelectionChange) {
            onRowSelectionChange(updaterOrValue);
            return;
        }

        setInternalRowSelection((prev) =>
            typeof updaterOrValue === 'function'
                ? (updaterOrValue as (old: RowSelectionState) => RowSelectionState)(prev)
                : updaterOrValue,
        );
    };

    const table = useReactTable({
        data: paginator.data,
        columns,
        getCoreRowModel: getCoreRowModel(),
        enableRowSelection: true,
        onRowSelectionChange: handleRowSelectionChange,
        state: {
            rowSelection: selectionState,
        },
    });

    return (
       <div>
           <div className="overflow-hidden rounded-md border mb-4 bg-white">
               <Table>
                   <TableHeader>
                       {table.getHeaderGroups().map((headerGroup) => (
                           <TableRow key={headerGroup.id}>
                               {headerGroup.headers.map((header) => {
                                   return (
                                       <TableHead 
                                           key={header.id}
                                           className={cn(
                                               header.isPlaceholder 
                                                   ? '' 
                                                   : header.column.columnDef.meta?.headerClassName
                                           )}
                                       >
                                           {header.isPlaceholder ? null : flexRender(header.column.columnDef.header, header.getContext())}
                                       </TableHead>
                                   );
                               })}
                           </TableRow>
                       ))}
                   </TableHeader>
                   <TableBody>
                       {table.getRowModel().rows?.length ? (
                           table.getRowModel().rows.map((row) => (
                               <TableRow 
                                   key={row.id} 
                                   data-state={row.getIsSelected() && 'selected'}
                                   className={onRowClick ? 'cursor-pointer hover:bg-muted/50' : ''}
                                   onClick={() => onRowClick?.(row.original)}
                               >
                                   {row.getVisibleCells().map((cell) => (
                                       <TableCell 
                                           key={cell.id}
                                           className={cn(cell.column.columnDef.meta?.cellClassName)}
                                       >
                                           {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                       </TableCell>
                                   ))}
                               </TableRow>
                           ))
                       ) : (
                           <TableRow>
                               <TableCell colSpan={columns.length} className="h-24 text-center">
                                   Không có dữ liệu nào để hiển thị.
                               </TableCell>
                           </TableRow>
                       )}
                   </TableBody>
               </Table>
           </div>
           <DataTablePagination paginator={paginator}  />
       </div>

    );
}

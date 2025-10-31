
import { ColumnDef, flexRender, getCoreRowModel, useReactTable } from '@tanstack/react-table';

import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { LaravelPaginator } from '@/lib/types/type';
import { DataTablePagination } from '@/components/table/pagination';

interface DataTableProps<TData, TValue> {
    columns: ColumnDef<TData, TValue>[];
    paginator: LaravelPaginator<TData>;
}

export function DataTable<TData, TValue>({ columns, paginator }: DataTableProps<TData, TValue>) {
    const table = useReactTable({
        data: paginator.data,
        columns,
        getCoreRowModel: getCoreRowModel(),
    });

    return (
       <div>
           <div className="overflow-hidden rounded-md border mb-4">
               <Table>
                   <TableHeader>
                       {table.getHeaderGroups().map((headerGroup) => (
                           <TableRow key={headerGroup.id}>
                               {headerGroup.headers.map((header) => {
                                   return (
                                       <TableHead key={header.id}>
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
                               <TableRow key={row.id} data-state={row.getIsSelected() && 'selected'}>
                                   {row.getVisibleCells().map((cell) => (
                                       <TableCell key={cell.id}>{flexRender(cell.column.columnDef.cell, cell.getContext())}</TableCell>
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

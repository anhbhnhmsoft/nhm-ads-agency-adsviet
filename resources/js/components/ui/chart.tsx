import * as React from 'react';
import { ResponsiveContainer, Tooltip as RechartsTooltip } from 'recharts';
import type { TooltipProps } from 'recharts';
import type { NameType, ValueType } from 'recharts/types/component/DefaultTooltipContent';
import { cn } from '@/lib/utils';

type ChartContainerProps = React.ComponentProps<'div'> & {
    height?: number;
    children: React.ReactElement;
};

export function ChartContainer({ children, className, height = 256, ...props }: ChartContainerProps) {
    return (
        <div className={cn('w-full', className)} style={{ height }} {...props}>
            <ResponsiveContainer width="100%" height="100%">
                {children}
            </ResponsiveContainer>
        </div>
    );
}

type ChartTooltipProps = TooltipProps<ValueType, NameType> & {
    content: React.ReactElement;
};

export function ChartTooltip({ content, ...props }: ChartTooltipProps) {
    return (
        <RechartsTooltip
            wrapperStyle={{ outline: 'none' }}
            cursor={{ fill: 'hsl(var(--muted))', opacity: 0.12 }}
            content={content}
            {...props}
        />
    );
}

type ChartTooltipContentProps = TooltipProps<ValueType, NameType> & {
    formatter?: (value: ValueType | null | undefined, name?: NameType, entry?: any) => React.ReactNode;
};

export function ChartTooltipContent({ active, payload, label, formatter }: ChartTooltipContentProps) {
    if (!active || !payload?.length) {
        return null;
    }

    return (
        <div className="rounded-md border bg-background/95 px-3 py-2 text-xs shadow-md">
            {label && <div className="mb-2 font-semibold">{label}</div>}
            <div className="grid gap-1">
                {payload.map((entry) => (
                    <div
                        key={`${entry.dataKey}-${entry.name}`}
                        className="flex items-center justify-between gap-4"
                    >
                        <span className="text-muted-foreground">{entry.name ?? entry.dataKey}</span>
                        <span className="font-semibold">
                            {formatter ? formatter(entry.value ?? 0, entry.name, entry) : entry.value}
                        </span>
                    </div>
                ))}
            </div>
        </div>
    );
}


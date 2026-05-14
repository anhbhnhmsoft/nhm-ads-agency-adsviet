import { format } from "date-fns"
import { Calendar as CalendarIcon } from "lucide-react"
import { DateRange } from "react-day-picker"
import { useEffect, useState } from "react"
import { useTranslation } from "react-i18next"

import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import { Calendar } from "@/components/ui/calendar"
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover"

interface DateRangePickerProps {
  date?: DateRange
  onDateChange?: (date: DateRange | undefined) => void
  className?: string
}

export function DateRangePicker({
  date,
  onDateChange,
  className,
}: DateRangePickerProps) {
  const { t } = useTranslation()
  const [open, setOpen] = useState(false)
  const [draftDate, setDraftDate] = useState<DateRange | undefined>(date)

  useEffect(() => {
    setDraftDate(date)
  }, [date])

  const displayDate = date?.from && date.to
    ? date.from.toDateString() === date.to.toDateString()
      ? format(date.from, "LLL dd, y")
      : `${format(date.from, "LLL dd, y")} - ${format(date.to, "LLL dd, y")}`
    : date?.from
      ? format(date.from, "LLL dd, y")
      : null

  const handleApply = () => {
    if (draftDate?.from) {
      onDateChange?.({
        from: draftDate.from,
        to: draftDate.to ?? draftDate.from,
      })
    } else {
      onDateChange?.(undefined)
    }
    setOpen(false)
  }

  const handleClear = () => {
    setDraftDate(undefined)
    onDateChange?.(undefined)
    setOpen(false)
  }

  return (
    <div className={cn("grid gap-2", className)}>
      <Popover open={open} onOpenChange={setOpen}>
        <PopoverTrigger asChild>
          <Button
            id="date"
            variant={"outline"}
            className={cn(
              "w-[300px] justify-start text-left font-normal",
              !date && "text-muted-foreground"
            )}
          >
            <CalendarIcon className="mr-2 h-4 w-4" />
            {displayDate ? (
              displayDate
            ) : (
              <span>{t('common.pick_date_range', { defaultValue: 'Pick a date range' })}</span>
            )}
          </Button>
        </PopoverTrigger>
        <PopoverContent className="w-auto p-0" align="start">
          <Calendar
            mode="range"
            defaultMonth={draftDate?.from}
            selected={draftDate}
            onSelect={setDraftDate}
            numberOfMonths={2}
            className="rounded-lg border shadow-sm"
          />
          <div className="flex items-center justify-end gap-2 border-t p-3">
            <Button type="button" variant="outline" size="sm" onClick={handleClear}>
              {t('common.clear', { defaultValue: 'Clear' })}
            </Button>
            <Button type="button" size="sm" onClick={handleApply}>
              {t('common.apply', { defaultValue: 'Apply' })}
            </Button>
          </div>
        </PopoverContent>
      </Popover>
    </div>
  )
}

import { ChevronLeft, ChevronRight } from "lucide-react"
import * as React from "react"
import { DayPicker } from "react-day-picker"
import { zhCN } from "react-day-picker/locale"

import { buttonVariants } from "@/components/ui/button"
import { currentLanguage } from "@/lib/i18n"
import { cn } from "@/lib/utils"

/**
 * A month calendar on react-day-picker, styled with the theme tokens. The
 * selected day is the primary colour and today has the grey `accent` fill; a
 * day is a ghost button, so hover and press follow the Button's rules.
 */
function Calendar({
  className,
  classNames,
  showOutsideDays = true,
  ...props
}: React.ComponentProps<typeof DayPicker>) {
  return (
    <DayPicker
      data-slot="calendar"
      // Month and day names, and the screen-reader labels, in the page's language.
      locale={currentLanguage() === "zh_CN" ? zhCN : undefined}
      showOutsideDays={showOutsideDays}
      className={cn("p-3", className)}
      classNames={{
        root: "w-fit",
        months: "relative flex flex-col gap-4",
        month: "flex flex-col gap-4",
        month_caption: "flex h-9 items-center justify-center",
        caption_label: "text-sm font-medium",
        nav: "absolute inset-x-0 top-0 flex h-9 items-center justify-between",
        button_previous: cn(
          buttonVariants({ variant: "ghost", size: "icon" }),
          "size-9"
        ),
        button_next: cn(
          buttonVariants({ variant: "ghost", size: "icon" }),
          "size-9"
        ),
        month_grid: "w-full border-collapse",
        weekdays: "flex",
        weekday: "w-9 text-center text-xs font-normal text-muted-foreground",
        week: "mt-1 flex w-full",
        day: "p-0 text-center text-sm",
        day_button: cn(
          buttonVariants({ variant: "ghost" }),
          "size-9 p-0 font-normal aria-selected:opacity-100"
        ),
        selected:
          "[&>button]:bg-primary [&>button]:text-primary-foreground [&>button]:hover:bg-primary [&>button]:active:bg-primary-pressed [&>button]:hover:text-primary-foreground",
        today: "[&>button:not([aria-selected])]:bg-accent",
        outside: "text-muted-foreground opacity-60",
        disabled: "text-muted-foreground opacity-50",
        hidden: "invisible",
        ...classNames,
      }}
      components={{
        Chevron: ({ orientation, className: chevron }) =>
          orientation === "left" ? (
            <ChevronLeft className={cn("size-4", chevron)} />
          ) : (
            <ChevronRight className={cn("size-4", chevron)} />
          ),
      }}
      {...props}
    />
  )
}

export { Calendar }

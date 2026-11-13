import * as React from "react"
import {
  Legend,
  ResponsiveContainer,
  Tooltip,
  type DefaultLegendContentProps,
  type TooltipContentProps,
} from "recharts"

import { cn } from "@/lib/utils"

/**
 * One entry per series. `color` is a theme token such as `var(--chart-1)`;
 * the series are coloured through `var(--color-<key>)` (patterns/report.md).
 */
export type ChartConfig = Record<string, { label: string; color: string }>

const ChartContext = React.createContext<ChartConfig | null>(null)

function useChart() {
  const config = React.useContext(ChartContext)

  if (!config) {
    throw new Error("useChart must be used inside a <ChartContainer />")
  }

  return config
}

/**
 * Sizes a chart to its box, defines `--color-<key>` for every series and gives
 * the axes, grid and tooltip the theme's muted and border colours.
 */
function ChartContainer({
  config,
  className,
  children,
  ...props
}: Omit<React.ComponentProps<"div">, "children"> & {
  config: ChartConfig
  children: React.ComponentProps<typeof ResponsiveContainer>["children"]
}) {
  const colors = Object.fromEntries(
    Object.entries(config).map(([key, item]) => [`--color-${key}`, item.color])
  ) as React.CSSProperties

  return (
    <ChartContext.Provider value={config}>
      <div
        data-slot="chart"
        style={colors}
        className={cn(
          "h-64 w-full text-xs [&_.recharts-cartesian-axis-tick_text]:fill-muted-foreground [&_.recharts-cartesian-grid_line]:stroke-border [&_.recharts-curve.recharts-tooltip-cursor]:stroke-border [&_.recharts-rectangle.recharts-tooltip-cursor]:fill-muted [&_.recharts-surface]:outline-none",
          className
        )}
        {...props}
      >
        <ResponsiveContainer width="100%" height="100%" initialDimension={{ width: 320, height: 256 }}>
          {children}
        </ResponsiveContainer>
      </div>
    </ChartContext.Provider>
  )
}

const ChartTooltip = Tooltip

/** The tooltip box: a popover with the series name, its colour and the exact figure. */
function ChartTooltipContent({
  active,
  payload,
  label,
  formatValue = (value) => String(value),
}: Partial<TooltipContentProps<number, string>> & {
  formatValue?: (value: number) => string
}) {
  const config = useChart()

  if (!active || !payload?.length) {
    return null
  }

  return (
    <div
      data-slot="chart-tooltip"
      className="grid min-w-32 gap-1.5 rounded-lg border bg-popover px-3 py-2 text-xs text-popover-foreground shadow-md"
    >
      {label ? <div className="font-medium">{label}</div> : null}
      {payload.map((item) => {
        const key = String(item.dataKey ?? item.name)

        return (
          <div key={key} className="flex items-center gap-2">
            <span
              className="size-2 shrink-0 rounded-full"
              style={{ backgroundColor: `var(--color-${key})` }}
            />
            <span className="text-muted-foreground">
              {config[key]?.label ?? item.name}
            </span>
            <span className="ml-auto font-medium tabular-nums">
              {formatValue(Number(item.value))}
            </span>
          </div>
        )
      })}
    </div>
  )
}

const ChartLegend = Legend

/** The legend: one dot and label per series, always shown when there are several. */
function ChartLegendContent({
  payload,
  className,
}: Pick<DefaultLegendContentProps, "payload"> & { className?: string }) {
  const config = useChart()

  if (!payload?.length) {
    return null
  }

  // Recharts sorts the payload; the legend follows the series order of the config.
  const order = Object.keys(config)
  const rank = (item: (typeof payload)[number]) =>
    order.indexOf(String(item.dataKey ?? item.value))
  const items = [...payload].sort((a, b) => rank(a) - rank(b))

  return (
    <div
      data-slot="chart-legend"
      className={cn("flex flex-wrap items-center justify-center gap-4 pt-3 text-xs", className)}
    >
      {items.map((item) => {
        const key = String(item.dataKey ?? item.value)

        return (
          <div key={key} className="flex items-center gap-1.5">
            <span
              className="size-2 shrink-0 rounded-full"
              style={{ backgroundColor: `var(--color-${key})` }}
            />
            {config[key]?.label ?? item.value}
          </div>
        )
      })}
    </div>
  )
}

export {
  ChartContainer,
  ChartLegend,
  ChartLegendContent,
  ChartTooltip,
  ChartTooltipContent,
}

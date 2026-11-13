import * as React from "react"

import { cn } from "@/lib/utils"

function Textarea({ className, ...props }: React.ComponentProps<"textarea">) {
  return (
    <textarea
      data-slot="textarea"
      className={cn(
        "border-input placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground field-sizing-content flex min-h-24 w-full min-w-0 rounded-md border bg-transparent px-3.5 py-2 text-base transition-colors outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm",
        "focus-visible:border-input-focus",
        "aria-invalid:border-destructive aria-invalid:focus-visible:border-destructive",
        className
      )}
      {...props}
    />
  )
}

export { Textarea }

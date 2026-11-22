import { Slot } from "@radix-ui/react-slot"
import { cva, type VariantProps } from "class-variance-authority"
import * as React from "react"

import { cn } from "@/lib/utils"

/*
 * Which variant for which need: `default` is the one main action of a screen,
 * area or dialog; `outline` any other action; `ghost` a quiet action in a
 * toolbar or a row; `secondary` a filled neutral action next to the main one;
 * `destructive` the confirm button of something that cannot be undone; `link`
 * with `size="inline"` an action inside a sentence. A form's button is
 * SubmitButton, an icon-only one IconButton, a row of them Actions.
 */
const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive",
  {
    variants: {
      variant: {
        default:
          "bg-primary text-primary-foreground hover:bg-primary/90 active:bg-primary-pressed",
        destructive:
          "bg-destructive text-white hover:bg-destructive/90 active:bg-destructive-pressed focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40",
        outline:
          "border border-input bg-background hover:bg-accent hover:text-accent-foreground active:bg-accent-pressed",
        secondary:
          "bg-secondary text-secondary-foreground hover:bg-secondary/80 active:bg-secondary-pressed",
        ghost: "hover:bg-accent hover:text-accent-foreground active:bg-accent-pressed",
        link: "text-primary underline-offset-4 hover:underline active:text-primary-pressed",
      },
      size: {
        default: "h-10 px-5 py-2 has-[>svg]:px-4",
        sm: "h-9 rounded-md px-4 has-[>svg]:px-3",
        lg: "h-11 rounded-md px-7 has-[>svg]:px-5",
        icon: "size-10",
        "icon-sm": "size-8",
        inline: "h-auto p-0",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
)

function Button({
  className,
  variant,
  size,
  asChild = false,
  type,
  ...props
}: React.ComponentProps<"button"> &
  VariantProps<typeof buttonVariants> & {
    asChild?: boolean
  }) {
  const Comp = asChild ? Slot : "button"

  return (
    <Comp
      data-slot="button"
      // A button sends no form unless it says so: only SubmitButton does.
      type={asChild ? type : (type ?? "button")}
      className={cn(buttonVariants({ variant, size, className }))}
      {...props}
    />
  )
}

export { Button, buttonVariants }

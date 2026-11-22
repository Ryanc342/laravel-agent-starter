import type { ComponentProps, ComponentType, ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

/**
 * A button that shows only an icon: close, more actions, the next month. Use
 * it whenever a button has no visible text. `label` is required: it is the
 * button's name for screen readers and the tooltip shown on hover and focus.
 * When the button opens a menu, a sheet or a popover, pass that component's
 * trigger as `trigger` (DropdownMenuTrigger), so the tooltip stays outside it.
 */
export default function IconButton({
    label,
    trigger: Trigger,
    children,
    variant = 'ghost',
    size = 'icon',
    ...props
}: Omit<ComponentProps<typeof Button>, 'aria-label' | 'children' | 'size'> & {
    label: string;
    trigger?: ComponentType<{ asChild?: boolean; children?: ReactNode }>;
    /** The icon, and a count badge on it when there is one. */
    children: ReactNode;
    size?: 'icon' | 'icon-sm';
}) {
    const button = (
        <Button variant={variant} size={size} aria-label={label} {...props}>
            {children}
        </Button>
    );

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                {Trigger ? <Trigger asChild>{button}</Trigger> : button}
            </TooltipTrigger>
            <TooltipContent>{label}</TooltipContent>
        </Tooltip>
    );
}

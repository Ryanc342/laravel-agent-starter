import type { ComponentProps, ReactNode } from 'react';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn } from '@/lib/utils';

type Props = {
    title: string;
    /** Only for a step the user must take or an unusual state; leave it out otherwise. */
    description?: string;
    /** An icon above the title; the header is then centred. */
    icon?: ReactNode;
    /** A narrower dialog on desktop, for a short content such as a QR code. */
    narrow?: boolean;
    /** The element that opens the dialog; leave it out when the page controls `open`. */
    trigger?: ReactNode;
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
    children: ReactNode;
};

/**
 * Create, edit and confirm in one place: a centred dialog on desktop, a
 * full-width sheet that slides up from the bottom on a phone. Put the fields in
 * ResponsiveDialogBody and the buttons in ResponsiveDialogFooter; a long form
 * scrolls inside the body while the title and the buttons stay in view.
 */
export default function ResponsiveDialog({
    title,
    description,
    icon,
    narrow = false,
    trigger,
    open,
    onOpenChange,
    children,
}: Props) {
    const isMobile = useIsMobile();

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            {trigger && <DialogTrigger asChild>{trigger}</DialogTrigger>}
            <DialogContent
                sheet={isMobile}
                className={cn('flex flex-col', narrow && 'sm:max-w-md')}
                // Radix warns about a missing description; opting out is
                // explicit, and only when there really is none.
                {...(description ? {} : { 'aria-describedby': undefined })}
            >
                <DialogHeader
                    className={cn(icon && 'items-center text-center')}
                >
                    {icon}
                    <DialogTitle>{title}</DialogTitle>
                    {description && (
                        <DialogDescription>{description}</DialogDescription>
                    )}
                </DialogHeader>
                {children}
            </DialogContent>
        </Dialog>
    );
}

/** The scrolling part of the dialog: the fields. */
export function ResponsiveDialogBody({
    className,
    ...props
}: ComponentProps<'div'>) {
    return (
        <div
            className={cn(
                '-mx-1 -my-1 flex min-h-0 flex-1 flex-col gap-6 overflow-y-auto px-1 py-1',
                className,
            )}
            {...props}
        />
    );
}

/**
 * The buttons at the right end, Cancel first and the main one last. They stay
 * in view; on a phone each takes the full width, stacked with the main one on
 * top.
 */
export function ResponsiveDialogFooter({
    className,
    ...props
}: ComponentProps<typeof DialogFooter>) {
    return (
        <DialogFooter
            className={cn('shrink-0 max-md:*:w-full', className)}
            {...props}
        />
    );
}

/** Wrap the Cancel button: it closes the dialog. */
export const ResponsiveDialogClose = DialogClose;

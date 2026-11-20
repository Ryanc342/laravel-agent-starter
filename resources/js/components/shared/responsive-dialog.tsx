import { useRef, type ComponentProps, type ReactNode } from 'react';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import Actions from '@/components/shared/actions';
import { useIsMobile } from '@/hooks/use-mobile';
import { cn } from '@/lib/utils';

/**
 * The width on desktop, one of the two or neither (both at once does not
 * compile): `narrow` for a short content such as a QR code, `wide` for a long
 * form with a table of lines (an invoice). On a phone every dialog is the same
 * full-width sheet.
 */
type Width =
    | { narrow?: boolean; wide?: false }
    | { narrow?: false; wide?: boolean };

type Props = Width & {
    title: string;
    /**
     * One sentence for a step the user must take (the two-factor setup says what
     * to do); a ConfirmDialog passes its `consequence` here. Leave it out otherwise.
     */
    instruction?: string;
    /** An icon above the title; the header is then centred. */
    icon?: ReactNode;
    /** The element that opens the dialog; leave it out when the page controls `open`. */
    trigger?: ReactNode;
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
    children: ReactNode;
};

/**
 * Create, edit and confirm in one place: a centred dialog on desktop, a
 * full-width sheet that slides up from the bottom on a phone. Put the fields in
 * ResponsiveDialogBody and the buttons in ResponsiveDialogFooter (`Actions`); a long form
 * scrolls inside the body while the title and the buttons stay in view.
 */
export default function ResponsiveDialog({
    title,
    instruction,
    icon,
    narrow = false,
    wide = false,
    trigger,
    open,
    onOpenChange,
    children,
}: Props) {
    const isMobile = useIsMobile();
    // Without a trigger (a menu item or the page opened it), Radix gives focus
    // to no one when the dialog closes: give it back to what had it.
    const returnTo = useRef<HTMLElement | null>(null);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            {trigger && <DialogTrigger asChild>{trigger}</DialogTrigger>}
            <DialogContent
                sheet={isMobile}
                data-size={narrow ? 'narrow' : wide ? 'wide' : 'default'}
                className={cn(
                    'flex flex-col',
                    narrow && 'sm:max-w-md',
                    wide && 'sm:max-w-3xl',
                )}
                onOpenAutoFocus={() => {
                    returnTo.current =
                        document.activeElement instanceof HTMLElement
                            ? document.activeElement
                            : null;
                }}
                onCloseAutoFocus={(event) => {
                    if (!trigger && returnTo.current?.isConnected) {
                        event.preventDefault();
                        returnTo.current.focus();
                    }
                }}
                // Radix warns about a missing description; opting out is
                // explicit, and only when there really is none.
                {...(instruction ? {} : { 'aria-describedby': undefined })}
            >
                <DialogHeader
                    className={cn(icon && 'items-center text-center')}
                >
                    {icon}
                    <DialogTitle>{title}</DialogTitle>
                    {instruction && (
                        <DialogDescription>{instruction}</DialogDescription>
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
                // The body scrolls as a whole; its blocks keep their height (*:shrink-0),
                // so a block that scrolls on its own, such as a table, is not squeezed.
                '-mx-1 -my-1 flex min-h-0 flex-1 flex-col gap-6 overflow-y-auto px-1 py-1 *:shrink-0',
                className,
            )}
            {...props}
        />
    );
}

/**
 * The buttons, as `Actions`: Cancel first and the main one last. They stay in
 * view while a long body scrolls.
 */
export function ResponsiveDialogFooter({
    className,
    ...props
}: ComponentProps<typeof Actions>) {
    return <Actions className={cn('shrink-0', className)} {...props} />;
}

/** Wrap the Cancel button: it closes the dialog. */
export const ResponsiveDialogClose = DialogClose;

import { useId, type ComponentProps } from 'react';
import { FloatingLabel, floatingControl } from '@/components/shared/form-field';
import { Input } from '@/components/ui/input';
import { useUrlParam } from '@/hooks/use-url-param';
import { cn } from '@/lib/utils';

/**
 * A search box tied to one URL parameter: the list's `search` in the toolbar
 * and in a text column's menu. Use it wherever typing filters a list. It asks
 * for the list after a pause in typing or at Enter. Its label is required and
 * floats inside the box.
 */
export default function UrlSearchInput({
    param,
    label,
    id,
    className,
    ...props
}: { param: string; label: string } & Omit<
    ComponentProps<typeof Input>,
    'value' | 'onChange' | 'onKeyDown' | 'type' | 'placeholder' | 'size'
>) {
    const fallbackId = useId();
    const inputId = id ?? fallbackId;
    const { inputProps } = useUrlParam(param);

    return (
        <div className={cn('relative', className)}>
            <Input
                id={inputId}
                type="search"
                placeholder=" "
                className={floatingControl}
                {...props}
                {...inputProps}
            />
            <FloatingLabel htmlFor={inputId}>{label}</FloatingLabel>
        </div>
    );
}

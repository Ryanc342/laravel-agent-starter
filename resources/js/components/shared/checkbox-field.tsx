import { useId, type ComponentProps } from 'react';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

/**
 * A yes-or-no choice with its label to the right: "Remember me", "Send me the
 * weekly summary". Use it for one checkbox with a label; pressing the label
 * ticks the box.
 */
export default function CheckboxField({
    label,
    id,
    ...props
}: Omit<ComponentProps<typeof Checkbox>, 'children'> & { label: string }) {
    const fallbackId = useId();
    const checkboxId = id ?? fallbackId;

    return (
        <div className="flex items-center gap-3">
            <Checkbox id={checkboxId} {...props} />
            <Label htmlFor={checkboxId}>{label}</Label>
        </div>
    );
}

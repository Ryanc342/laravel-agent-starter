import { Bell } from 'lucide-react';
import { toast } from 'sonner';
import { Specimen } from '@/components/style/style-section';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { NETWORK_ERROR_MESSAGE } from '@/hooks/use-network-error-toast';

const variants = [
    'default',
    'secondary',
    'outline',
    'ghost',
    'destructive',
    'link',
] as const;

// A status starts with a capital letter, as the backend sends it.
const badges = [
    { variant: 'default', label: 'Default' },
    { variant: 'secondary', label: 'Open' },
    { variant: 'outline', label: 'Outline' },
    { variant: 'success', label: 'Done' },
    { variant: 'warning', label: 'Due soon' },
    { variant: 'destructive', label: 'Overdue' },
] as const;

/** Button, Badge, Spinner, Tooltip and toasts: what the user presses and what tells them. */
export default function UiActionsDemo() {
    return (
        <div className="grid gap-8">
            <Specimen label="Button variants: one default per area, outline for the rest">
                {variants.map((variant) => (
                    <Button key={variant} variant={variant}>
                        {variant}
                    </Button>
                ))}
            </Specimen>

            <Specimen label="Button sizes">
                <Button size="sm">Small</Button>
                <Button>Default</Button>
                <Button size="lg">Large</Button>
                <span className="text-sm text-muted-foreground">
                    or in a sentence:{' '}
                    <Button variant="link" size="inline">
                        inline
                    </Button>
                </span>
            </Specimen>

            <Specimen label="Button states: disabled, and sending (SubmitButton under Shared blocks does this itself)">
                <Button disabled>Disabled</Button>
                <Button disabled>
                    <Spinner />
                    Saving
                </Button>
                <Button variant="outline" disabled>
                    Disabled
                </Button>
            </Specimen>

            <Specimen label="Tooltip: a hint for a control, shown on hover and focus">
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Button variant="outline">
                            <Bell />
                            Hover me
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Notifications</TooltipContent>
                </Tooltip>
            </Specimen>

            <Specimen label="Badge: a status, in the words the backend sends">
                {badges.map(({ variant, label }) => (
                    <Badge key={variant} variant={variant}>
                        {label}
                    </Badge>
                ))}
            </Specimen>

            <Specimen label="Toasts: the result of an action and a network error">
                <Button
                    variant="outline"
                    onClick={() => toast.success('Task created.')}
                >
                    Success toast
                </Button>
                <Button
                    variant="outline"
                    onClick={() => toast.error(NETWORK_ERROR_MESSAGE)}
                >
                    Error toast
                </Button>
            </Specimen>
        </div>
    );
}

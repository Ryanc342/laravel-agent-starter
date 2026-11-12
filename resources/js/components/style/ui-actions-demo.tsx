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

const variants = [
    'default',
    'secondary',
    'outline',
    'ghost',
    'destructive',
    'link',
] as const;

const badges = [
    'default',
    'secondary',
    'outline',
    'success',
    'warning',
    'destructive',
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

            <Specimen label="Button states: disabled, and sending (disabled with a Spinner)">
                <Button disabled>Disabled</Button>
                <Button disabled>
                    <Spinner />
                    Saving
                </Button>
                <Button variant="outline" disabled>
                    Disabled
                </Button>
            </Specimen>

            <Specimen label="Icon button: always a Tooltip and an aria-label">
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label="Notifications"
                        >
                            <Bell />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Notifications</TooltipContent>
                </Tooltip>
            </Specimen>

            <Specimen label="Badge: a status, in the words the backend sends">
                {badges.map((variant) => (
                    <Badge key={variant} variant={variant}>
                        {variant}
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
                    onClick={() =>
                        toast.error(
                            'Cannot reach the server. Check your connection and try again.',
                        )
                    }
                >
                    Error toast
                </Button>
            </Specimen>
        </div>
    );
}

import { Eye, EyeOff } from 'lucide-react';
import type { ComponentProps, Ref } from 'react';
import { useState } from 'react';
import { Input } from '@/components/ui/input';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { t } from '@/lib/i18n';

export default function PasswordInput({
    className,
    ref,
    ...props
}: Omit<ComponentProps<'input'>, 'type' | 'size'> & {
    ref?: Ref<HTMLInputElement>;
}) {
    const [showPassword, setShowPassword] = useState(false);
    const label = showPassword ? t('Hide password') : t('Show password');

    return (
        <div className="relative">
            <Input
                type={showPassword ? 'text' : 'password'}
                size="end-icon"
                className={className}
                ref={ref}
                {...props}
            />
            <Tooltip>
                <TooltipTrigger asChild>
                    <button
                        type="button"
                        onClick={() => setShowPassword((prev) => !prev)}
                        className="absolute inset-y-0 right-0 flex items-center rounded-r-md px-3 text-muted-foreground hover:text-foreground focus-visible:ring-3 focus-visible:ring-ring focus-visible:outline-none active:text-primary-pressed"
                        aria-label={label}
                        tabIndex={-1}
                    >
                        {showPassword ? (
                            <EyeOff className="size-4" />
                        ) : (
                            <Eye className="size-4" />
                        )}
                    </button>
                </TooltipTrigger>
                <TooltipContent>{label}</TooltipContent>
            </Tooltip>
        </div>
    );
}

import AppLogoIcon from '@/components/app-logo-icon';
import FormField from '@/components/shared/form-field';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { style } from '@/routes';

type AuthField = {
    id: string;
    label: string;
    type: string;
    placeholder: string;
};

/**
 * A page a signed-out person sees: the narrow centred column of the auth layout,
 * with the label above each input. The pages use the real AuthLayout; this frame
 * shows the same column inside the overview page.
 */
export default function AuthDemo({
    title,
    fields,
    submitLabel,
    footer,
    footerLink,
}: {
    title: string;
    fields: AuthField[];
    submitLabel: string;
    footer: string;
    footerLink: string;
}) {
    return (
        <div className="mx-auto flex w-full max-w-sm flex-col gap-8">
            <div className="flex flex-col items-center gap-4">
                <AppLogoIcon className="size-9 fill-current text-foreground" />
                <h1 className="text-xl font-medium">{title}</h1>
            </div>

            <form
                onSubmit={(event) => event.preventDefault()}
                className="flex flex-col gap-6"
            >
                {fields.map((field) => (
                    <FormField
                        key={field.id}
                        label={field.label}
                        htmlFor={`auth-demo-${field.id}`}
                    >
                        <Input
                            id={`auth-demo-${field.id}`}
                            type={field.type}
                            placeholder={field.placeholder}
                        />
                    </FormField>
                ))}
                <Button type="submit" className="mt-2 w-full">
                    {submitLabel}
                </Button>
            </form>

            <div className="text-center text-sm text-muted-foreground">
                {footer}{' '}
                <TextLink href={`${style().url}#auth`}>{footerLink}</TextLink>
            </div>
        </div>
    );
}

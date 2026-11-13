import { Form } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/heading';
import PasswordInput from '@/components/password-input';
import FormField from '@/components/shared/form-field';
import ResponsiveDialog, {
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogFooter,
} from '@/components/shared/responsive-dialog';
import { Button } from '@/components/ui/button';

export default function DeleteUser() {
    return (
        <div className="space-y-6">
            <Heading variant="small" title="Delete account" />
            <div className="space-y-4 rounded-lg border border-destructive/20 bg-destructive/5 p-4">
                <div className="relative space-y-0.5 text-destructive-foreground">
                    <p className="font-medium">Warning</p>
                    <p className="text-sm">
                        Please proceed with caution, this cannot be undone.
                    </p>
                </div>

                <ResponsiveDialog
                    trigger={
                        <Button
                            variant="destructive"
                            data-test="delete-user-button"
                        >
                            Delete account
                        </Button>
                    }
                    title="Are you sure you want to delete your account?"
                    description="Once your account is deleted, all of its resources and data will also be permanently deleted. Please enter your password to confirm you would like to permanently delete your account."
                >
                    <Form
                        {...ProfileController.destroy.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        resetOnSuccess
                        className="flex min-h-0 flex-1 flex-col gap-6"
                    >
                        {({ resetAndClearErrors, processing, errors }) => (
                            <>
                                <ResponsiveDialogBody>
                                    <FormField
                                        label="Password"
                                        htmlFor="password"
                                        hideLabel
                                        error={errors.password}
                                    >
                                        <PasswordInput
                                            id="password"
                                            name="password"
                                            placeholder="Password"
                                            autoComplete="current-password"
                                        />
                                    </FormField>
                                </ResponsiveDialogBody>

                                <ResponsiveDialogFooter>
                                    <Button
                                        variant="destructive"
                                        disabled={processing}
                                        asChild
                                    >
                                        <button
                                            type="submit"
                                            data-test="confirm-delete-user-button"
                                        >
                                            Delete account
                                        </button>
                                    </Button>

                                    <ResponsiveDialogClose asChild>
                                        <Button
                                            variant="outline"
                                            onClick={() =>
                                                resetAndClearErrors()
                                            }
                                        >
                                            Cancel
                                        </Button>
                                    </ResponsiveDialogClose>
                                </ResponsiveDialogFooter>
                            </>
                        )}
                    </Form>
                </ResponsiveDialog>
            </div>
        </div>
    );
}

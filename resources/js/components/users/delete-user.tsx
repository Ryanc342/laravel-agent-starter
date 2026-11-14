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
                description="Enter your password to permanently delete your account and all of its data."
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
                                <ResponsiveDialogClose asChild>
                                    <Button
                                        variant="outline"
                                        onClick={() => resetAndClearErrors()}
                                    >
                                        Cancel
                                    </Button>
                                </ResponsiveDialogClose>
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
                            </ResponsiveDialogFooter>
                        </>
                    )}
                </Form>
            </ResponsiveDialog>
        </div>
    );
}

import { Link, useForm, usePage } from '@inertiajs/react';
import { update as updateProfile } from '@/actions/App/Http/Controllers/AccountController';
import { store as changeEmail } from '@/actions/App/Http/Controllers/Auth/EmailChangeController';
import { update as updatePassword } from '@/actions/App/Http/Controllers/Auth/PasswordController';
import Alert from '@/components/booktrips/alert';
import { Field, Input } from '@/components/booktrips/field';
import PasswordRules from '@/components/booktrips/password-rules';
import { withAppLayout } from '@/layouts/app-layout';
import type { SharedProps } from '@/types/booktrips';
import type { InertiaComponent } from '@/types/inertia';

type AccountProps = { bookingCount: number; reviewCount: number };

const Account: InertiaComponent<AccountProps> = ({ bookingCount }) => {
    const { auth } = usePage<SharedProps>().props;
    const user = auth.user;

    const profileForm = useForm({
        name: user?.name ?? '',
        phone: user?.phone ?? '',
    });

    const emailForm = useForm({ email: '' });

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    return (
        <div className="mx-auto w-[min(560px,calc(100%-2rem))] py-7 pb-14">
            <h1 className="text-4xl">My account</h1>
            <p className="text-muted mb-4">{user?.email}</p>
            {user?.email_verified ? (
                <div className="bg-brand-50 text-brand-950 my-2 mb-2 rounded-xl px-3 py-2.5 text-[13px]">
                    Email verified — you can book.
                </div>
            ) : (
                <div className="text-warn my-2 mb-2 rounded-xl bg-orange-50 px-3 py-2.5 text-[13px]">
                    Email not verified. You cannot book yet.{' '}
                    <Link className="font-bold underline" href="/verify-email">
                        Verify
                    </Link>
                </div>
            )}
            {user?.pending_email ? (
                <Alert tone="note">
                    Waiting on confirmation for{' '}
                    <strong>{user.pending_email}</strong>. Open the link we sent
                    to that address to finish the change.
                </Alert>
            ) : null}
            <p className="text-muted mt-3 text-[13px]">
                {bookingCount} bookings on this account.
            </p>

            <form
                className="mt-5"
                onSubmit={(event) => {
                    event.preventDefault();
                    profileForm.put(updateProfile.url(), {
                        preserveScroll: true,
                    });
                }}
            >
                <h3 className="mb-2 font-sans text-lg font-bold">Profile</h3>
                {profileForm.recentlySuccessful ? (
                    <Alert tone="success">Profile saved.</Alert>
                ) : null}
                {profileForm.errors.name ? (
                    <Alert tone="error">{profileForm.errors.name}</Alert>
                ) : null}
                <Field label="Name">
                    <Input
                        value={profileForm.data.name}
                        onChange={(event) =>
                            profileForm.setData('name', event.target.value)
                        }
                    />
                </Field>
                <Field label="Phone">
                    <Input
                        value={profileForm.data.phone}
                        onChange={(event) =>
                            profileForm.setData('phone', event.target.value)
                        }
                    />
                </Field>
                <button
                    type="submit"
                    className="bg-brand-800 hover:bg-brand-900 cursor-pointer rounded-full px-4.5 py-2.5 text-sm font-bold text-white transition disabled:opacity-55"
                    disabled={profileForm.processing}
                >
                    Save
                </button>
            </form>

            <form
                className="mt-7"
                onSubmit={(event) => {
                    event.preventDefault();
                    emailForm.post(changeEmail.url(), { preserveScroll: true });
                }}
            >
                <h3 className="mb-2 font-sans text-lg font-bold">
                    Reset email
                </h3>
                <p className="text-muted mb-2 text-[13px]">
                    We’ll send a confirmation link to the new address.
                </p>
                {emailForm.recentlySuccessful ? (
                    <Alert tone="success">
                        We sent a confirmation link to the new address.
                    </Alert>
                ) : null}
                {emailForm.errors.email ? (
                    <Alert tone="error">{emailForm.errors.email}</Alert>
                ) : null}
                <Field label="New email">
                    <Input
                        type="email"
                        value={emailForm.data.email}
                        onChange={(event) =>
                            emailForm.setData('email', event.target.value)
                        }
                    />
                </Field>
                <button
                    type="submit"
                    className="border-line text-brand-900 hover:border-brand-700 cursor-pointer rounded-full border bg-white px-4.5 py-2.5 text-sm font-bold transition disabled:opacity-55"
                    disabled={emailForm.processing}
                >
                    Send confirmation
                </button>
            </form>

            <form
                className="mt-7"
                onSubmit={(event) => {
                    event.preventDefault();
                    passwordForm.post(updatePassword.url(), {
                        preserveScroll: true,
                        onSuccess: () => passwordForm.reset(),
                    });
                }}
            >
                <h3 className="mb-2 font-sans text-lg font-bold">
                    Reset password
                </h3>
                {passwordForm.recentlySuccessful ? (
                    <Alert tone="success">Password updated.</Alert>
                ) : null}
                {passwordForm.errors.current_password ? (
                    <Alert tone="error">
                        {passwordForm.errors.current_password}
                    </Alert>
                ) : null}
                {passwordForm.errors.password ? (
                    <Alert tone="error">{passwordForm.errors.password}</Alert>
                ) : null}
                <Field label="Current password">
                    <Input
                        type="password"
                        value={passwordForm.data.current_password}
                        onChange={(event) =>
                            passwordForm.setData(
                                'current_password',
                                event.target.value,
                            )
                        }
                    />
                </Field>
                <Field label="New password">
                    <Input
                        type="password"
                        value={passwordForm.data.password}
                        onChange={(event) =>
                            passwordForm.setData('password', event.target.value)
                        }
                    />
                </Field>
                <PasswordRules password={passwordForm.data.password} />
                <Field label="Confirm new password">
                    <Input
                        type="password"
                        value={passwordForm.data.password_confirmation}
                        onChange={(event) =>
                            passwordForm.setData(
                                'password_confirmation',
                                event.target.value,
                            )
                        }
                    />
                </Field>
                <button
                    type="submit"
                    className="border-line text-brand-900 hover:border-brand-700 cursor-pointer rounded-full border bg-white px-4.5 py-2.5 text-sm font-bold transition disabled:opacity-55"
                    disabled={passwordForm.processing}
                >
                    Update password
                </button>
            </form>

            <p className="mt-6">
                <Link
                    className="text-brand-800 font-bold"
                    href="/account/bookings"
                >
                    My bookings
                </Link>
            </p>
        </div>
    );
};

Account.layout = withAppLayout;

export default Account;

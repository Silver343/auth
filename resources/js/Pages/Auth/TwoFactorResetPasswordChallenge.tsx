import TwoFactorChallengeForm from '@/Components/TwoFactorChallengeForm';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head } from '@inertiajs/react';

export default function TwoFactorResetPasswordChallenge({
    token,
    email,
}: {
    token: string;
    email: string;
}) {
    return (
        <GuestLayout>
            <Head title="Two-factor Confirmation" />
            <TwoFactorChallengeForm
                onSubmit={() => {}}
                submitRoute={route('password.reset.two-factor.confirm')}
                initialData={{ token, email }}
            />
        </GuestLayout>
    );
}

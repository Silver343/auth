import TwoFactorChallengeForm from '@/Components/TwoFactorChallengeForm';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head } from '@inertiajs/react';

export default function TwoFactorLoginChallenge() {
    return (
        <GuestLayout>
            <Head title="Two-factor Confirmation" />
            <TwoFactorChallengeForm
                onSubmit={() => {}}
                submitRoute={route('two-factor.login')}
            />
        </GuestLayout>
    );
}

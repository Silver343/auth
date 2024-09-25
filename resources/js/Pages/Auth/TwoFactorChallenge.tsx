import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useRef, useState } from 'react';

export default function TwoFactorChallenge() {
    const [recovery, setRecovery] = useState<boolean>(false);
    const recoveryInput = useRef<HTMLInputElement>(null);
    const codeInput = useRef<HTMLInputElement>(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        code: '',
        recovery_code: '',
    });

    const toggleRecovery = () => {
        setRecovery(!recovery);

        if (recovery) {
            recoveryInput.current?.focus();
            reset('code');
        } else {
            codeInput.current?.focus();
            reset('recovery_code');
        }
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('two-factor.login'));
    };
    console.log(errors.code);
    return (
        <GuestLayout>
            <Head title="Two-factor Confirmation" />

            <div className="mb-4 text-sm text-gray-600 dark:text-gray-400">
                {!recovery ? (
                    <>
                        Please confirm access to your account by entering the
                        authentication code provided by your authenticator
                        application.
                    </>
                ) : (
                    <>
                        Please confirm access to your account by entering one of
                        your emergency recovery codes.
                    </>
                )}
            </div>

            <form onSubmit={submit}>
                {!recovery ? (
                    <div>
                        <InputLabel htmlFor="code" value="Code" />
                        <TextInput
                            id="code"
                            ref={codeInput}
                            type="text"
                            inputMode="numeric"
                            name="code"
                            value={data.code}
                            className="mt-1 block w-full"
                            autoComplete="one-time-code"
                            isFocused={true}
                            onChange={(e) => setData('code', e.target.value)}
                        />
                        <InputError message={errors.code} className="mt-2" />
                    </div>
                ) : (
                    <div>
                        <InputLabel
                            htmlFor="recovery_code"
                            value="Recovery Code"
                        />
                        <TextInput
                            id="recovery_code"
                            ref={recoveryInput}
                            type="text"
                            name="recovery code"
                            value={data.recovery_code}
                            className="mt-1 block w-full"
                            autoComplete="one-time-code"
                            onChange={(e) =>
                                setData('recovery_code', e.target.value)
                            }
                        />
                        <InputError
                            message={errors.recovery_code}
                            className="mt-2"
                        />
                    </div>
                )}

                <div className="mt-4 flex items-center justify-end">
                    <SecondaryButton onClick={toggleRecovery}>
                        {!recovery ? (
                            <>Use a recovery code</>
                        ) : (
                            <>Use a authentication code</>
                        )}
                    </SecondaryButton>

                    <PrimaryButton className="ms-4" disabled={processing}>
                        Log in
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}

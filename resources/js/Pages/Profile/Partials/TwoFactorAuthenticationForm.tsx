import ConfirmsPassword from '@/Components/ConfirmsPassword';
import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { PageProps } from '@/types';
import { useForm, usePage } from '@inertiajs/react';
import axios from 'axios';
import { useState } from 'react';

function renderSVG(SVG: string | null) {
    if (!SVG) {
        return;
    }
    return { __html: SVG };
}

export default function TwoFactorAuthenticationForm({
    className = '',
}: {
    className?: string;
}) {
    const user = usePage<PageProps>().props.auth.user;

    const {
        data,
        setData,
        post,
        errors,
        reset,
        clearErrors,
        delete: destroy,
    } = useForm({
        code: '',
    });

    const [enabling, setEnabling] = useState<boolean>(false);
    const [confirming, setConfirming] = useState<boolean>(false);
    const [disabling, setDisabling] = useState<boolean>(false);
    const [qrCode, setQrCode] = useState<null | string>(null);
    const [setupKey, setSetupKey] = useState<null | string>(null);
    const [recoveryCodes, setRecoveryCodes] = useState<string[]>([]);

    const twoFactorEnabled = !enabling && user.two_factor_enabled;

    const qrCodeSVG = renderSVG(qrCode);

    const enableTwoFactorAuthentication = () => {
        setEnabling(true);

        post(route('two-factor.enable'), {
            preserveScroll: true,
            onSuccess: () =>
                Promise.all([
                    showQrCode(),
                    showSetupKey(),
                    showRecoveryCodes(),
                ]),
            onFinish: () => {
                setEnabling(false);
                setConfirming(true);
            },
        });
    };

    const showQrCode = async () => {
        const response = await axios.get(route('two-factor.qr-code'));
        setQrCode(response.data.svg);
    };

    const showSetupKey = async () => {
        const response = await axios.get(route('two-factor.secret-key'));
        setSetupKey(response.data.secretKey);
    };

    const showRecoveryCodes = async () => {
        const response = await axios.get(route('two-factor.recovery-codes'));
        setRecoveryCodes(response.data);
    };

    const confirmTwoFactorAuthentication = () => {
        post(route('two-factor.confirm'), {
            errorBag: 'confirmTwoFactorAuthentication',
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                setConfirming(false);
                setQrCode(null);
                setSetupKey(null);
                reset();
                clearErrors();
            },
        });
    };

    const regenerateRecoveryCodes = () => {
        axios
            .post(route('two-factor.recovery-codes'))
            .then(() => showRecoveryCodes());
    };

    const disableTwoFactorAuthentication = () => {
        setDisabling(true);

        destroy(route('two-factor.disable'), {
            preserveScroll: true,
            onSuccess: () => {
                setDisabling(false);
                setConfirming(false);
                reset();
                clearErrors();
            },
        });
    };

    return (
        <section className={className}>
            <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                Two Factor Authentication
            </h2>

            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Add additional security to your account using two factor
                authentication.
            </p>

            <div className="mt-5">
                <div className="bg-white px-4 py-5 shadow sm:rounded-lg sm:p-6 dark:bg-gray-800">
                    {twoFactorEnabled && !confirming && (
                        <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                            You have enabled two factor authentication.
                        </h3>
                    )}
                    {twoFactorEnabled && confirming && (
                        <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Finish enabling two factor authentication.
                        </h3>
                    )}
                    {!twoFactorEnabled && !confirming && (
                        <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                            You have not enabled two factor authentication
                        </h3>
                    )}
                    <div className="mt-3 max-w-xl text-sm text-gray-600 dark:text-gray-400">
                        <p>
                            When two factor authentication is enabled, you will
                            be prompted for a secure, random token during
                            authentication. You may retrieve this token from
                            your phone's Google Authenticator application.
                        </p>
                    </div>
                    {twoFactorEnabled && (
                        <div>
                            {qrCode && (
                                <div>
                                    <div className="mt-4 max-w-xl text-sm text-gray-600 dark:text-gray-400">
                                        {confirming ? (
                                            <p className="font-semibold">
                                                To finish enabling two factor
                                                authentication, scan the
                                                following QR code using your
                                                phone's authenticator
                                                application or enter the setup
                                                key and provide the generated
                                                OTP code.
                                            </p>
                                        ) : (
                                            <p>
                                                {' '}
                                                Two factor authentication is now
                                                enabled. Scan the following QR
                                                code using your phone's
                                                authenticator application or
                                                enter the setup key.
                                            </p>
                                        )}
                                    </div>
                                    <div
                                        className="mt-4 inline-block bg-white p-2"
                                        dangerouslySetInnerHTML={qrCodeSVG}
                                    />
                                    {setupKey && (
                                        <div className="mt-4 max-w-xl text-sm text-gray-600 dark:text-gray-400">
                                            <p className="font-semibold">
                                                SetUp Key:{' '}
                                                <span>{setupKey}</span>
                                            </p>
                                        </div>
                                    )}
                                    {confirming && (
                                        <div className="mt-4">
                                            <InputLabel
                                                htmlFor="code"
                                                value="code"
                                            />
                                            <TextInput
                                                id="code"
                                                type="text"
                                                inputMode="numeric"
                                                name="code"
                                                value={data.code}
                                                className="mt-1 block w-full"
                                                autoComplete="one-time-code"
                                                isFocused={true}
                                                onChange={(e) =>
                                                    setData(
                                                        'code',
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={errors.code}
                                                className="mt-2"
                                            />
                                        </div>
                                    )}
                                </div>
                            )}
                            {recoveryCodes.length > 0 && !confirming && (
                                <div>
                                    <div className="mt-4 max-w-xl text-sm text-gray-600 dark:text-gray-400">
                                        <p className="font-semibold">
                                            Store these recovery codes in a
                                            secure password manager. They can be
                                            used to recover access to your
                                            account if your two factor
                                            authentication device is lost.
                                        </p>
                                    </div>
                                    <div className="mt-4 grid max-w-xl gap-1 rounded-lg bg-gray-100 px-4 py-4 font-mono text-sm dark:bg-gray-900 dark:text-gray-100">
                                        {recoveryCodes.map((code) => (
                                            <div key={code}>{code}</div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                    <div className="mt-5">
                        {!twoFactorEnabled ? (
                            <div>
                                <ConfirmsPassword
                                    confirmed={enableTwoFactorAuthentication}
                                >
                                    <PrimaryButton
                                        type="button"
                                        disabled={enabling}
                                    >
                                        Enable
                                    </PrimaryButton>
                                </ConfirmsPassword>
                            </div>
                        ) : (
                            <div>
                                <ConfirmsPassword
                                    confirmed={confirmTwoFactorAuthentication}
                                >
                                    {confirming && (
                                        <PrimaryButton
                                            type="button"
                                            className="me-3"
                                            disabled={enabling}
                                        >
                                            confirm
                                        </PrimaryButton>
                                    )}
                                </ConfirmsPassword>
                                <ConfirmsPassword
                                    confirmed={regenerateRecoveryCodes}
                                >
                                    {recoveryCodes.length > 0 &&
                                        !confirming && (
                                            <SecondaryButton className="me-3">
                                                Regenerate Recovery Codes
                                            </SecondaryButton>
                                        )}
                                </ConfirmsPassword>
                                <ConfirmsPassword confirmed={showRecoveryCodes}>
                                    {recoveryCodes.length === 0 &&
                                        !confirming && (
                                            <SecondaryButton className="me-3">
                                                Show Recovery Codes
                                            </SecondaryButton>
                                        )}
                                </ConfirmsPassword>
                                <ConfirmsPassword
                                    confirmed={disableTwoFactorAuthentication}
                                >
                                    {confirming && (
                                        <SecondaryButton disabled={disabling}>
                                            Cancel
                                        </SecondaryButton>
                                    )}
                                </ConfirmsPassword>
                                <ConfirmsPassword
                                    confirmed={disableTwoFactorAuthentication}
                                >
                                    {!confirming && (
                                        <DangerButton disabled={disabling}>
                                            Disable
                                        </DangerButton>
                                    )}
                                </ConfirmsPassword>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </section>
    );
}

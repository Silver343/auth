import axios from 'axios';
import { PropsWithChildren, useRef, useState } from 'react';
import InputError from './InputError';
import InputLabel from './InputLabel';
import Modal from './Modal';
import PrimaryButton from './PrimaryButton';
import SecondaryButton from './SecondaryButton';
import TextInput from './TextInput';

export default function ConfirmsPassword({
    children,
    title = 'Confirm Password',
    content = 'For your security, please confirm your password to continue.',
    button = 'Confirm',
    confirmed = () => {},
}: PropsWithChildren<{
    title?: string;
    content?: string;
    button?: string;
    confirmed: CallableFunction;
}>) {
    const [confirmingPassword, setConfirmingPassword] = useState(false);
    const passwordInput = useRef<HTMLInputElement>(null);

    const [form, setForm] = useState<{
        password: string;
        error: string;
        processing: boolean;
    }>({
        password: '',
        error: '',
        processing: false,
    });

    const startConfirmingPassword = () => {
        axios.get(route('password.confirmation')).then((response) => {
            if (response.data.confirmed) {
                confirmed();
            } else {
                setConfirmingPassword(true);

                setTimeout(() => passwordInput.current?.focus(), 250);
            }
        });
    };

    const confirmPassword = () => {
        setForm({ ...form, processing: true });

        axios
            .post(route('password.confirm'), {
                password: form.password,
            })
            .then(() => {
                setForm({ ...form, processing: false });

                closeModal();
                confirmed();
            })
            .catch((error) => {
                setForm({
                    ...form,
                    processing: false,
                    error: error.response.data.errors.password[0],
                });

                passwordInput.current?.focus();
            });
    };

    const closeModal = () => {
        setConfirmingPassword(false);
        setForm({
            ...form,
            password: '',
            error: '',
        });
    };

    return (
        <span>
            <span onClick={startConfirmingPassword}>{children}</span>
            <Modal show={confirmingPassword} onClose={closeModal}>
                <form onSubmit={confirmPassword} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {title}
                    </h2>

                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {content}
                    </p>

                    <div className="mt-6">
                        <InputLabel
                            htmlFor="password"
                            value="Password"
                            className="sr-only"
                        />

                        <TextInput
                            id="password"
                            type="password"
                            name="password"
                            ref={passwordInput}
                            value={form.password}
                            onChange={(e) =>
                                setForm({ ...form, password: e.target.value })
                            }
                            className="mt-1 block w-3/4"
                            isFocused
                            autoComplete="current-password"
                            placeholder="Password"
                        />

                        <InputError message={form.error} className="mt-2" />

                        <div className="mt-6 flex justify-end">
                            <SecondaryButton onClick={closeModal}>
                                Cancel
                            </SecondaryButton>

                            <PrimaryButton
                                className="ms-3"
                                disabled={form.processing}
                                onClick={confirmPassword}
                            >
                                {button}
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </Modal>
        </span>
    );
}

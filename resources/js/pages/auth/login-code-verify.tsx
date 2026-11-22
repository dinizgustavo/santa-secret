import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useState } from 'react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { create, store, verify } from '@/routes/login-code';
import type { AuthLayoutProps } from '@/types';

type Props = {
    email: string;
    codeLength: number;
};

export default function LoginCodeVerify({ email, codeLength }: Props) {
    const { t } = useTranslation();
    const [code, setCode] = useState('');

    setLayoutProps({ descriptionParams: { email } });

    return (
        <>
            <Head title={t('auth.login_code_verify.head')} />

            <Form
                {...verify.form()}
                resetOnError
                onError={() => setCode('')}
                className="flex flex-col items-center gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="flex flex-col items-center gap-3">
                            <InputOTP
                                name="code"
                                maxLength={codeLength}
                                value={code}
                                onChange={setCode}
                                disabled={processing}
                                pattern={REGEXP_ONLY_DIGITS}
                                inputMode="numeric"
                                autoComplete="one-time-code"
                                autoFocus
                            >
                                <InputOTPGroup>
                                    {Array.from(
                                        { length: codeLength },
                                        (_, index) => (
                                            <InputOTPSlot
                                                key={index}
                                                index={index}
                                            />
                                        ),
                                    )}
                                </InputOTPGroup>
                            </InputOTP>
                            <InputError message={errors.code} />
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing || code.length < codeLength}
                            data-test="verify-login-code-button"
                        >
                            {processing && <Spinner />}
                            {t('auth.login_code_verify.submit')}
                        </Button>
                    </>
                )}
            </Form>

            <div className="flex flex-col items-center gap-2 text-sm">
                <Form
                    {...store.form()}
                    transform={() => ({ email })}
                    options={{ preserveScroll: true }}
                >
                    {({ processing }) => (
                        <Button
                            type="submit"
                            variant="link"
                            className="h-auto p-0"
                            disabled={processing}
                        >
                            {t('auth.login_code_verify.resend')}
                        </Button>
                    )}
                </Form>
                <TextLink href={create()}>
                    {t('auth.login_code_verify.other_email')}
                </TextLink>
            </div>
        </>
    );
}

LoginCodeVerify.layout = {
    title: 'auth.login_code_verify.title',
    description: 'auth.login_code_verify.description',
} satisfies AuthLayoutProps;

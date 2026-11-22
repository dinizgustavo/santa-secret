import { Form, Head, setLayoutProps } from '@inertiajs/react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { login } from '@/routes';
import { store } from '@/routes/login-code';
import type { AuthLayoutProps } from '@/types';

type Props = {
    email: string | null;
    codeLength: number;
};

export default function LoginCode({ email, codeLength }: Props) {
    const { t } = useTranslation();

    setLayoutProps({ descriptionParams: { length: codeLength } });

    return (
        <>
            <Head title={t('auth.login_code.head')} />

            <Form {...store.form()} className="flex flex-col gap-6">
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="email">
                                {t('common.fields.email')}
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autoFocus
                                autoComplete="email"
                                defaultValue={email ?? ''}
                                placeholder={t('common.placeholders.email')}
                            />
                            <InputError message={errors.email} />
                        </div>

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                            data-test="send-login-code-button"
                        >
                            {processing && <Spinner />}
                            {t('auth.login_code.submit')}
                        </Button>
                    </>
                )}
            </Form>

            <div className="text-center text-sm">
                <TextLink href={login()}>
                    {t('auth.login_code.back_to_login')}
                </TextLink>
            </div>
        </>
    );
}

LoginCode.layout = {
    title: 'auth.login_code.title',
    description: 'auth.login_code.description',
} satisfies AuthLayoutProps;

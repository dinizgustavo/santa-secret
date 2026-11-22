import { Link } from '@inertiajs/react';
import { Mail } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useTranslation } from '@/lib/i18n';
import { redirect as googleRedirect } from '@/routes/auth/google';
import { create as loginCodeCreate } from '@/routes/login-code';
import type { SignInOptions as SignInOptionsType } from '@/types';

function GoogleIcon() {
    return (
        <svg viewBox="0 0 24 24" className="size-4" aria-hidden="true">
            <path
                fill="#4285F4"
                d="M23.5 12.27c0-.79-.07-1.54-.2-2.27H12v4.3h6.45a5.52 5.52 0 0 1-2.4 3.62v3h3.88c2.27-2.09 3.57-5.17 3.57-8.65Z"
            />
            <path
                fill="#34A853"
                d="M12 24c3.24 0 5.96-1.07 7.95-2.9l-3.88-3c-1.08.72-2.45 1.15-4.07 1.15-3.13 0-5.78-2.11-6.73-4.96H1.27v3.1A12 12 0 0 0 12 24Z"
            />
            <path
                fill="#FBBC05"
                d="M5.27 14.29a7.2 7.2 0 0 1 0-4.58v-3.1H1.27a12 12 0 0 0 0 10.78l4-3.1Z"
            />
            <path
                fill="#EA4335"
                d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.44-3.44A11.97 11.97 0 0 0 12 0 12 12 0 0 0 1.27 6.61l4 3.1C6.22 6.86 8.87 4.75 12 4.75Z"
            />
        </svg>
    );
}

/**
 * Passwordless sign-in buttons shown above the password form when they are configured.
 */
export default function SignInOptions({
    options,
}: {
    options: SignInOptionsType;
}) {
    const { t } = useTranslation();

    if (!options.google && !options.loginCode) {
        return null;
    }

    return (
        <div className="flex flex-col gap-6">
            <div className="grid gap-3">
                {options.google && (
                    <Button
                        asChild
                        variant="outline"
                        className="w-full"
                        data-test="google-sign-in"
                    >
                        {/* A real navigation, not an Inertia visit: it leaves the app for Google. */}
                        <a href={googleRedirect.url()}>
                            <GoogleIcon />
                            {t('auth.options.google')}
                        </a>
                    </Button>
                )}
                {options.loginCode && (
                    <Button
                        asChild
                        variant="outline"
                        className="w-full"
                        data-test="login-code-sign-in"
                    >
                        <Link href={loginCodeCreate()}>
                            <Mail className="size-4" aria-hidden="true" />
                            {t('auth.options.login_code')}
                        </Link>
                    </Button>
                )}
            </div>

            <div className="relative">
                <div className="absolute inset-0 flex items-center">
                    <Separator className="w-full" />
                </div>
                <div className="relative flex justify-center text-xs uppercase">
                    <span className="bg-background px-2 text-muted-foreground">
                        {t('auth.options.or_password')}
                    </span>
                </div>
            </div>
        </div>
    );
}

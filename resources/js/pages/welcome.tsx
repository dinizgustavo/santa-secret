import { Head, Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import LanguageSwitcher from '@/components/language-switcher';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import { dashboard, login, register } from '@/routes';

export default function Welcome() {
    const { auth } = usePage().props;
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('welcome.head')} />
            <div className="relative flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
                <div className="absolute top-4 right-4">
                    <LanguageSwitcher className="w-auto" />
                </div>

                <main className="flex w-full max-w-sm flex-col items-center gap-8 text-center">
                    <AppLogoIcon className="size-12 fill-current text-[var(--foreground)] dark:text-white" />

                    <div className="space-y-3">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {t('welcome.title')}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {t('welcome.description')}
                        </p>
                    </div>

                    <div className="flex w-full flex-col gap-3">
                        {auth.user ? (
                            <Button asChild className="w-full">
                                <Link href={dashboard()}>
                                    {t('welcome.dashboard')}
                                </Link>
                            </Button>
                        ) : (
                            <>
                                <Button asChild className="w-full">
                                    <Link href={register()}>
                                        {t('welcome.register')}
                                    </Link>
                                </Button>
                                <Button
                                    asChild
                                    variant="outline"
                                    className="w-full"
                                >
                                    <Link href={login()}>
                                        {t('welcome.login')}
                                    </Link>
                                </Button>
                            </>
                        )}
                    </div>
                </main>
            </div>
        </>
    );
}

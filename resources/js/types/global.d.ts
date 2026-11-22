import type { LocaleInfo, Translations } from '@/lib/i18n';
import type { Auth } from '@/types/auth';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            locale: LocaleInfo;
            translations: Translations;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}

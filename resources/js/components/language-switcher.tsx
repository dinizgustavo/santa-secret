import { router } from '@inertiajs/react';
import { Languages } from 'lucide-react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/lib/i18n';
import { update } from '@/routes/locale';

export default function LanguageSwitcher({
    className,
}: {
    className?: string;
}) {
    const { t, locale } = useTranslation();

    const change = (code: string) => {
        if (code === locale.current) {
            return;
        }

        router.put(update.url(), { locale: code }, { preserveScroll: true });
    };

    return (
        <Select value={locale.current} onValueChange={change}>
            <SelectTrigger
                className={className}
                aria-label={t('common.language.label')}
                data-test="language-switcher"
            >
                <Languages className="size-4 opacity-70" aria-hidden="true" />
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                {locale.available.map((option) => (
                    <SelectItem
                        key={option.code}
                        value={option.code}
                        lang={option.code.replace('_', '-')}
                    >
                        {option.name}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

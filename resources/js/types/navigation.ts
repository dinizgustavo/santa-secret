import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import type { TranslationKey } from '@/types/translation-keys';

type Href = NonNullable<InertiaLinkProps['href']>;

/**
 * Static crumbs use a translation key; dynamic ones (e.g. a group name) pass a ready label.
 */
export type BreadcrumbItem =
    | { title: TranslationKey; href: Href }
    | { label: string; href: Href };

export type NavItem = {
    title: TranslationKey;
    href: Href;
    icon?: LucideIcon | null;
    isActive?: boolean;
};

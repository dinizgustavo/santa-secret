import { Head, setLayoutProps } from '@inertiajs/react';
import GroupForm from '@/components/groups/group-form';
import Heading from '@/components/heading';
import { useTranslation } from '@/lib/i18n';
import { dashboard } from '@/routes';
import { edit, show, update } from '@/routes/groups';
import type { BreadcrumbItem } from '@/types';
import type { Budget } from '@/types/groups';

type Props = {
    group: {
        id: string;
        name: string;
        description: string | null;
        budget: Budget;
        event_date: string;
    };
    currencies: string[];
    defaultCurrency: string;
};

export default function EditGroup({
    group,
    currencies,
    defaultCurrency,
}: Props) {
    const { t } = useTranslation();

    setLayoutProps({
        breadcrumbs: [
            { title: 'nav.dashboard', href: dashboard() },
            { label: group.name, href: show(group.id) },
            { title: 'groups.form.edit_head', href: edit(group.id) },
        ] satisfies BreadcrumbItem[],
    });

    return (
        <>
            <Head title={t('groups.form.edit_head')} />

            <div className="max-w-xl p-4">
                <Heading
                    title={t('groups.form.edit_title')}
                    description={t('groups.form.edit_description')}
                />
                <GroupForm
                    action={update(group.id)}
                    group={group}
                    currencies={currencies}
                    defaultCurrency={defaultCurrency}
                    submitLabel={t('groups.form.submit_edit')}
                />
            </div>
        </>
    );
}

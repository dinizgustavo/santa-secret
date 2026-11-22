import { Head } from '@inertiajs/react';
import GroupForm from '@/components/groups/group-form';
import Heading from '@/components/heading';
import { useTranslation } from '@/lib/i18n';
import { dashboard } from '@/routes';
import { create, store } from '@/routes/groups';
import type { BreadcrumbItem } from '@/types';

type Props = {
    currencies: string[];
    defaultCurrency: string;
};

export default function CreateGroup({ currencies, defaultCurrency }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('groups.form.create_head')} />

            <div className="max-w-xl p-4">
                <Heading
                    title={t('groups.form.create_title')}
                    description={t('groups.form.create_description')}
                />
                <GroupForm
                    action={store()}
                    currencies={currencies}
                    defaultCurrency={defaultCurrency}
                    submitLabel={t('groups.form.submit_create')}
                />
            </div>
        </>
    );
}

CreateGroup.layout = {
    breadcrumbs: [
        { title: 'nav.dashboard', href: dashboard() },
        { title: 'groups.form.create_head', href: create() },
    ] satisfies BreadcrumbItem[],
};

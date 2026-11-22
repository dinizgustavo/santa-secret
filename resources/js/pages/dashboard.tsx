import { Head, Link } from '@inertiajs/react';
import { CalendarDays, Gift, Plus, Users } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useFormatters, useTranslation } from '@/lib/i18n';
import { dashboard } from '@/routes';
import { create, show } from '@/routes/groups';
import type { BreadcrumbItem } from '@/types';
import type { GroupSummary } from '@/types/groups';

type Props = {
    groups: GroupSummary[];
};

export default function Dashboard({ groups }: Props) {
    const { t, tc } = useTranslation();
    const format = useFormatters();

    return (
        <>
            <Head title={t('groups.index.head')} />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title={t('groups.index.title')}
                        description={t('groups.index.description')}
                    />
                    <Button asChild>
                        <Link href={create()} data-test="new-group-button">
                            <Plus />
                            {t('groups.index.new')}
                        </Link>
                    </Button>
                </div>

                {groups.length === 0 ? (
                    <Card className="items-center py-12 text-center">
                        <CardHeader className="w-full max-w-md items-center">
                            <div className="mx-auto mb-2 flex size-12 items-center justify-center rounded-full bg-muted">
                                <Gift className="size-6 text-muted-foreground" />
                            </div>
                            <CardTitle>
                                {t('groups.index.empty_title')}
                            </CardTitle>
                            <CardDescription>
                                {t('groups.index.empty_description')}
                            </CardDescription>
                        </CardHeader>
                    </Card>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {groups.map((group) => (
                            <Link
                                key={group.id}
                                href={show(group.id)}
                                className="rounded-xl focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                            >
                                <Card className="h-full transition-colors hover:bg-muted/40">
                                    <CardHeader className="gap-3">
                                        <div className="flex flex-wrap gap-2">
                                            <Badge
                                                variant={
                                                    group.is_drawn
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {group.is_drawn
                                                    ? t('groups.badges.drawn')
                                                    : t('groups.badges.open')}
                                            </Badge>
                                            {group.is_organizer && (
                                                <Badge variant="outline">
                                                    {t(
                                                        'groups.badges.organizer',
                                                    )}
                                                </Badge>
                                            )}
                                        </div>
                                        <CardTitle className="text-lg">
                                            {group.name}
                                        </CardTitle>
                                        <CardDescription className="flex flex-col gap-1">
                                            <span className="flex items-center gap-2">
                                                <CalendarDays className="size-4" />
                                                {format.date(group.event_date)}
                                            </span>
                                            <span className="flex items-center gap-2">
                                                <Users className="size-4" />
                                                {tc(
                                                    'groups.index.participants',
                                                    group.participants_count,
                                                )}
                                            </span>
                                            {group.budget && (
                                                <span className="flex items-center gap-2">
                                                    <Gift className="size-4" />
                                                    {t(
                                                        'groups.show.budget_up_to',
                                                        {
                                                            amount: format.money(
                                                                group.budget
                                                                    .amount,
                                                                group.budget
                                                                    .currency,
                                                            ),
                                                        },
                                                    )}
                                                </span>
                                            )}
                                        </CardDescription>
                                    </CardHeader>
                                </Card>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'nav.dashboard',
            href: dashboard(),
        },
    ] satisfies BreadcrumbItem[],
};

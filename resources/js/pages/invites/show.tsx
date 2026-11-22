import { Form, Head, Link } from '@inertiajs/react';
import { CalendarDays, Gift, Lock, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { useFormatters, useTranslation } from '@/lib/i18n';
import { dashboard } from '@/routes';
import { accept } from '@/routes/invites';
import type { BreadcrumbItem } from '@/types';
import type { Budget } from '@/types/groups';

type Props = {
    token: string;
    group: {
        name: string;
        description: string | null;
        event_date: string;
        budget: Budget;
        organizer_name: string | null;
        participants_count: number;
        is_drawn: boolean;
    };
};

export default function ShowInvite({ token, group }: Props) {
    const { t, tc } = useTranslation();
    const format = useFormatters();

    return (
        <>
            <Head title={t('invites.head')} />

            <div className="flex flex-1 justify-center p-4">
                <Card className="w-full max-w-md">
                    <CardHeader>
                        <CardTitle className="text-xl">
                            {t('invites.title', { group: group.name })}
                        </CardTitle>
                        {group.organizer_name && (
                            <CardDescription>
                                {t('invites.organized_by', {
                                    name: group.organizer_name,
                                })}
                            </CardDescription>
                        )}
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm">
                        {group.description && (
                            <p className="whitespace-pre-line text-muted-foreground">
                                {group.description}
                            </p>
                        )}
                        <p className="flex items-center gap-2">
                            <CalendarDays className="size-4 text-muted-foreground" />
                            {t('groups.show.event_on', {
                                date: format.date(group.event_date),
                            })}
                        </p>
                        <p className="flex items-center gap-2">
                            <Users className="size-4 text-muted-foreground" />
                            {tc(
                                'groups.index.participants',
                                group.participants_count,
                            )}
                        </p>
                        <p className="flex items-center gap-2">
                            <Gift className="size-4 text-muted-foreground" />
                            {group.budget
                                ? t('groups.show.budget_up_to', {
                                      amount: format.money(
                                          group.budget.amount,
                                          group.budget.currency,
                                      ),
                                  })
                                : t('groups.show.no_budget')}
                        </p>
                    </CardContent>
                    <CardFooter className="flex-col items-stretch gap-3">
                        {group.is_drawn ? (
                            <>
                                <div className="flex items-start gap-2 rounded-md bg-muted p-3 text-sm">
                                    <Lock className="mt-0.5 size-4 shrink-0" />
                                    <div>
                                        <p className="font-medium">
                                            {t('invites.closed_title')}
                                        </p>
                                        <p className="text-muted-foreground">
                                            {t('invites.closed_description')}
                                        </p>
                                    </div>
                                </div>
                                <Button asChild variant="outline">
                                    <Link href={dashboard()}>
                                        {t('invites.back')}
                                    </Link>
                                </Button>
                            </>
                        ) : (
                            <Form {...accept.form(token)}>
                                {({ processing }) => (
                                    <Button
                                        type="submit"
                                        className="w-full"
                                        disabled={processing}
                                        data-test="accept-invite-button"
                                    >
                                        {processing && <Spinner />}
                                        {t('invites.join')}
                                    </Button>
                                )}
                            </Form>
                        )}
                    </CardFooter>
                </Card>
            </div>
        </>
    );
}

ShowInvite.layout = {
    breadcrumbs: [
        { title: 'nav.dashboard', href: dashboard() },
    ] satisfies BreadcrumbItem[],
};

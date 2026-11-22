import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { CalendarDays, Gift, LogOut, Pencil, Trash2 } from 'lucide-react';
import ConfirmDialog from '@/components/confirm-dialog';
import DrawCard from '@/components/groups/draw-card';
import ExclusionsCard from '@/components/groups/exclusions-card';
import InviteCard from '@/components/groups/invite-card';
import ParticipantsCard from '@/components/groups/participants-card';
import RevealCard from '@/components/groups/reveal-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useFormatters, useTranslation } from '@/lib/i18n';
import { dashboard } from '@/routes';
import { destroy, edit, leave, show } from '@/routes/groups';
import type { BreadcrumbItem } from '@/types';
import type {
    Exclusion,
    GroupAbilities,
    GroupDetail,
    Participant,
} from '@/types/groups';

type Props = {
    group: GroupDetail;
    me: {
        participant_id: number | null;
        is_organizer: boolean;
        gift_rule: string | null;
    };
    participants: Participant[];
    invite_url: string | null;
    exclusions: Exclusion[];
    can: GroupAbilities;
};

export default function ShowGroup({
    group,
    me,
    participants,
    invite_url,
    exclusions,
    can,
}: Props) {
    const { t } = useTranslation();
    const format = useFormatters();

    setLayoutProps({
        breadcrumbs: [
            { title: 'nav.dashboard', href: dashboard() },
            { label: group.name, href: show(group.id) },
        ] satisfies BreadcrumbItem[],
    });

    const otherNames = participants.filter((p) => !p.is_me).map((p) => p.name);

    return (
        <>
            <Head title={group.name} />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4">
                <div className="space-y-3">
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge
                            variant={group.is_drawn ? 'default' : 'secondary'}
                        >
                            {group.is_drawn
                                ? t('groups.badges.drawn')
                                : t('groups.badges.open')}
                        </Badge>
                        {me.is_organizer && (
                            <Badge variant="outline">
                                {t('groups.badges.organizer')}
                            </Badge>
                        )}
                    </div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {group.name}
                    </h1>
                    <div className="flex flex-col gap-1 text-sm text-muted-foreground sm:flex-row sm:gap-4">
                        <span className="flex items-center gap-2">
                            <CalendarDays className="size-4" />
                            {t('groups.show.event_on', {
                                date: format.date(group.event_date),
                            })}
                        </span>
                        <span className="flex items-center gap-2">
                            <Gift className="size-4" />
                            {group.budget
                                ? t('groups.show.budget_up_to', {
                                      amount: format.money(
                                          group.budget.amount,
                                          group.budget.currency,
                                      ),
                                  })
                                : t('groups.show.no_budget')}
                        </span>
                    </div>
                    {group.description && (
                        <p className="text-sm whitespace-pre-line">
                            {group.description}
                        </p>
                    )}
                    {can.update && (
                        <Button asChild variant="outline" size="sm">
                            <Link href={edit(group.id)}>
                                <Pencil />
                                {t('groups.show.edit')}
                            </Link>
                        </Button>
                    )}
                </div>

                {can.reveal ? (
                    <RevealCard
                        names={otherNames}
                        budget={group.budget}
                        myGiftRule={me.gift_rule}
                    />
                ) : (
                    !group.is_drawn &&
                    !me.is_organizer && (
                        <Card>
                            <CardContent className="space-y-2 text-sm">
                                <p className="text-muted-foreground">
                                    {t('groups.draw.waiting')}
                                </p>
                                {me.gift_rule && (
                                    <p>
                                        {t('groups.participants.your_rule', {
                                            rule: me.gift_rule,
                                        })}
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    )
                )}

                {me.is_organizer && (
                    <DrawCard
                        group={group}
                        participantsCount={participants.length}
                        can={can}
                    />
                )}

                {can.manage_invite && invite_url && (
                    <InviteCard
                        groupId={group.id}
                        groupName={group.name}
                        inviteUrl={invite_url}
                    />
                )}

                <ParticipantsCard
                    groupId={group.id}
                    participants={participants}
                    can={can}
                />

                {can.manage_exclusions && (
                    <ExclusionsCard
                        groupId={group.id}
                        participants={participants}
                        exclusions={exclusions}
                    />
                )}

                {(can.leave || can.delete) && (
                    <div className="flex flex-wrap gap-2 pt-2">
                        {can.leave && (
                            <ConfirmDialog
                                trigger={
                                    <Button variant="outline">
                                        <LogOut />
                                        {t('groups.show.leave')}
                                    </Button>
                                }
                                title={t('groups.show.leave_title')}
                                description={t('groups.show.leave_description')}
                                confirmLabel={t('groups.show.leave')}
                                action={leave(group.id)}
                                destructive
                            />
                        )}
                        {can.delete && (
                            <ConfirmDialog
                                trigger={
                                    <Button
                                        variant="ghost"
                                        className="text-destructive hover:text-destructive"
                                    >
                                        <Trash2 />
                                        {t('groups.show.delete')}
                                    </Button>
                                }
                                title={t('groups.show.delete_title')}
                                description={t(
                                    'groups.show.delete_description',
                                )}
                                confirmLabel={t('groups.show.delete')}
                                action={destroy(group.id)}
                                destructive
                            />
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

import { UserMinus } from 'lucide-react';
import ConfirmDialog from '@/components/confirm-dialog';
import GiftRuleDialog from '@/components/groups/gift-rule-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTranslation } from '@/lib/i18n';
import { destroy } from '@/routes/groups/participants';
import type { GroupAbilities, Participant } from '@/types/groups';

type Props = {
    groupId: string;
    participants: Participant[];
    can: GroupAbilities;
};

export default function ParticipantsCard({
    groupId,
    participants,
    can,
}: Props) {
    const { t, tc } = useTranslation();

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('groups.participants.title')}</CardTitle>
                <CardDescription>
                    {tc('groups.participants.count', participants.length)}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <ul className="divide-y" data-test="participants-list">
                    {participants.map((participant) => (
                        <li
                            key={participant.id}
                            className="flex flex-col gap-2 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div className="min-w-0 space-y-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="truncate font-medium">
                                        {participant.name}
                                    </span>
                                    {participant.is_organizer && (
                                        <Badge variant="outline">
                                            {t('groups.badges.organizer')}
                                        </Badge>
                                    )}
                                    {participant.is_me && (
                                        <Badge variant="secondary">
                                            {t('groups.badges.you')}
                                        </Badge>
                                    )}
                                </div>
                                {participant.gift_rule && (
                                    <p className="text-sm text-muted-foreground">
                                        {t('groups.participants.gift_rule')}:{' '}
                                        {participant.gift_rule}
                                    </p>
                                )}
                            </div>

                            {(can.manage_gift_rules ||
                                (can.manage_participants &&
                                    !participant.is_organizer)) && (
                                <div className="flex shrink-0 gap-1">
                                    {can.manage_gift_rules && (
                                        <GiftRuleDialog
                                            groupId={groupId}
                                            participant={participant}
                                        />
                                    )}
                                    {can.manage_participants &&
                                        !participant.is_organizer && (
                                            <ConfirmDialog
                                                trigger={
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-destructive hover:text-destructive"
                                                    >
                                                        <UserMinus />
                                                        {t(
                                                            'groups.participants.remove',
                                                        )}
                                                    </Button>
                                                }
                                                title={t(
                                                    'groups.participants.remove_title',
                                                    { name: participant.name },
                                                )}
                                                description={t(
                                                    'groups.participants.remove_description',
                                                    { name: participant.name },
                                                )}
                                                confirmLabel={t(
                                                    'groups.participants.remove',
                                                )}
                                                action={destroy({
                                                    group: groupId,
                                                    participant: participant.id,
                                                })}
                                                destructive
                                            />
                                        )}
                                </div>
                            )}
                        </li>
                    ))}
                </ul>
            </CardContent>
        </Card>
    );
}

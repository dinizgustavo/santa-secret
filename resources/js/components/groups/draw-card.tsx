import { Lock, Shuffle, Undo2 } from 'lucide-react';
import ConfirmDialog from '@/components/confirm-dialog';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useFormatters, useTranslation } from '@/lib/i18n';
import { destroy, store } from '@/routes/groups/draw';
import type { GroupAbilities, GroupDetail } from '@/types/groups';

type Props = {
    group: GroupDetail;
    participantsCount: number;
    can: GroupAbilities;
};

/**
 * Organizer-only controls to run or reset the draw.
 */
export default function DrawCard({ group, participantsCount, can }: Props) {
    const { t } = useTranslation();
    const format = useFormatters();
    const enough = participantsCount >= group.min_participants_to_draw;

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    {group.is_drawn && <Lock className="size-4" />}
                    {t('groups.draw.title')}
                </CardTitle>
                <CardDescription>
                    {group.is_drawn && group.drawn_at
                        ? t('groups.draw.done', {
                              date: format.dateTime(group.drawn_at),
                          })
                        : enough
                          ? t('groups.draw.ready')
                          : t('groups.draw.not_enough', {
                                min: group.min_participants_to_draw,
                                count: participantsCount,
                            })}
                </CardDescription>
            </CardHeader>
            <CardContent>
                {can.draw && (
                    <ConfirmDialog
                        trigger={
                            <Button
                                disabled={!enough}
                                data-test="run-draw-button"
                            >
                                <Shuffle />
                                {t('groups.draw.run')}
                            </Button>
                        }
                        title={t('groups.draw.run_title')}
                        description={t('groups.draw.run_description')}
                        confirmLabel={t('groups.draw.run')}
                        action={store(group.id)}
                    />
                )}
                {can.reset_draw && (
                    <ConfirmDialog
                        trigger={
                            <Button
                                variant="outline"
                                data-test="reset-draw-button"
                            >
                                <Undo2 />
                                {t('groups.draw.reset')}
                            </Button>
                        }
                        title={t('groups.draw.reset_title')}
                        description={t('groups.draw.reset_description')}
                        confirmLabel={t('groups.draw.reset')}
                        action={destroy(group.id)}
                        destructive
                    />
                )}
            </CardContent>
        </Card>
    );
}

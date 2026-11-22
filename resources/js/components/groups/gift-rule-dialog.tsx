import { Form } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/lib/i18n';
import { giftRule } from '@/routes/groups/participants';
import type { Participant } from '@/types/groups';

type Props = {
    groupId: string;
    participant: Participant;
};

export default function GiftRuleDialog({ groupId, participant }: Props) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const hasRule = Boolean(participant.gift_rule);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="ghost" size="sm" data-test="gift-rule-button">
                    {hasRule
                        ? t('groups.participants.gift_rule_edit')
                        : t('groups.participants.gift_rule_add')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>
                    {t('groups.participants.gift_rule_title', {
                        name: participant.name,
                    })}
                </DialogTitle>
                <DialogDescription>
                    {t('groups.participants.gift_rule_description', {
                        name: participant.name,
                    })}
                </DialogDescription>
                <Form
                    {...giftRule.form({
                        group: groupId,
                        participant: participant.id,
                    })}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label
                                    htmlFor={`gift_rule_${participant.id}`}
                                    className="sr-only"
                                >
                                    {t('groups.participants.gift_rule')}
                                </Label>
                                <Textarea
                                    id={`gift_rule_${participant.id}`}
                                    name="gift_rule"
                                    maxLength={200}
                                    rows={3}
                                    defaultValue={participant.gift_rule ?? ''}
                                    placeholder={t(
                                        'groups.participants.gift_rule_placeholder',
                                    )}
                                />
                                <InputError message={errors.gift_rule} />
                            </div>
                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        {t('common.actions.cancel')}
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {t('common.actions.save')}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

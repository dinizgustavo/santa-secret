import { Form, router } from '@inertiajs/react';
import { ArrowLeftRight, X } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { destroy, store } from '@/routes/groups/exclusions';
import type { Exclusion, Participant } from '@/types/groups';

type Props = {
    groupId: string;
    participants: Participant[];
    exclusions: Exclusion[];
};

function PersonSelect({
    name,
    label,
    participants,
}: {
    name: string;
    label: string;
    participants: Participant[];
}) {
    const { t } = useTranslation();

    return (
        <div className="grid flex-1 gap-2">
            <Label htmlFor={name}>{label}</Label>
            <Select name={name}>
                <SelectTrigger id={name} className="w-full">
                    <SelectValue
                        placeholder={t('groups.exclusions.select_person')}
                    />
                </SelectTrigger>
                <SelectContent>
                    {participants.map((participant) => (
                        <SelectItem
                            key={participant.id}
                            value={String(participant.id)}
                        >
                            {participant.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}

export default function ExclusionsCard({
    groupId,
    participants,
    exclusions,
}: Props) {
    const { t } = useTranslation();

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('groups.exclusions.title')}</CardTitle>
                <CardDescription>
                    {t('groups.exclusions.description')}
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {exclusions.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        {t('groups.exclusions.empty')}
                    </p>
                ) : (
                    <ul className="divide-y" data-test="exclusions-list">
                        {exclusions.map((exclusion) => (
                            <li
                                key={exclusion.id}
                                className="flex items-center justify-between gap-2 py-2"
                            >
                                <span className="flex min-w-0 items-center gap-2 text-sm">
                                    <span className="truncate">
                                        {exclusion.a.name}
                                    </span>
                                    <ArrowLeftRight className="size-4 shrink-0 text-muted-foreground" />
                                    <span className="truncate">
                                        {exclusion.b.name}
                                    </span>
                                </span>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label={t('groups.exclusions.remove')}
                                    onClick={() =>
                                        router.visit(
                                            destroy({
                                                group: groupId,
                                                exclusion: exclusion.id,
                                            }),
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    <X />
                                </Button>
                            </li>
                        ))}
                    </ul>
                )}

                <Form
                    {...store.form(groupId)}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    className="space-y-3"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="flex flex-col gap-3 sm:flex-row">
                                <PersonSelect
                                    name="participant_a"
                                    label={t('groups.exclusions.person_a')}
                                    participants={participants}
                                />
                                <PersonSelect
                                    name="participant_b"
                                    label={t('groups.exclusions.person_b')}
                                    participants={participants}
                                />
                            </div>
                            <InputError
                                message={
                                    errors.participant_a ?? errors.participant_b
                                }
                            />
                            <Button
                                type="submit"
                                variant="outline"
                                disabled={processing}
                                data-test="add-exclusion-button"
                            >
                                {processing && <Spinner />}
                                {t('groups.exclusions.add')}
                            </Button>
                        </>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}

import type { UrlMethodPair } from '@inertiajs/core';
import { Form } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/lib/i18n';
import type { GroupFormData } from '@/types/groups';

type Props = {
    action: UrlMethodPair;
    group?: GroupFormData;
    currencies: string[];
    defaultCurrency: string;
    submitLabel: string;
};

function todayIso(): string {
    const now = new Date();
    const offset = now.getTimezoneOffset() * 60_000;

    return new Date(now.getTime() - offset).toISOString().slice(0, 10);
}

export default function GroupForm({
    action,
    group,
    currencies,
    defaultCurrency,
    submitLabel,
}: Props) {
    const { t } = useTranslation();

    return (
        <Form
            action={action.url}
            method={action.method}
            options={{ preserveScroll: true }}
            className="space-y-6"
        >
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="name">{t('groups.form.name')}</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            maxLength={100}
                            autoFocus={!group}
                            defaultValue={group?.name}
                            placeholder={t('groups.form.name_placeholder')}
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="event_date">
                            {t('groups.form.event_date')}
                        </Label>
                        <Input
                            id="event_date"
                            name="event_date"
                            type="date"
                            required
                            min={group ? undefined : todayIso()}
                            defaultValue={group?.event_date}
                        />
                        <InputError message={errors.event_date} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="budget_max">
                            {t('groups.form.budget_max')}
                        </Label>
                        <div className="flex gap-2">
                            <Input
                                id="budget_max"
                                name="budget_max"
                                inputMode="decimal"
                                className="flex-1"
                                defaultValue={
                                    group?.budget
                                        ? String(group.budget.amount / 100)
                                        : ''
                                }
                                placeholder="50"
                            />
                            <Select
                                name="budget_currency"
                                defaultValue={
                                    group?.budget?.currency ?? defaultCurrency
                                }
                            >
                                <SelectTrigger
                                    className="w-28"
                                    aria-label={t('groups.form.currency')}
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {currencies.map((currency) => (
                                        <SelectItem
                                            key={currency}
                                            value={currency}
                                        >
                                            {currency}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            {t('groups.form.budget_hint')}
                        </p>
                        <InputError
                            message={
                                errors.budget_max ?? errors.budget_currency
                            }
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">
                            {t('groups.form.description')}
                        </Label>
                        <Textarea
                            id="description"
                            name="description"
                            maxLength={1000}
                            rows={3}
                            defaultValue={group?.description ?? ''}
                            placeholder={t(
                                'groups.form.description_placeholder',
                            )}
                        />
                        <InputError message={errors.description} />
                    </div>

                    <Button
                        type="submit"
                        disabled={processing}
                        className="w-full sm:w-auto"
                        data-test="save-group-button"
                    >
                        {processing && <Spinner />}
                        {submitLabel}
                    </Button>
                </>
            )}
        </Form>
    );
}

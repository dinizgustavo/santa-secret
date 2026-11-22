import { router } from '@inertiajs/react';
import { Eye, EyeOff, Gift, Sparkles } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { useFormatters, useTranslation } from '@/lib/i18n';
import type { Budget, MyMatch } from '@/types/groups';

type Props = {
    /** Names to spin through. The result never depends on them: it always comes from the server. */
    names: string[];
    budget: Budget;
    myGiftRule: string | null;
};

type Phase = 'idle' | 'loading' | 'spinning' | 'revealed' | 'hidden';

const TICKS = 28;

/** Delay before each tick: fast at first, slowing down like a wheel coming to rest. */
function delayFor(tick: number): number {
    const progress = tick / TICKS;

    return 45 + Math.round(380 * progress ** 3);
}

function prefersReducedMotion(): boolean {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

export default function RevealCard({ names, budget, myGiftRule }: Props) {
    const { t } = useTranslation();
    const format = useFormatters();
    const [phase, setPhase] = useState<Phase>('idle');
    const [match, setMatch] = useState<MyMatch | null>(null);
    const [shown, setShown] = useState<string>('?');
    const timer = useRef<number | null>(null);

    useEffect(
        () => () => {
            if (timer.current !== null) {
                window.clearTimeout(timer.current);
            }
        },
        [],
    );

    const spin = (result: MyMatch) => {
        if (prefersReducedMotion() || names.length < 2) {
            setShown(result.receiver_name);
            setPhase('revealed');

            return;
        }

        setPhase('spinning');

        const step = (tick: number) => {
            if (tick >= TICKS) {
                setShown(result.receiver_name);
                setPhase('revealed');

                return;
            }

            setShown(names[tick % names.length]);
            timer.current = window.setTimeout(
                () => step(tick + 1),
                delayFor(tick),
            );
        };

        step(0);
    };

    const reveal = () => {
        if (match) {
            spin(match);

            return;
        }

        setPhase('loading');

        // Partial reload: asks the server for the "my_match" prop only.
        router.reload({
            only: ['my_match'],
            onSuccess: (page) => {
                const result = (page.props.my_match ?? null) as MyMatch | null;

                if (!result) {
                    setPhase('idle');

                    return;
                }

                setMatch(result);
                spin(result);
            },
            onError: () => setPhase('idle'),
        });
    };

    return (
        <Card className="border-primary/30">
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <Gift className="size-5" />
                    {t('groups.reveal.title')}
                </CardTitle>
                <CardDescription>
                    {t('groups.reveal.description')}
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                {(phase === 'spinning' || phase === 'revealed') && (
                    <div className="space-y-1 text-center">
                        {phase === 'revealed' && (
                            <p className="text-sm text-muted-foreground">
                                {t('groups.reveal.result')}
                            </p>
                        )}
                        <div
                            className="flex min-h-16 items-center justify-center overflow-hidden rounded-lg border bg-muted/40 px-4 py-3"
                            aria-hidden={phase === 'spinning'}
                        >
                            <span
                                key={phase === 'spinning' ? shown : 'result'}
                                className={
                                    phase === 'revealed'
                                        ? 'animate-in text-2xl font-semibold tracking-tight duration-300 zoom-in-95'
                                        : 'animate-in text-xl font-medium text-muted-foreground duration-75 slide-in-from-top-2'
                                }
                                data-test={
                                    phase === 'revealed'
                                        ? 'match-name'
                                        : undefined
                                }
                            >
                                {shown}
                            </span>
                        </div>
                        {phase === 'revealed' && (
                            <p className="sr-only" role="status">
                                {t('groups.reveal.result')} {shown}
                            </p>
                        )}
                    </div>
                )}

                {phase === 'revealed' && match && (
                    <div className="space-y-3 text-sm">
                        {match.receiver_gift_rule && (
                            <div className="rounded-md border border-amber-200 bg-amber-50 p-3 text-amber-900 dark:border-amber-200/20 dark:bg-amber-500/10 dark:text-amber-100">
                                <p className="font-medium">
                                    {t('groups.reveal.their_rule', {
                                        name: match.receiver_name,
                                    })}
                                </p>
                                <p>{match.receiver_gift_rule}</p>
                            </div>
                        )}
                        {myGiftRule && (
                            <div className="rounded-md border p-3">
                                <p className="font-medium">
                                    {t('groups.reveal.your_rule')}
                                </p>
                                <p className="text-muted-foreground">
                                    {myGiftRule}
                                </p>
                            </div>
                        )}
                        {budget && (
                            <p className="text-muted-foreground">
                                {t('groups.reveal.budget', {
                                    amount: format.money(
                                        budget.amount,
                                        budget.currency,
                                    ),
                                })}
                            </p>
                        )}
                        <p className="flex items-center gap-2 font-medium">
                            <Sparkles className="size-4" />
                            {t('groups.reveal.keep_secret')}
                        </p>
                    </div>
                )}

                <div className="flex flex-wrap gap-2">
                    {(phase === 'idle' || phase === 'loading') && (
                        <Button
                            size="lg"
                            className="w-full sm:w-auto"
                            onClick={reveal}
                            disabled={phase === 'loading'}
                            data-test="reveal-button"
                        >
                            {phase === 'loading' ? <Spinner /> : <Gift />}
                            {t('groups.reveal.button')}
                        </Button>
                    )}
                    {phase === 'spinning' && (
                        <Button size="lg" className="w-full sm:w-auto" disabled>
                            <Spinner />
                            {t('groups.reveal.spinning')}
                        </Button>
                    )}
                    {phase === 'revealed' && (
                        <Button
                            variant="outline"
                            onClick={() => setPhase('hidden')}
                        >
                            <EyeOff />
                            {t('groups.reveal.hide')}
                        </Button>
                    )}
                    {phase === 'hidden' && (
                        <Button
                            variant="outline"
                            onClick={() => {
                                if (match) {
                                    setShown(match.receiver_name);
                                    setPhase('revealed');
                                }
                            }}
                        >
                            <Eye />
                            {t('groups.reveal.show_again')}
                        </Button>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

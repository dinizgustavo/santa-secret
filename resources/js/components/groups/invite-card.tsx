import { Check, Copy, RefreshCw, Share2 } from 'lucide-react';
import ConfirmDialog from '@/components/confirm-dialog';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useClipboard } from '@/hooks/use-clipboard';
import { useTranslation } from '@/lib/i18n';
import { regenerate } from '@/routes/groups/invite';

type Props = {
    groupId: string;
    groupName: string;
    inviteUrl: string;
};

export default function InviteCard({ groupId, groupName, inviteUrl }: Props) {
    const { t } = useTranslation();
    const [copied, copy] = useClipboard();
    const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(
        t('groups.invite.whatsapp_message', {
            group: groupName,
            url: inviteUrl,
        }),
    )}`;

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('groups.invite.title')}</CardTitle>
                <CardDescription>
                    {t('groups.invite.description')}
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
                <Input
                    readOnly
                    value={inviteUrl}
                    onFocus={(event) => event.currentTarget.select()}
                    aria-label={t('groups.invite.title')}
                    data-test="invite-url"
                />
                <div className="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        onClick={() => void copy(inviteUrl)}
                        data-test="copy-invite-button"
                    >
                        {copied === inviteUrl ? <Check /> : <Copy />}
                        {copied === inviteUrl
                            ? t('groups.invite.copied')
                            : t('groups.invite.copy')}
                    </Button>
                    <Button asChild variant="outline">
                        <a
                            href={whatsappUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Share2 />
                            {t('groups.invite.share_whatsapp')}
                        </a>
                    </Button>
                    <ConfirmDialog
                        trigger={
                            <Button variant="ghost">
                                <RefreshCw />
                                {t('groups.invite.regenerate')}
                            </Button>
                        }
                        title={t('groups.invite.regenerate_title')}
                        description={t('groups.invite.regenerate_description')}
                        confirmLabel={t('groups.invite.regenerate')}
                        action={regenerate(groupId)}
                    />
                </div>
            </CardContent>
        </Card>
    );
}

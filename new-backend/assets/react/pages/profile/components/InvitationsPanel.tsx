import { useEffect, useState } from 'react';
import { QrCode } from 'lucide-react';
import { Pager, Rows, Section, type Listing } from '../../../program/shared';
import { request } from '../../../site/api';
import { formatDate } from '../../../site/format';
import { Button } from '../../../site/ui';
import { OfferLinkDialog } from './OfferLinkDialog';

export function InvitationsPanel({ referralCode, bonusPercent }: { referralCode?: string; bonusPercent: number }) {
  const [inviteOpen, setInviteOpen] = useState(false);
  const [page, setPage] = useState(1);
  const [list, setList] = useState<Listing | null>(null);
  const [error, setError] = useState('');

  useEffect(() => {
    let live = true;
    void request<Listing>(`/v1/program/referrals?page=${page}&sort=created_at&direction=desc`)
      .then((result) => { if (live) setList(result); })
      .catch((cause: unknown) => {
        if (live) setError(cause instanceof Error ? cause.message : 'Не удалось загрузить приглашения');
      });
    return () => { live = false; };
  }, [page]);

  function changePage(next: number) {
    setError('');
    setPage(next);
  }

  const loaded = list?.page === page;

  return (
    <Section title="Приглашения">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <p className="text-sm text-muted-foreground">Получайте {bonusPercent.toLocaleString('ru-RU')}% бонусами за покупки друзей, которых вы пригласили напрямую.</p>
        {referralCode ? (
          <Button type="button" onClick={() => setInviteOpen(true)}>
            <QrCode className="h-4 w-4" aria-hidden="true" />
            Моя ссылка и QR-код
          </Button>
        ) : <p className="text-sm text-muted-foreground">Ссылка для приглашений недоступна.</p>}
      </div>
      {inviteOpen && referralCode && (
        <OfferLinkDialog
          url={`/login?tab=register&ref=${encodeURIComponent(referralCode)}`}
          title="Пригласить друга"
          description="Отправьте ссылку или QR-код для регистрации."
          onClose={() => setInviteOpen(false)}
        />
      )}
      <h3 className="text-base font-medium text-foreground">Приглашённые</h3>
      {error ? <p role="alert" className="rounded-xl bg-red-50 p-4 text-sm text-red-700">{error}</p> : (
        <Rows rows={list?.items ?? []} loading={!loaded} columns={[['name', 'Имя'], ['created_at', 'Дата регистрации']]}
          renderCell={(key, row) => key === 'created_at' ? formatDate(String(row.created_at ?? '')) : undefined} />
      )}
      {(page > 1 || (loaded && list.items.length === list.limit)) && (
        <Pager page={page} setPage={changePage} hasMore={loaded && list.items.length === list.limit} />
      )}
    </Section>
  );
}

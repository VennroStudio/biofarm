import { useMemo, useState } from 'react';
import { withdrawalsApi } from '../api/resources';
import { PendingWithdrawalsTable } from '../features/withdrawals/ui/PendingWithdrawalsTable';
import { ProcessedWithdrawalsTable } from '../features/withdrawals/ui/ProcessedWithdrawalsTable';
import { WithdrawalStats } from '../features/withdrawals/ui/WithdrawalStats';
import { WithdrawalTabs } from '../features/withdrawals/ui/WithdrawalTabs';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { PageHeader } from '../shared/ui';
import type { Withdrawal } from '../types';

type WithdrawalTab = 'pending' | 'processed';

export function AdminWithdrawals() {
  const [withdrawals, setWithdrawals] = useState<Withdrawal[]>([]);
  const [tab, setTab] = useState<WithdrawalTab>('pending');

  async function load() {
    const result = await withdrawalsApi.list();
    setWithdrawals(result.items);
  }

  useLoadOnMount(load);

  const pending = useMemo(() => withdrawals.filter((withdrawal) => withdrawal.status === 'pending'), [withdrawals]);
  const processed = useMemo(() => withdrawals.filter((withdrawal) => withdrawal.status !== 'pending'), [withdrawals]);

  async function setStatus(withdrawal: Withdrawal, status: 'approved' | 'rejected') {
    await withdrawalsApi.setStatus(withdrawal.id, status);
    await load();
  }

  return (
    <>
      <PageHeader title="Заявки на вывод" subtitle="Управление заявками на вывод средств партнёров" />
      <WithdrawalStats pending={pending} processed={processed} />
      <WithdrawalTabs processedCount={processed.length} tab={tab} onChange={setTab} />

      {tab === 'pending' ? (
        <PendingWithdrawalsTable withdrawals={pending} onSetStatus={(withdrawal, status) => void setStatus(withdrawal, status)} />
      ) : (
        <ProcessedWithdrawalsTable withdrawals={processed} />
      )}
    </>
  );
}

import { Check, CheckCircle, Clock, Wallet, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import { withdrawalsApi } from '../api/resources';
import {
  AdminTable,
  Badge,
  Button,
  Card,
  EmptyState,
  PageHeader,
  TableCell,
  TableHead,
  TableHeaderCell,
  TableRow,
} from '../components/ui';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import type { Withdrawal } from '../types';

const money = new Intl.NumberFormat('ru-RU');

function statusBadge(status: Withdrawal['status']) {
  if (status === 'approved') {
    return <Badge tone="green"><CheckCircle className="mr-1 h-3 w-3" />Одобрено</Badge>;
  }
  if (status === 'rejected') {
    return <Badge tone="red">Отклонено</Badge>;
  }
  return <Badge tone="gray">Ожидает</Badge>;
}

export function AdminWithdrawals() {
  const [withdrawals, setWithdrawals] = useState<Withdrawal[]>([]);
  const [tab, setTab] = useState<'pending' | 'processed'>('pending');

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

      <div className="grid gap-4 md:grid-cols-3">
        <Card className="p-6">
          <div className="flex items-center justify-between gap-4">
            <div>
              <p className="text-sm text-[#789083]">Ожидают</p>
              <p className="text-2xl font-bold">{pending.length}</p>
            </div>
            <span className="grid h-12 w-12 place-items-center rounded-full bg-[#fff2bf] text-[#f59e0b]"><Clock className="h-6 w-6" /></span>
          </div>
        </Card>
        <Card className="p-6">
          <div className="flex items-center justify-between gap-4">
            <div>
              <p className="text-sm text-[#789083]">Сумма ожидания</p>
              <p className="text-2xl font-bold">{money.format(pending.reduce((sum, item) => sum + item.amount, 0))} ₽</p>
            </div>
            <span className="grid h-12 w-12 place-items-center rounded-full bg-[#dbeafe] text-[#2563eb]"><Wallet className="h-6 w-6" /></span>
          </div>
        </Card>
        <Card className="p-6">
          <div className="flex items-center justify-between gap-4">
            <div>
              <p className="text-sm text-[#789083]">Выплачено всего</p>
              <p className="text-2xl font-bold">{money.format(processed.filter((item) => item.status === 'approved').reduce((sum, item) => sum + item.amount, 0))} ₽</p>
            </div>
            <span className="grid h-12 w-12 place-items-center rounded-full bg-[#dcfce7] text-[#16a34a]"><CheckCircle className="h-6 w-6" /></span>
          </div>
        </Card>
      </div>

      <div className="mt-6 inline-flex rounded-md bg-[#eef1e8] p-1">
        <button
          type="button"
          className={`rounded-md px-4 py-2 text-sm font-semibold transition ${tab === 'pending' ? 'bg-white text-[#26382d] shadow-sm' : 'text-[#789083]'}`}
          onClick={() => setTab('pending')}
        >
          Ожидают
        </button>
        <button
          type="button"
          className={`rounded-md px-4 py-2 text-sm font-semibold transition ${tab === 'processed' ? 'bg-white text-[#26382d] shadow-sm' : 'text-[#789083]'}`}
          onClick={() => setTab('processed')}
        >
          Обработанные <Badge tone="gray" className="ml-2">{processed.length}</Badge>
        </button>
      </div>

      {tab === 'pending' ? (
        <Card className="mt-6 p-6">
          <div className="mb-6">
            <h2 className="text-2xl font-bold">Заявки на рассмотрении</h2>
            <p className="text-sm text-[#789083]">Проверьте и обработайте заявки партнёров</p>
          </div>
          {pending.length === 0 ? (
            <EmptyState>
              <Wallet className="mx-auto mb-3 h-12 w-12 opacity-60" />
              Нет заявок на вывод
            </EmptyState>
          ) : (
            <div className="overflow-x-auto">
              <AdminTable>
                <TableHead>
                  <tr>
                    <TableHeaderCell>Пользователь</TableHeaderCell>
                    <TableHeaderCell>Карта</TableHeaderCell>
                    <TableHeaderCell>Сумма</TableHeaderCell>
                    <TableHeaderCell>Баланс</TableHeaderCell>
                    <TableHeaderCell>Дата</TableHeaderCell>
                    <TableHeaderCell>Действия</TableHeaderCell>
                  </tr>
                </TableHead>
                <tbody>
                  {pending.map((withdrawal) => (
                    <TableRow key={withdrawal.id}>
                      <TableCell>
                        <p className="font-semibold">{withdrawal.user.name}</p>
                        <p className="text-sm text-[#789083]">{withdrawal.user.email}</p>
                      </TableCell>
                      <TableCell>{withdrawal.user.card_number || 'Не указана'}</TableCell>
                      <TableCell className="font-semibold">{money.format(withdrawal.amount)} ₽</TableCell>
                      <TableCell>{money.format(withdrawal.user.bonus_balance)} ₽</TableCell>
                      <TableCell>{new Date(withdrawal.created_at).toLocaleDateString('ru-RU')}</TableCell>
                      <TableCell>
                        <div className="flex gap-2">
                          <Button size="sm" onClick={() => void setStatus(withdrawal, 'approved')}><Check className="h-4 w-4" />Одобрить</Button>
                          <Button size="icon" variant="danger" onClick={() => void setStatus(withdrawal, 'rejected')}><X className="h-4 w-4" /></Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </tbody>
              </AdminTable>
            </div>
          )}
        </Card>
      ) : (
        <Card className="mt-6 p-6">
          <h2 className="mb-6 text-2xl font-bold">История заявок</h2>
          {processed.length === 0 ? (
            <EmptyState>Нет обработанных заявок</EmptyState>
          ) : (
            <div className="overflow-x-auto">
              <AdminTable>
                <TableHead>
                  <tr>
                    <TableHeaderCell>Пользователь</TableHeaderCell>
                    <TableHeaderCell>Сумма</TableHeaderCell>
                    <TableHeaderCell>Дата заявки</TableHeaderCell>
                    <TableHeaderCell>Обработано</TableHeaderCell>
                    <TableHeaderCell>Статус</TableHeaderCell>
                  </tr>
                </TableHead>
                <tbody>
                  {processed.map((withdrawal) => (
                    <TableRow key={withdrawal.id}>
                      <TableCell className="font-semibold">{withdrawal.user.name}</TableCell>
                      <TableCell className="font-semibold">{money.format(withdrawal.amount)} ₽</TableCell>
                      <TableCell>{new Date(withdrawal.created_at).toLocaleDateString('ru-RU')}</TableCell>
                      <TableCell>{withdrawal.processed_at ? new Date(withdrawal.processed_at).toLocaleDateString('ru-RU') : '—'}</TableCell>
                      <TableCell>{statusBadge(withdrawal.status)}</TableCell>
                    </TableRow>
                  ))}
                </tbody>
              </AdminTable>
            </div>
          )}
        </Card>
      )}
    </>
  );
}

import { Check, Wallet, X } from 'lucide-react';
import { formatDate, formatMoney } from '../../../shared/lib';
import {
  AdminTable,
  Button,
  Card,
  EmptyState,
  TableCell,
  TableHead,
  TableHeaderCell,
  TableRow,
} from '../../../shared/ui';
import type { Withdrawal } from '../../../types';

type Props = {
  withdrawals: Withdrawal[];
  onSetStatus: (withdrawal: Withdrawal, status: 'approved' | 'rejected') => void;
};

export function PendingWithdrawalsTable({ withdrawals, onSetStatus }: Props) {
  return (
    <Card className="mt-6 p-6">
      <div className="mb-6">
        <h2 className="text-2xl font-bold">Заявки на рассмотрении</h2>
        <p className="text-sm text-[#789083]">Проверьте и обработайте заявки партнёров</p>
      </div>
      {withdrawals.length === 0 ? (
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
              {withdrawals.map((withdrawal) => (
                <TableRow key={withdrawal.id}>
                  <TableCell>
                    <p className="font-semibold">{withdrawal.user.name}</p>
                    <p className="text-sm text-[#789083]">{withdrawal.user.email}</p>
                  </TableCell>
                  <TableCell>{withdrawal.user.card_number || 'Не указана'}</TableCell>
                  <TableCell className="font-semibold">{formatMoney(withdrawal.amount)}</TableCell>
                  <TableCell>{formatMoney(withdrawal.user.bonus_balance)}</TableCell>
                  <TableCell>{formatDate(withdrawal.created_at)}</TableCell>
                  <TableCell>
                    <div className="flex gap-2">
                      <Button size="sm" onClick={() => onSetStatus(withdrawal, 'approved')}><Check className="h-4 w-4" />Одобрить</Button>
                      <Button size="icon" variant="danger" onClick={() => onSetStatus(withdrawal, 'rejected')}><X className="h-4 w-4" /></Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </tbody>
          </AdminTable>
        </div>
      )}
    </Card>
  );
}

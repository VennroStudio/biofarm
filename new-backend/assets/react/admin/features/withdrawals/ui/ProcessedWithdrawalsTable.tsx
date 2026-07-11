import { formatDate, formatMoney } from '../../../shared/lib';
import {
  AdminTable,
  Card,
  EmptyState,
  TableCell,
  TableHead,
  TableHeaderCell,
  TableRow,
} from '../../../shared/ui';
import type { Withdrawal } from '../../../types';
import { WithdrawalStatusBadge } from './WithdrawalStatusBadge';

type Props = {
  withdrawals: Withdrawal[];
};

export function ProcessedWithdrawalsTable({ withdrawals }: Props) {
  return (
    <Card className="mt-6 p-6">
      <h2 className="mb-6 text-2xl font-bold">История заявок</h2>
      {withdrawals.length === 0 ? (
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
              {withdrawals.map((withdrawal) => (
                <TableRow key={withdrawal.id}>
                  <TableCell className="font-semibold">{withdrawal.user.name}</TableCell>
                  <TableCell className="font-semibold">{formatMoney(withdrawal.amount)}</TableCell>
                  <TableCell>{formatDate(withdrawal.created_at)}</TableCell>
                  <TableCell>{withdrawal.processed_at ? formatDate(withdrawal.processed_at) : '—'}</TableCell>
                  <TableCell><WithdrawalStatusBadge status={withdrawal.status} /></TableCell>
                </TableRow>
              ))}
            </tbody>
          </AdminTable>
        </div>
      )}
    </Card>
  );
}

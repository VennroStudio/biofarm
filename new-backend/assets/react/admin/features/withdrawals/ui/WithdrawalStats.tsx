import { CheckCircle, Clock, Wallet } from 'lucide-react';
import { formatMoney } from '../../../shared/lib';
import { Card } from '../../../shared/ui';
import type { Withdrawal } from '../../../types';

type Props = {
  pending: Withdrawal[];
  processed: Withdrawal[];
};

export function WithdrawalStats({ pending, processed }: Props) {
  const pendingAmount = pending.reduce((sum, item) => sum + item.amount, 0);
  const approvedAmount = processed
    .filter((item) => item.status === 'approved')
    .reduce((sum, item) => sum + item.amount, 0);

  return (
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
            <p className="text-2xl font-bold">{formatMoney(pendingAmount)}</p>
          </div>
          <span className="grid h-12 w-12 place-items-center rounded-full bg-[#dbeafe] text-[#2563eb]"><Wallet className="h-6 w-6" /></span>
        </div>
      </Card>
      <Card className="p-6">
        <div className="flex items-center justify-between gap-4">
          <div>
            <p className="text-sm text-[#789083]">Выплачено всего</p>
            <p className="text-2xl font-bold">{formatMoney(approvedAmount)}</p>
          </div>
          <span className="grid h-12 w-12 place-items-center rounded-full bg-[#dcfce7] text-[#16a34a]"><CheckCircle className="h-6 w-6" /></span>
        </div>
      </Card>
    </div>
  );
}

import { CheckCircle } from 'lucide-react';
import { Badge } from '../../../shared/ui';
import type { Withdrawal } from '../../../types';

type Props = {
  status: Withdrawal['status'];
};

export function WithdrawalStatusBadge({ status }: Props) {
  if (status === 'approved') {
    return <Badge tone="green"><CheckCircle className="mr-1 h-3 w-3" />Одобрено</Badge>;
  }
  if (status === 'rejected') {
    return <Badge tone="red">Отклонено</Badge>;
  }
  return <Badge tone="gray">Ожидает</Badge>;
}

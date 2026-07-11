import { Gift, UserCheck, Users } from 'lucide-react';
import { formatMoney } from '../../../shared/lib';
import { Card } from '../../../shared/ui';
import type { AdminCustomer } from '../../../types';

type Props = {
  users: AdminCustomer[];
};

export function UserStats({ users }: Props) {
  return (
    <div className="grid gap-4 md:grid-cols-3">
      <Card className="p-6">
        <div className="flex items-center gap-4">
          <span className="grid h-12 w-12 place-items-center rounded-full bg-[#dbeafe] text-[#2563eb]"><Users className="h-6 w-6" /></span>
          <div>
            <p className="text-2xl font-bold">{users.length}</p>
            <p className="text-sm text-[#789083]">Всего пользователей</p>
          </div>
        </div>
      </Card>
      <Card className="p-6">
        <div className="flex items-center gap-4">
          <span className="grid h-12 w-12 place-items-center rounded-full bg-[#dcfce7] text-[#16a34a]"><Gift className="h-6 w-6" /></span>
          <div>
            <p className="text-2xl font-bold">{formatMoney(users.reduce((sum, user) => sum + user.bonus_balance, 0))}</p>
            <p className="text-sm text-[#789083]">Всего бонусов</p>
          </div>
        </div>
      </Card>
      <Card className="p-6">
        <div className="flex items-center gap-4">
          <span className="grid h-12 w-12 place-items-center rounded-full bg-[#f3d9ff] text-[#a855f7]"><UserCheck className="h-6 w-6" /></span>
          <div>
            <p className="text-2xl font-bold">{users.filter((user) => user.is_partner).length}</p>
            <p className="text-sm text-[#789083]">Партнёров</p>
          </div>
        </div>
      </Card>
    </div>
  );
}

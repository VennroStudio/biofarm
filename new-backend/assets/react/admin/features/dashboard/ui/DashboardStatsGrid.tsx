import { DollarSign, ShoppingCart, Users, Wallet } from 'lucide-react';
import { formatMoney } from '../../../shared/lib';
import { Card } from '../../../shared/ui';
import type { DashboardStats } from '../../../types';

type Props = {
  stats: DashboardStats | null;
};

export function DashboardStatsGrid({ stats }: Props) {
  const cards = [
    {
      title: 'Выручка',
      value: formatMoney(stats?.total_revenue ?? 0),
      caption: 'Всего оплачено',
      icon: DollarSign,
      color: 'bg-[#c9ffd8] text-[#16a34a]',
    },
    {
      title: 'Заказов всего',
      value: stats?.total_orders ?? 0,
      caption: 'Всего создано',
      icon: ShoppingCart,
      color: 'bg-[#dbeafe] text-[#2563eb]',
    },
    {
      title: 'Пользователей',
      value: stats?.total_users ?? 0,
      caption: 'Зарегистрировано',
      icon: Users,
      color: 'bg-[#f3d9ff] text-[#a855f7]',
    },
    {
      title: 'Заявок на вывод',
      value: stats?.pending_withdrawals ?? 0,
      caption: stats?.total_withdrawal_amount ? formatMoney(stats.total_withdrawal_amount) : 'Нет заявок',
      icon: Wallet,
      color: 'bg-[#fff2bf] text-[#f59e0b]',
    },
  ];

  return (
    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      {cards.map((card) => {
        const Icon = card.icon;
        return (
          <Card key={card.title} className="p-6">
            <div className="flex items-center justify-between gap-4">
              <div>
                <p className="text-sm text-[#789083]">{card.title}</p>
                <p className="mt-1 text-2xl font-bold text-[#26382d]">{card.value}</p>
                <p className="mt-1 text-xs text-[#789083]">{card.caption}</p>
              </div>
              <span className={`grid h-12 w-12 place-items-center rounded-full ${card.color}`}>
                <Icon className="h-6 w-6" />
              </span>
            </div>
          </Card>
        );
      })}
    </div>
  );
}

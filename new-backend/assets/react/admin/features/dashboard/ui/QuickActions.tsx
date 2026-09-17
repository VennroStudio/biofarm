import { FileText, Package, ShoppingCart, Wallet } from 'lucide-react';
import { Link } from 'react-router-dom';
import { Card } from '../../../shared/ui';

const quickLinks = [
  { href: '/admin/shop/products', label: 'Добавить товар', icon: Package },
  { href: '/admin/orders', label: 'Просмотреть заказы', icon: ShoppingCart },
  { href: '/admin/blog', label: 'Написать статью', icon: FileText },
  { href: '/admin/program/withdrawals', label: 'Выплаты партнёрам', icon: Wallet },
];

export function QuickActions() {
  return (
    <Card className="mt-8 p-6">
      <h2 className="mb-5 text-2xl font-bold text-[#294555]">Быстрые действия</h2>
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {quickLinks.map((link) => {
          const Icon = link.icon;
          return (
            <Link
              key={link.href}
              to={link.href}
              className="flex min-h-20 flex-col items-center justify-center gap-2 rounded-md border border-[#dfece9] bg-[#f5faf8] px-4 py-4 text-sm font-semibold text-[#294555] transition hover:bg-white"
            >
              <Icon className="h-5 w-5" />
              {link.label}
            </Link>
          );
        })}
      </div>
    </Card>
  );
}

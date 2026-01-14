import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { motion } from 'framer-motion';
import { 
  ShoppingCart, Users, TrendingUp, 
  Package, FileText, ArrowRight, DollarSign, Wallet
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { adminApi, DashboardStats } from '@/data/admin';

const AdminDashboard = () => {
  const [stats, setStats] = useState<DashboardStats | null>(null);

  useEffect(() => {
    adminApi.getDashboardStats().then(setStats);
  }, []);

  const statCards = [
    { 
      title: 'Выручка', 
      value: stats ? `${stats.totalRevenue.toLocaleString()} ₽` : '—', 
      icon: DollarSign, 
      color: 'bg-green-100 text-green-600',
      change: 'Всего оплачено'
    },
    { 
      title: 'Заказов всего', 
      value: stats?.totalOrders ?? '—', 
      icon: ShoppingCart, 
      color: 'bg-blue-100 text-blue-600',
      change: 'Всего создано'
    },
    { 
      title: 'Пользователей', 
      value: stats?.totalUsers ?? '—', 
      icon: Users, 
      color: 'bg-purple-100 text-purple-600',
      change: 'Зарегистрировано'
    },
    { 
      title: 'Заявок на вывод', 
      value: stats?.pendingWithdrawals ?? '—', 
      icon: Wallet, 
      color: 'bg-amber-100 text-amber-600',
      change: stats?.totalWithdrawalAmount ? `${stats.totalWithdrawalAmount.toLocaleString()} ₽` : 'Нет заявок'
    },
  ];

  const quickLinks = [
    { href: '/admin/products', label: 'Добавить товар', icon: Package },
    { href: '/admin/orders', label: 'Просмотреть заказы', icon: ShoppingCart },
    { href: '/admin/blog', label: 'Написать статью', icon: FileText },
    { href: '/admin/withdrawals', label: 'Заявки на вывод', icon: Wallet },
  ];

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-3xl font-bold">Дашборд</h1>
        <p className="text-muted-foreground">Обзор магазина BioFarm</p>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {statCards.map((card, index) => {
          const Icon = card.icon;
          return (
            <motion.div
              key={card.title}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: index * 0.1 }}
            >
              <Card>
                <CardContent className="p-6">
                  <div className="flex items-center justify-between">
                    <div>
                      <p className="text-sm text-muted-foreground">{card.title}</p>
                      <p className="text-2xl font-bold mt-1">{card.value}</p>
                      <p className="text-xs text-muted-foreground mt-1">{card.change}</p>
                    </div>
                    <div className={`p-3 rounded-full ${card.color}`}>
                      <Icon className="h-6 w-6" />
                    </div>
                  </div>
                </CardContent>
              </Card>
            </motion.div>
          );
        })}
      </div>

      {/* Quick Actions */}
      <Card>
        <CardHeader>
          <CardTitle>Быстрые действия</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {quickLinks.map((link) => {
              const Icon = link.icon;
              return (
                <Button 
                  key={link.href} 
                  variant="outline" 
                  className="h-auto py-4 flex-col gap-2"
                  asChild
                >
                  <Link to={link.href}>
                    <Icon className="h-6 w-6" />
                    <span>{link.label}</span>
                  </Link>
                </Button>
              );
            })}
          </div>
        </CardContent>
      </Card>

      {/* Recent Activity Placeholder */}
      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle>Последние заказы</CardTitle>
          <Button variant="ghost" size="sm" asChild>
            <Link to="/admin/orders">
              Все заказы <ArrowRight className="ml-2 h-4 w-4" />
            </Link>
          </Button>
        </CardHeader>
        <CardContent>
          <div className="text-center py-8 text-muted-foreground">
            <TrendingUp className="h-12 w-12 mx-auto mb-4 opacity-50" />
            <p>Заказы будут отображаться здесь</p>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default AdminDashboard;

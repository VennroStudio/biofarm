import { useState, useEffect } from 'react';
import { Check, X, Wallet, Clock, CheckCircle, XCircle, CreditCard } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { withdrawalsApi, WithdrawalRequest } from '@/data/withdrawals';
import { authApi, User } from '@/data/users';
import { adminApi } from '@/data/admin';
import { useToast } from '@/hooks/use-toast';

interface WithdrawalWithUser extends WithdrawalRequest {
  user?: User;
}

const AdminWithdrawals = () => {
  const { toast } = useToast();
  const [withdrawals, setWithdrawals] = useState<WithdrawalWithUser[]>([]);
  const [tab, setTab] = useState('pending');

  useEffect(() => {
    const loadData = async () => {
      const allWithdrawals = await withdrawalsApi.getAll();
      const allUsers = await authApi.getAllUsers();
      
      const withdrawalsWithUsers = allWithdrawals.map(w => ({
        ...w,
        user: allUsers.find(u => u.id === w.userId),
      }));
      
      setWithdrawals(withdrawalsWithUsers);
    };
    loadData();
  }, []);

  const pendingWithdrawals = withdrawals.filter(w => w.status === 'pending');
  const processedWithdrawals = withdrawals.filter(w => w.status !== 'pending');

  const handleApprove = async (id: string) => {
    const admin = adminApi.getCurrentAdmin();
    const result = await withdrawalsApi.approve(id, admin?.name || 'Admin');
    if (result) {
      setWithdrawals(prev => prev.map(w => w.id === id ? { ...w, ...result } : w));
      toast({ title: 'Заявка одобрена', description: 'Средства списаны с баланса пользователя' });
    }
  };

  const handleReject = async (id: string) => {
    const admin = adminApi.getCurrentAdmin();
    const result = await withdrawalsApi.reject(id, admin?.name || 'Admin');
    if (result) {
      setWithdrawals(prev => prev.map(w => w.id === id ? { ...w, ...result } : w));
      toast({ title: 'Заявка отклонена' });
    }
  };

  const getStatusBadge = (status: WithdrawalRequest['status']) => {
    const statusMap = {
      pending: { label: 'Ожидает', variant: 'secondary' as const, icon: Clock },
      approved: { label: 'Одобрено', variant: 'default' as const, icon: CheckCircle },
      rejected: { label: 'Отклонено', variant: 'destructive' as const, icon: XCircle },
    };
    const s = statusMap[status];
    return (
      <Badge variant={s.variant} className="gap-1">
        <s.icon className="h-3 w-3" />
        {s.label}
      </Badge>
    );
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Заявки на вывод</h1>
        <p className="text-muted-foreground">Управление заявками на вывод средств партнёров</p>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Ожидают</p>
                <p className="text-2xl font-bold">{pendingWithdrawals.length}</p>
              </div>
              <div className="p-3 rounded-full bg-amber-100 text-amber-600">
                <Clock className="h-6 w-6" />
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Сумма ожидания</p>
                <p className="text-2xl font-bold">
                  {pendingWithdrawals.reduce((sum, w) => sum + w.amount, 0).toLocaleString()} ₽
                </p>
              </div>
              <div className="p-3 rounded-full bg-blue-100 text-blue-600">
                <Wallet className="h-6 w-6" />
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-muted-foreground">Выплачено всего</p>
                <p className="text-2xl font-bold">
                  {processedWithdrawals
                    .filter(w => w.status === 'approved')
                    .reduce((sum, w) => sum + w.amount, 0)
                    .toLocaleString()} ₽
                </p>
              </div>
              <div className="p-3 rounded-full bg-green-100 text-green-600">
                <CheckCircle className="h-6 w-6" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList>
          <TabsTrigger value="pending" className="gap-2">
            Ожидают
            {pendingWithdrawals.length > 0 && (
              <Badge variant="destructive" className="ml-1">{pendingWithdrawals.length}</Badge>
            )}
          </TabsTrigger>
          <TabsTrigger value="processed" className="gap-2">
            Обработанные
            <Badge variant="secondary" className="ml-1">{processedWithdrawals.length}</Badge>
          </TabsTrigger>
        </TabsList>

        <TabsContent value="pending" className="mt-6">
          <Card>
            <CardHeader>
              <CardTitle>Заявки на рассмотрении</CardTitle>
              <CardDescription>Проверьте и обработайте заявки партнёров</CardDescription>
            </CardHeader>
            <CardContent>
              {pendingWithdrawals.length === 0 ? (
                <div className="text-center py-12">
                  <Wallet className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                  <p className="text-muted-foreground">Нет заявок на вывод</p>
                </div>
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Пользователь</TableHead>
                      <TableHead>Номер карты</TableHead>
                      <TableHead>Сумма</TableHead>
                      <TableHead>Баланс</TableHead>
                      <TableHead>Дата</TableHead>
                      <TableHead>Статус</TableHead>
                      <TableHead>Действия</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {pendingWithdrawals.map((withdrawal) => (
                      <TableRow key={withdrawal.id}>
                        <TableCell>
                          <div>
                            <p className="font-medium">{withdrawal.user?.name || 'Неизвестно'}</p>
                            <p className="text-sm text-muted-foreground">{withdrawal.user?.email}</p>
                          </div>
                        </TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2 text-sm">
                            <CreditCard className="h-4 w-4 text-muted-foreground" />
                            {withdrawal.user?.cardNumber || 'Не указана'}
                          </div>
                        </TableCell>
                        <TableCell className="font-medium">{withdrawal.amount.toLocaleString()} ₽</TableCell>
                        <TableCell className="text-muted-foreground">
                          {withdrawal.user?.bonusBalance?.toLocaleString() || 0} ₽
                        </TableCell>
                        <TableCell>{new Date(withdrawal.createdAt).toLocaleDateString('ru-RU')}</TableCell>
                        <TableCell>{getStatusBadge(withdrawal.status)}</TableCell>
                        <TableCell>
                          <div className="flex gap-2">
                            <Button size="sm" onClick={() => handleApprove(withdrawal.id)}>
                              <Check className="h-4 w-4 mr-1" />
                              Одобрить
                            </Button>
                            <Button size="sm" variant="destructive" onClick={() => handleReject(withdrawal.id)}>
                              <X className="h-4 w-4" />
                            </Button>
                          </div>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="processed" className="mt-6">
          <Card>
            <CardHeader>
              <CardTitle>История заявок</CardTitle>
            </CardHeader>
            <CardContent>
              {processedWithdrawals.length === 0 ? (
                <div className="text-center py-12">
                  <Wallet className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                  <p className="text-muted-foreground">Нет обработанных заявок</p>
                </div>
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Пользователь</TableHead>
                      <TableHead>Сумма</TableHead>
                      <TableHead>Дата заявки</TableHead>
                      <TableHead>Обработано</TableHead>
                      <TableHead>Статус</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {processedWithdrawals.map((withdrawal) => (
                      <TableRow key={withdrawal.id}>
                        <TableCell className="font-medium">{withdrawal.user?.name || 'Неизвестно'}</TableCell>
                        <TableCell className="font-medium">{withdrawal.amount.toLocaleString()} ₽</TableCell>
                        <TableCell>{new Date(withdrawal.createdAt).toLocaleDateString('ru-RU')}</TableCell>
                        <TableCell>
                          {withdrawal.processedAt 
                            ? new Date(withdrawal.processedAt).toLocaleDateString('ru-RU') 
                            : '—'}
                        </TableCell>
                        <TableCell>{getStatusBadge(withdrawal.status)}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
};

export default AdminWithdrawals;

import { useState, useEffect } from 'react';
import { Search, Users, Mail, Phone, Calendar, Gift, UserCheck } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { authApi, User } from '@/data/users';
import { useToast } from '@/hooks/use-toast';

const AdminUsers = () => {
  const { toast } = useToast();
  const [search, setSearch] = useState('');
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    authApi.getAllUsers()
      .then(setUsers)
      .catch((error) => {
        console.error('Failed to load users:', error);
        toast({ title: 'Ошибка загрузки пользователей', variant: 'destructive' });
      })
      .finally(() => setLoading(false));
  }, [toast]);

  const filteredUsers = users.filter(user => 
    user.name.toLowerCase().includes(search.toLowerCase()) ||
    user.email.toLowerCase().includes(search.toLowerCase())
  );

  if (loading) {
    return (
      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold">Пользователи</h1>
          <p className="text-muted-foreground">Список зарегистрированных пользователей</p>
        </div>
        <div className="text-center py-12">
          <p className="text-muted-foreground">Загрузка пользователей...</p>
        </div>
      </div>
    );
  }

  const handleTogglePartner = async (userId: string, isPartner: boolean) => {
    const result = await authApi.setPartnerStatus(userId, !isPartner);
    if (result) {
      setUsers(prev => prev.map(u => u.id === userId ? { ...u, isPartner: !isPartner } : u));
      toast({ title: isPartner ? 'Статус партнёра снят' : 'Пользователь стал партнёром' });
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Пользователи</h1>
        <p className="text-muted-foreground">Список зарегистрированных пользователей</p>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <Card>
          <CardContent className="p-6 flex items-center gap-4">
            <div className="p-3 rounded-full bg-blue-100 text-blue-600"><Users className="h-6 w-6" /></div>
            <div>
              <p className="text-2xl font-bold">{users.length}</p>
              <p className="text-sm text-muted-foreground">Всего пользователей</p>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-6 flex items-center gap-4">
            <div className="p-3 rounded-full bg-green-100 text-green-600"><Gift className="h-6 w-6" /></div>
            <div>
              <p className="text-2xl font-bold">{users.reduce((sum, u) => sum + u.bonusBalance, 0)} ₽</p>
              <p className="text-sm text-muted-foreground">Всего бонусов</p>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-6 flex items-center gap-4">
            <div className="p-3 rounded-full bg-purple-100 text-purple-600"><UserCheck className="h-6 w-6" /></div>
            <div>
              <p className="text-2xl font-bold">{users.filter(u => u.isPartner).length}</p>
              <p className="text-sm text-muted-foreground">Партнёров</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <div className="flex items-center gap-4">
            <div className="relative flex-1 max-w-sm">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input placeholder="Поиск пользователей..." value={search} onChange={(e) => setSearch(e.target.value)} className="pl-10" />
            </div>
            <Badge variant="secondary">{filteredUsers.length} пользователей</Badge>
          </div>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Пользователь</TableHead>
                  <TableHead>Контакты</TableHead>
                  <TableHead>Статус</TableHead>
                  <TableHead>Бонусы</TableHead>
                  <TableHead>Регистрация</TableHead>
                  <TableHead>Действия</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredUsers.map((user) => (
                  <TableRow key={user.id}>
                    <TableCell>
                      <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                          <span className="font-medium text-primary">{user.name.charAt(0).toUpperCase()}</span>
                        </div>
                        <div>
                          <p className="font-medium">{user.name}</p>
                          {user.referredBy && <Badge variant="outline" className="text-xs">Реферал</Badge>}
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className="space-y-1">
                        <div className="flex items-center gap-2 text-sm"><Mail className="h-3 w-3 text-muted-foreground" />{user.email}</div>
                        {user.phone && <div className="flex items-center gap-2 text-sm"><Phone className="h-3 w-3 text-muted-foreground" />{user.phone}</div>}
                      </div>
                    </TableCell>
                    <TableCell>
                      {user.isPartner ? <Badge variant="default">Партнёр</Badge> : <Badge variant="secondary">Пользователь</Badge>}
                    </TableCell>
                    <TableCell><span className="font-medium text-primary">{user.bonusBalance} ₽</span></TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <Calendar className="h-3 w-3" />
                        {new Date(user.createdAt).toLocaleDateString('ru-RU')}
                      </div>
                    </TableCell>
                    <TableCell>
                      <Button size="sm" variant={user.isPartner ? "outline" : "default"} onClick={() => handleTogglePartner(user.id, user.isPartner)}>
                        <UserCheck className="h-4 w-4 mr-1" />
                        {user.isPartner ? 'Снять партнёра' : 'Сделать партнёром'}
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default AdminUsers;

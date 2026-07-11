import { Calendar, Gift, Mail, Phone, Search, UserCheck, Users } from 'lucide-react';
import { useMemo, useState } from 'react';
import { usersApi } from '../api/resources';
import {
  AdminTable,
  Badge,
  Button,
  Card,
  EmptyState,
  inputClass,
  PageHeader,
  TableCell,
  TableHead,
  TableHeaderCell,
  TableRow,
} from '../components/ui';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import type { AdminCustomer } from '../types';

const money = new Intl.NumberFormat('ru-RU');

export function AdminUsers() {
  const [users, setUsers] = useState<AdminCustomer[]>([]);
  const [search, setSearch] = useState('');

  async function load() {
    const result = await usersApi.list();
    setUsers(result.items);
  }

  useLoadOnMount(load);

  const filteredUsers = useMemo(() => {
    const needle = search.toLowerCase();
    return users.filter((user) => (
      user.name.toLowerCase().includes(needle) ||
      user.email.toLowerCase().includes(needle) ||
      String(user.phone ?? '').toLowerCase().includes(needle)
    ));
  }, [users, search]);

  async function togglePartner(user: AdminCustomer) {
    await usersApi.update(user.id, {
      name: user.name,
      phone: user.phone,
      cardNumber: user.card_number,
      bonusBalance: user.bonus_balance,
      isPartner: !user.is_partner,
    });
    await load();
  }

  return (
    <>
      <PageHeader title="Пользователи" subtitle="Список зарегистрированных пользователей" />

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
              <p className="text-2xl font-bold">{money.format(users.reduce((sum, user) => sum + user.bonus_balance, 0))} ₽</p>
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

      <Card className="mt-6 p-6">
        <div className="mb-8 flex flex-wrap items-center gap-4">
          <div className="relative w-full max-w-sm">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#789083]" />
            <input
              className={`${inputClass} pl-10`}
              placeholder="Поиск пользователей..."
              value={search}
              onChange={(event) => setSearch(event.target.value)}
            />
          </div>
          <Badge tone="gray">{filteredUsers.length} пользователей</Badge>
        </div>

        {filteredUsers.length === 0 ? (
          <EmptyState>Пользователи не найдены</EmptyState>
        ) : (
          <div className="overflow-x-auto">
            <AdminTable>
              <TableHead>
                <tr>
                  <TableHeaderCell>Пользователь</TableHeaderCell>
                  <TableHeaderCell>Контакты</TableHeaderCell>
                  <TableHeaderCell>Статус</TableHeaderCell>
                  <TableHeaderCell>Бонусы</TableHeaderCell>
                  <TableHeaderCell>Регистрация</TableHeaderCell>
                  <TableHeaderCell>Действия</TableHeaderCell>
                </tr>
              </TableHead>
              <tbody>
                {filteredUsers.map((user) => (
                  <TableRow key={user.id}>
                    <TableCell>
                      <div className="flex items-center gap-3">
                        <span className="grid h-10 w-10 place-items-center rounded-full bg-[#eef1e8] font-semibold text-[#2f7d4b]">
                          {user.name.charAt(0).toUpperCase()}
                        </span>
                        <div>
                          <p className="font-semibold">{user.name}</p>
                          {user.referred_by_user_id && <Badge tone="gray" className="mt-1">Реферал</Badge>}
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className="space-y-1 text-sm">
                        <p className="flex items-center gap-2"><Mail className="h-3 w-3 text-[#789083]" />{user.email}</p>
                        {user.phone && <p className="flex items-center gap-2"><Phone className="h-3 w-3 text-[#789083]" />{user.phone}</p>}
                      </div>
                    </TableCell>
                    <TableCell><Badge tone={user.is_partner ? 'green' : 'gray'}>{user.is_partner ? 'Партнёр' : 'Пользователь'}</Badge></TableCell>
                    <TableCell className="font-semibold text-[#2f7d4b]">{money.format(user.bonus_balance)} ₽</TableCell>
                    <TableCell>
                      <span className="flex items-center gap-2 text-sm text-[#789083]">
                        <Calendar className="h-3 w-3" />
                        {new Date(user.created_at).toLocaleDateString('ru-RU')}
                      </span>
                    </TableCell>
                    <TableCell>
                      <Button variant={user.is_partner ? 'outline' : 'primary'} size="sm" onClick={() => void togglePartner(user)}>
                        <UserCheck className="h-4 w-4" />
                        {user.is_partner ? 'Снять партнёра' : 'Сделать партнёром'}
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </tbody>
            </AdminTable>
          </div>
        )}
      </Card>
    </>
  );
}

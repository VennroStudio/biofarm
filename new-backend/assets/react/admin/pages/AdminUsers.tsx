import { useMemo, useState } from 'react';
import { usersApi } from '../api/resources';
import { UserStats } from '../features/users/ui/UserStats';
import { UsersTable } from '../features/users/ui/UsersTable';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { Badge, Card, PageHeader, SearchField } from '../shared/ui';
import type { AdminCustomer } from '../types';

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

      <UserStats users={users} />

      <Card className="mt-6 p-6">
        <div className="mb-8 flex flex-wrap items-center gap-4">
          <SearchField placeholder="Поиск пользователей..." value={search} onChange={setSearch} />
          <Badge tone="gray">{filteredUsers.length} пользователей</Badge>
        </div>

        <UsersTable users={filteredUsers} onTogglePartner={(user) => void togglePartner(user)} />
      </Card>
    </>
  );
}

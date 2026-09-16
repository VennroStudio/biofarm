import { useMemo, useState } from 'react';
import { usersApi } from '../api/resources';
import { UserDetailsModal } from '../features/users/ui/UserDetailsModal';
import { UserStats } from '../features/users/ui/UserStats';
import { UsersTable } from '../features/users/ui/UsersTable';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { messageFromError } from '../shared/lib';
import { Badge, Card, ErrorAlert, PageHeader, SearchField } from '../shared/ui';
import type { AdminCustomer } from '../types';

export function AdminUsers() {
  const [users, setUsers] = useState<AdminCustomer[]>([]);
  const [search, setSearch] = useState('');
  const [selectedUser, setSelectedUser] = useState<AdminCustomer | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [detailsError, setDetailsError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

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

  async function togglePartner() {
    setError(null);
    try {
      window.location.href = '/admin/program';
      await load();
    } catch (toggleError) {
      setError(messageFromError(toggleError, 'Не удалось изменить статус пользователя'));
    }
  }

  async function saveUser(user: AdminCustomer, payload: Record<string, unknown>) {
    setDetailsError(null);
    setSaving(true);
    try {
      await usersApi.update(user.id, payload);
      setSelectedUser(null);
      await load();
    } catch (saveError) {
      setDetailsError(messageFromError(saveError, 'Не удалось сохранить пользователя'));
    } finally {
      setSaving(false);
    }
  }

  function openUser(user: AdminCustomer) {
    setDetailsError(null);
    setSelectedUser(user);
  }

  return (
    <>
      <PageHeader title="Пользователи" subtitle="Список зарегистрированных пользователей" />

      <UserStats users={users} />

      <Card className="mt-6 p-6">
        <ErrorAlert className="mb-5">{error}</ErrorAlert>

        <div className="mb-8 flex flex-wrap items-center gap-4">
          <SearchField placeholder="Поиск пользователей..." value={search} onChange={setSearch} />
          <Badge tone="gray">{filteredUsers.length} пользователей</Badge>
        </div>

        <UsersTable
          users={filteredUsers}
          onEdit={openUser}
          onTogglePartner={() => void togglePartner()}
        />
      </Card>

      <UserDetailsModal
        key={selectedUser?.id ?? 'empty'}
        user={selectedUser}
        error={detailsError}
        saving={saving}
        onClose={() => {
          setSelectedUser(null);
          setDetailsError(null);
        }}
        onSave={(user, payload) => saveUser(user, payload)}
      />
    </>
  );
}

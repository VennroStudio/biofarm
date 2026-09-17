import { Calendar, Edit, Mail, Phone } from 'lucide-react';
import { formatDate } from '../../../shared/lib';
import {
  AdminTable,
  Badge,
  Button,
  EmptyState,
  TableCell,
  TableHead,
  TableHeaderCell,
  TableRow,
} from '../../../shared/ui';
import type { AdminCustomer } from '../../../types';

type Props = {
  users: AdminCustomer[];
  onEdit: (user: AdminCustomer) => void;
};

export function UsersTable({ users, onEdit }: Props) {
  if (users.length === 0) {
    return <EmptyState>Пользователи не найдены</EmptyState>;
  }

  return (
    <div className="overflow-x-auto">
      <AdminTable>
        <TableHead>
          <tr>
            <TableHeaderCell>Пользователь</TableHeaderCell>
            <TableHeaderCell>Контакты</TableHeaderCell>
            <TableHeaderCell>Статус</TableHeaderCell>
            <TableHeaderCell>Регистрация</TableHeaderCell>
            <TableHeaderCell>Действия</TableHeaderCell>
          </tr>
        </TableHead>
        <tbody>
          {users.map((user) => (
            <TableRow key={user.id}>
              <TableCell>
                <div className="flex items-center gap-3">
                  <span className="grid h-10 w-10 place-items-center rounded-full bg-[#eaf5f1] font-semibold text-[#2e8175]">
                    {user.name.charAt(0).toUpperCase()}
                  </span>
                  <div>
                    <p className="font-semibold">{user.name}</p>
                    {user.is_team_member && <p className="text-xs">Команда: {user.team_partner_name}</p>}
                    {user.is_referral && <p className="text-xs">Пригласивший: {user.parent_name || user.referred_by_user_id}</p>}
                  </div>
                </div>
              </TableCell>
              <TableCell>
                <div className="space-y-1 text-sm">
                  <p className="flex items-center gap-2"><Mail className="h-3 w-3 text-[#5f7580]" />{user.email}</p>
                  {user.phone && <p className="flex items-center gap-2"><Phone className="h-3 w-3 text-[#5f7580]" />{user.phone}</p>}
                </div>
              </TableCell>
              <TableCell><Badge tone={user.is_partner ? 'green' : 'gray'}>{user.is_partner ? 'Партнёр' : user.is_team_member ? 'Участник команды' : user.is_referral ? 'Реферал' : 'Пользователь'}</Badge></TableCell>
              <TableCell>
                <span className="flex items-center gap-2 text-sm text-[#5f7580]">
                  <Calendar className="h-3 w-3" />
                  {formatDate(user.created_at)}
                </span>
              </TableCell>
              <TableCell>
                <div className="flex flex-wrap gap-2">
                  <Button variant="outline" size="sm" onClick={() => onEdit(user)}>
                    <Edit className="h-4 w-4" />
                    Изменить
                  </Button>
                </div>
              </TableCell>
            </TableRow>
          ))}
        </tbody>
      </AdminTable>
    </div>
  );
}

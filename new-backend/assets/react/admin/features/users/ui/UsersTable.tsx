import { Calendar, Mail, Phone, UserCheck } from 'lucide-react';
import { formatDate, formatMoney } from '../../../shared/lib';
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
  onTogglePartner: (user: AdminCustomer) => void;
};

export function UsersTable({ users, onTogglePartner }: Props) {
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
            <TableHeaderCell>Бонусы</TableHeaderCell>
            <TableHeaderCell>Регистрация</TableHeaderCell>
            <TableHeaderCell>Действия</TableHeaderCell>
          </tr>
        </TableHead>
        <tbody>
          {users.map((user) => (
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
              <TableCell className="font-semibold text-[#2f7d4b]">{formatMoney(user.bonus_balance)}</TableCell>
              <TableCell>
                <span className="flex items-center gap-2 text-sm text-[#789083]">
                  <Calendar className="h-3 w-3" />
                  {formatDate(user.created_at)}
                </span>
              </TableCell>
              <TableCell>
                <Button variant={user.is_partner ? 'outline' : 'primary'} size="sm" onClick={() => onTogglePartner(user)}>
                  <UserCheck className="h-4 w-4" />
                  {user.is_partner ? 'Снять партнёра' : 'Сделать партнёром'}
                </Button>
              </TableCell>
            </TableRow>
          ))}
        </tbody>
      </AdminTable>
    </div>
  );
}

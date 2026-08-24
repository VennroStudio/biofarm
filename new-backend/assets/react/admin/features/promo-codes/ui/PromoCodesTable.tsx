import { Edit, Trash2 } from 'lucide-react';
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
import type { PromoCode } from '../../../types';

type Props = {
  promoCodes: PromoCode[];
  onEdit: (promoCode: PromoCode) => void;
  onRemove: (promoCode: PromoCode) => void;
};

export function PromoCodesTable({ promoCodes, onEdit, onRemove }: Props) {
  if (promoCodes.length === 0) {
    return <EmptyState>Промокоды пока не добавлены</EmptyState>;
  }

  return (
    <div className="overflow-x-auto">
      <AdminTable>
        <TableHead>
          <tr>
            <TableHeaderCell>Код</TableHeaderCell>
            <TableHeaderCell>Скидка</TableHeaderCell>
            <TableHeaderCell>Минимум</TableHeaderCell>
            <TableHeaderCell>Период</TableHeaderCell>
            <TableHeaderCell>Использовано</TableHeaderCell>
            <TableHeaderCell>Статус</TableHeaderCell>
            <TableHeaderCell className="text-right">Действия</TableHeaderCell>
          </tr>
        </TableHead>
        <tbody>
          {promoCodes.map((promoCode) => (
            <TableRow key={promoCode.id}>
              <TableCell>
                <p className="font-bold text-[#1f3328]">{promoCode.code}</p>
              </TableCell>
              <TableCell>
                {promoCode.type === 'percent' ? `${promoCode.value}%` : formatMoney(promoCode.value)}
              </TableCell>
              <TableCell>{promoCode.min_order_total > 0 ? formatMoney(promoCode.min_order_total) : 'Без минимума'}</TableCell>
              <TableCell>
                <p>{promoCode.starts_at ? formatDate(promoCode.starts_at) : 'Сразу'}</p>
                <p className="text-[#789083]">{promoCode.ends_at ? `до ${formatDate(promoCode.ends_at)}` : 'Без окончания'}</p>
              </TableCell>
              <TableCell>
                {promoCode.used_count}{promoCode.usage_limit ? ` / ${promoCode.usage_limit}` : ''}
              </TableCell>
              <TableCell>
                <Badge tone={promoCode.is_active ? 'green' : 'gray'}>{promoCode.is_active ? 'Активен' : 'Выключен'}</Badge>
              </TableCell>
              <TableCell>
                <div className="flex justify-end gap-2">
                  <Button variant="ghost" size="icon" onClick={() => onEdit(promoCode)} title="Изменить">
                    <Edit className="h-4 w-4" />
                  </Button>
                  <Button variant="ghost" size="icon" className="text-[#ef4444]" onClick={() => onRemove(promoCode)} title="Удалить">
                    <Trash2 className="h-4 w-4" />
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

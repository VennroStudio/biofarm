import { Edit, Eye, EyeOff, Trash2 } from 'lucide-react';
import { formatMoney } from '../../../shared/lib';
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
import type { Product } from '../../../types';

type Props = {
  categoryById: Map<string, string>;
  products: Product[];
  onEdit: (product: Product) => void;
  onRemove: (product: Product) => void;
  onToggleActive: (product: Product) => void;
};

export function ProductTable({ categoryById, products, onEdit, onRemove, onToggleActive }: Props) {
  if (products.length === 0) {
    return <EmptyState>Товары не найдены</EmptyState>;
  }

  return (
    <div className="overflow-x-auto">
      <AdminTable>
        <TableHead>
          <tr>
            <TableHeaderCell className="w-20">Фото</TableHeaderCell>
            <TableHeaderCell>Название</TableHeaderCell>
            <TableHeaderCell>Категория</TableHeaderCell>
            <TableHeaderCell>Цена</TableHeaderCell>
            <TableHeaderCell>Маркетплейсы</TableHeaderCell>
            <TableHeaderCell className="text-right">Действия</TableHeaderCell>
          </tr>
        </TableHead>
        <tbody>
          {products.map((product) => (
            <TableRow key={product.id} className={!product.is_active ? 'opacity-60' : ''}>
              <TableCell>
                <img src={product.image} alt={product.name} className="h-12 w-12 rounded object-cover" />
              </TableCell>
              <TableCell>
                <p className="font-semibold text-[#26382d]">{product.name}</p>
                <p className="text-sm text-[#789083]">{product.weight}</p>
              </TableCell>
              <TableCell>
                <Badge tone="gray">{categoryById.get(product.category_id) || product.category_id}</Badge>
              </TableCell>
              <TableCell>
                <p className="font-semibold">{formatMoney(product.price)}</p>
                {product.old_price && <p className="text-sm text-[#789083] line-through">{formatMoney(product.old_price)}</p>}
              </TableCell>
              <TableCell>
                <div className="flex gap-1">
                  {product.wb_link && <Badge tone="gray">WB</Badge>}
                  {product.ozon_link && <Badge tone="gray">Ozon</Badge>}
                </div>
              </TableCell>
              <TableCell>
                <div className="flex justify-end gap-2">
                  <Button
                    variant="ghost"
                    size="icon"
                    title={product.is_active ? 'Скрыть товар' : 'Показать товар'}
                    onClick={() => onToggleActive(product)}
                  >
                    {product.is_active ? <Eye className="h-4 w-4 text-[#34a853]" /> : <EyeOff className="h-4 w-4" />}
                  </Button>
                  <Button variant="ghost" size="icon" title="Изменить" onClick={() => onEdit(product)}>
                    <Edit className="h-4 w-4" />
                  </Button>
                  <Button variant="ghost" size="icon" title="Удалить" className="text-[#ef4444]" onClick={() => onRemove(product)}>
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

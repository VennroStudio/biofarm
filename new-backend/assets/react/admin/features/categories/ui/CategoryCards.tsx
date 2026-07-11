import { Edit, Trash2 } from 'lucide-react';
import { Badge, Button, Card, EmptyState } from '../../../shared/ui';
import type { Category } from '../../../types';

type Props = {
  categories: Category[];
  productCounts: Map<string, number>;
  onEdit: (category: Category) => void;
  onRemove: (category: Category) => void;
};

export function CategoryCards({ categories, productCounts, onEdit, onRemove }: Props) {
  if (categories.length === 0) {
    return <EmptyState>Категории не найдены</EmptyState>;
  }

  return (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      {categories.map((category) => (
        <Card key={category.id} className="group p-6">
          <div className="flex items-start justify-between gap-4">
            <div>
              <h3 className="text-lg font-semibold text-[#26382d]">{category.name}</h3>
              <Badge tone="gray" className="mt-3">{productCounts.get(String(category.id)) ?? 0} товаров</Badge>
            </div>
            <div className="flex gap-1 opacity-0 transition group-hover:opacity-100">
              <Button variant="ghost" size="icon" onClick={() => onEdit(category)} title="Изменить">
                <Edit className="h-4 w-4" />
              </Button>
              <Button variant="ghost" size="icon" className="text-[#ef4444]" onClick={() => onRemove(category)} title="Удалить">
                <Trash2 className="h-4 w-4" />
              </Button>
            </div>
          </div>
        </Card>
      ))}
    </div>
  );
}

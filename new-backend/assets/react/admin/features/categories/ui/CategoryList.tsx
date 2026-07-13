import { Edit, Trash2 } from 'lucide-react';
import { Badge, Button, Card, EmptyState } from '../../../shared/ui';
import type { Category } from '../../../types';

type Props = {
  categories: Category[];
  productCounts: Map<string, number>;
  onEdit: (category: Category) => void;
  onRemove: (category: Category) => void;
};

export function CategoryList({ categories, productCounts, onEdit, onRemove }: Props) {
  if (categories.length === 0) {
    return <EmptyState>Категории не найдены</EmptyState>;
  }

  const sorted = [...categories].sort(compareCategories);
  const byParent = new Map<string, Category[]>();
  sorted.forEach((category) => {
    const key = category.parent_id ? String(category.parent_id) : 'root';
    byParent.set(key, [...(byParent.get(key) ?? []), category]);
  });

  const roots = [
    ...(byParent.get('root') ?? []),
    ...sorted.filter((category) => category.parent_id && !categories.some((parent) => parent.id === category.parent_id)),
  ];

  return (
    <Card className="overflow-hidden">
      <div className="divide-y divide-[#e4e5da]">
        {roots.map((category) => renderCategory(category, byParent, productCounts, onEdit, onRemove))}
      </div>
    </Card>
  );
}

function renderCategory(
  category: Category,
  byParent: Map<string, Category[]>,
  productCounts: Map<string, number>,
  onEdit: (category: Category) => void,
  onRemove: (category: Category) => void,
  level = 0,
): React.ReactNode {
  return (
    <div key={category.id}>
      <CategoryRow
        category={category}
        level={level}
        productCount={productCounts.get(String(category.id)) ?? 0}
        onEdit={onEdit}
        onRemove={onRemove}
      />
      {(byParent.get(String(category.id)) ?? []).map((child) => (
        renderCategory(child, byParent, productCounts, onEdit, onRemove, level + 1)
      ))}
    </div>
  );
}

function CategoryRow({
  category,
  level,
  productCount,
  onEdit,
  onRemove,
}: {
  category: Category;
  level: number;
  productCount: number;
  onEdit: (category: Category) => void;
  onRemove: (category: Category) => void;
}) {
  const child = level > 0;

  return (
    <div
      className={`group flex items-center justify-between gap-4 px-5 py-4 ${child ? 'bg-[#fbfaf4]' : 'bg-white'}`}
      data-category-row="true"
      data-category-level={level}
      style={{ paddingLeft: child ? `${3 + Math.min(level - 1, 3) * 1.5}rem` : undefined }}
    >
      <div className="min-w-0">
        <div className="flex flex-wrap items-center gap-2">
          {child && <span className="text-[#9aa89d]">↳</span>}
          <h3 className={`truncate font-semibold ${child ? 'text-[#53685c]' : 'text-[#26382d]'}`}>{category.name}</h3>
          {child && <Badge tone="gray">Подкатегория</Badge>}
        </div>
        <div className="mt-2 flex flex-wrap items-center gap-2">
          <Badge tone="gray">{productCount} товаров</Badge>
          <span className="text-xs text-[#789083]">/{category.slug}</span>
        </div>
      </div>
      <div className="flex shrink-0 gap-1 opacity-0 transition group-hover:opacity-100">
        <Button variant="ghost" size="icon" onClick={() => onEdit(category)} title="Изменить">
          <Edit className="h-4 w-4" />
        </Button>
        <Button variant="ghost" size="icon" className="text-[#ef4444]" onClick={() => onRemove(category)} title="Удалить">
          <Trash2 className="h-4 w-4" />
        </Button>
      </div>
    </div>
  );
}

function compareCategories(left: Category, right: Category) {
  if (left.sort_order !== right.sort_order) {
    return left.sort_order - right.sort_order;
  }

  return left.name.localeCompare(right.name, 'ru');
}

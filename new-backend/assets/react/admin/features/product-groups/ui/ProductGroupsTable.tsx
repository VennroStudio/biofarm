import { Edit, Trash2 } from 'lucide-react';
import { Button, Card } from '../../../shared/ui';
import type { ProductGroup } from '../../../types';

type Props = {
  groups: ProductGroup[];
  onEdit: (group: ProductGroup) => void;
  onRemove: (group: ProductGroup) => void;
};

export function ProductGroupsTable({ groups, onEdit, onRemove }: Props) {
  if (groups.length === 0) {
    return (
      <Card className="p-8 text-center text-[#789083]">
        Группы товаров пока не добавлены
      </Card>
    );
  }

  return (
    <div className="grid gap-3 md:grid-cols-2">
      {groups.map((group) => (
        <Card key={group.id} className="p-5">
          <div className="flex items-start justify-between gap-4">
            <div>
              <h2 className="font-bold text-[#1f3328]">{group.name}</h2>
              <p className="mt-1 text-sm text-[#789083]">{group.products_count} товаров</p>
            </div>
            <div className="flex gap-2">
              <Button size="sm" variant="outline" onClick={() => onEdit(group)} aria-label={`Изменить ${group.name}`}>
                <Edit className="h-4 w-4" />
              </Button>
              <Button size="sm" variant="danger" onClick={() => onRemove(group)} aria-label={`Удалить ${group.name}`}>
                <Trash2 className="h-4 w-4" />
              </Button>
            </div>
          </div>
        </Card>
      ))}
    </div>
  );
}

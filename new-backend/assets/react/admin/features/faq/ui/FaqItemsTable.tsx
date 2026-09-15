import { Edit, Trash2 } from 'lucide-react';
import { Badge, Button, EmptyState } from '../../../shared/ui';
import type { FaqItem } from '../../../types';
import { faqScopeLabel } from '../model/faqForm';

type Props = {
  items: FaqItem[];
  onEdit: (item: FaqItem) => void;
  onRemove: (item: FaqItem) => void;
};

export function FaqItemsTable({ items, onEdit, onRemove }: Props) {
  if (items.length === 0) {
    return <EmptyState>FAQ пока не добавлен</EmptyState>;
  }

  return (
    <div className="grid gap-3">
      {items.map((item) => (
        <article key={item.id} className="rounded-lg border border-[#dfece9] bg-white p-5 shadow-sm">
          <div className="flex flex-wrap items-start justify-between gap-4">
            <div className="min-w-0">
              <div className="flex flex-wrap items-center gap-2">
                <h2 className="font-bold text-[#294555]">{item.question}</h2>
                <Badge tone={item.is_active ? 'green' : 'gray'}>{item.is_active ? 'Активен' : 'Выключен'}</Badge>
                <Badge tone="gray">{faqScopeLabel(item.page_scope)}</Badge>
                {item.page_id && <Badge tone="amber">{item.page_id}</Badge>}
              </div>
              <p className="mt-2 max-w-4xl text-sm text-[#526d78]">{item.answer}</p>
            </div>
            <div className="flex gap-2">
              <Button variant="ghost" size="icon" onClick={() => onEdit(item)} title="Изменить">
                <Edit className="h-4 w-4" />
              </Button>
              <Button variant="ghost" size="icon" className="text-[#ef4444]" onClick={() => onRemove(item)} title="Удалить">
                <Trash2 className="h-4 w-4" />
              </Button>
            </div>
          </div>
        </article>
      ))}
    </div>
  );
}

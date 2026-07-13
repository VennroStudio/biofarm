import { Edit, Plus, Trash2 } from 'lucide-react';
import { Badge, Button, Card } from '../../../shared/ui';
import type { AttributeValue, ProductAttribute } from '../../../types';

type Props = {
  attributes: ProductAttribute[];
  onCreateValue: (attribute: ProductAttribute) => void;
  onEditAttribute: (attribute: ProductAttribute) => void;
  onEditValue: (attribute: ProductAttribute, value: AttributeValue) => void;
  onRemoveAttribute: (attribute: ProductAttribute) => void;
  onRemoveValue: (value: AttributeValue) => void;
};

export function AttributesTable({
  attributes,
  onCreateValue,
  onEditAttribute,
  onEditValue,
  onRemoveAttribute,
  onRemoveValue,
}: Props) {
  if (attributes.length === 0) {
    return (
      <Card className="p-8 text-center text-[#789083]">
        Атрибуты пока не добавлены
      </Card>
    );
  }

  return (
    <div className="grid gap-4">
      {attributes.map((attribute) => (
        <Card key={attribute.id} className="p-5">
          <div className="flex flex-wrap items-start justify-between gap-4">
            <div>
              <div className="flex flex-wrap items-center gap-2">
                <h2 className="text-lg font-bold text-[#1f3328]">{attribute.name}</h2>
                <Badge tone="gray">{attribute.slug}</Badge>
                {attribute.filter_prefix && <Badge tone="green">/{attribute.filter_prefix}/</Badge>}
              </div>
              <p className="mt-1 text-sm text-[#789083]">
                {attribute.values_count} значений, {attribute.products_count} товаров
              </p>
            </div>
            <div className="flex flex-wrap gap-2">
              <Button size="sm" onClick={() => onCreateValue(attribute)}>
                <Plus className="h-4 w-4" />Значение
              </Button>
              <Button size="sm" variant="outline" onClick={() => onEditAttribute(attribute)}>
                <Edit className="h-4 w-4" />Изменить
              </Button>
              <Button size="sm" variant="danger" onClick={() => onRemoveAttribute(attribute)}>
                <Trash2 className="h-4 w-4" />Удалить
              </Button>
            </div>
          </div>

          {attribute.values.length > 0 && (
            <div className="mt-5 grid gap-2">
              {attribute.values.map((value) => (
                <div key={value.id} className="flex flex-wrap items-center justify-between gap-3 rounded-md border border-[#e4e5da] bg-[#fbfaf4] px-4 py-3">
                  <div>
                    <div className="flex flex-wrap items-center gap-2">
                      <p className="font-semibold text-[#26382d]">{value.name}</p>
                      <Badge tone="gray">{value.slug}</Badge>
                      {!value.is_indexable && <Badge tone="gray">noindex</Badge>}
                    </div>
                    <p className="mt-1 text-sm text-[#789083]">{value.products_count} товаров</p>
                  </div>
                  <div className="flex gap-2">
                    <Button size="sm" variant="outline" onClick={() => onEditValue(attribute, value)} aria-label={`Изменить ${value.name}`}>
                      <Edit className="h-4 w-4" />
                    </Button>
                    <Button size="sm" variant="danger" onClick={() => onRemoveValue(value)} aria-label={`Удалить ${value.name}`}>
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </Card>
      ))}
    </div>
  );
}

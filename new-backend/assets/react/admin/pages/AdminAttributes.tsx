import { Plus } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { attributesApi } from '../api/resources';
import {
  attributeFormFromAttribute,
  attributePayloadFromForm,
  attributeValueFormFromValue,
  attributeValuePayloadFromForm,
  emptyAttributeForm,
  emptyAttributeValueForm,
  type AttributeForm,
  type AttributeValueForm,
} from '../features/attributes/model/attributeForm';
import { AttributeFormModal } from '../features/attributes/ui/AttributeFormModal';
import { AttributeValueFormModal } from '../features/attributes/ui/AttributeValueFormModal';
import { AttributesTable } from '../features/attributes/ui/AttributesTable';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { Button, PageHeader } from '../shared/ui';
import type { AttributeValue, ProductAttribute } from '../types';

export function AdminAttributes() {
  const [attributes, setAttributes] = useState<ProductAttribute[]>([]);
  const [attributeForm, setAttributeForm] = useState<AttributeForm>(emptyAttributeForm);
  const [valueForm, setValueForm] = useState<AttributeValueForm>(emptyAttributeValueForm(0));
  const [valueAttributeName, setValueAttributeName] = useState('');
  const [attributeDialogOpen, setAttributeDialogOpen] = useState(false);
  const [valueDialogOpen, setValueDialogOpen] = useState(false);
  const [saving, setSaving] = useState(false);

  async function load() {
    const result = await attributesApi.list();
    setAttributes(result.items);
  }

  useLoadOnMount(load);

  function openCreateAttribute() {
    setAttributeForm(emptyAttributeForm);
    setAttributeDialogOpen(true);
  }

  function openEditAttribute(attribute: ProductAttribute) {
    setAttributeForm(attributeFormFromAttribute(attribute));
    setAttributeDialogOpen(true);
  }

  function openCreateValue(attribute: ProductAttribute) {
    setValueForm(emptyAttributeValueForm(attribute.id));
    setValueAttributeName(attribute.name);
    setValueDialogOpen(true);
  }

  function openEditValue(attribute: ProductAttribute, value: AttributeValue) {
    setValueForm(attributeValueFormFromValue(value));
    setValueAttributeName(attribute.name);
    setValueDialogOpen(true);
  }

  async function submitAttribute(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    try {
      if (attributeForm.id) {
        await attributesApi.update(attributeForm.id, attributePayloadFromForm(attributeForm));
      } else {
        await attributesApi.create(attributePayloadFromForm(attributeForm));
      }
      setAttributeDialogOpen(false);
      await load();
    } finally {
      setSaving(false);
    }
  }

  async function submitValue(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    try {
      if (valueForm.id) {
        await attributesApi.updateValue(valueForm.id, attributeValuePayloadFromForm(valueForm));
      } else {
        await attributesApi.createValue(valueForm.attribute_id, attributeValuePayloadFromForm(valueForm));
      }
      setValueDialogOpen(false);
      await load();
    } finally {
      setSaving(false);
    }
  }

  async function removeAttribute(attribute: ProductAttribute) {
    if (!confirm(`Удалить атрибут "${attribute.name}" вместе со значениями?`)) {
      return;
    }
    await attributesApi.delete(attribute.id);
    await load();
  }

  async function removeValue(value: AttributeValue) {
    if (!confirm(`Удалить значение "${value.name}"?`)) {
      return;
    }
    await attributesApi.deleteValue(value.id);
    await load();
  }

  return (
    <>
      <PageHeader
        title="Атрибуты"
        subtitle="Фильтры, характеристики и SEO-значения товаров"
        actions={<Button onClick={openCreateAttribute}><Plus className="h-4 w-4" />Добавить атрибут</Button>}
      />

      <AttributesTable
        attributes={attributes}
        onCreateValue={openCreateValue}
        onEditAttribute={openEditAttribute}
        onEditValue={openEditValue}
        onRemoveAttribute={(attribute) => void removeAttribute(attribute)}
        onRemoveValue={(value) => void removeValue(value)}
      />

      <AttributeFormModal
        form={attributeForm}
        open={attributeDialogOpen}
        saving={saving}
        setForm={setAttributeForm}
        onClose={() => setAttributeDialogOpen(false)}
        onSubmit={(event) => void submitAttribute(event)}
      />
      <AttributeValueFormModal
        attributeName={valueAttributeName}
        form={valueForm}
        open={valueDialogOpen}
        saving={saving}
        setForm={setValueForm}
        onClose={() => setValueDialogOpen(false)}
        onSubmit={(event) => void submitValue(event)}
      />
    </>
  );
}

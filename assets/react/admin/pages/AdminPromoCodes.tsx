import { Plus } from 'lucide-react';
import type { FormEvent } from 'react';
import { useMemo, useState } from 'react';
import { promoCodesApi } from '../api/resources';
import {
  emptyPromoCodeForm,
  promoCodeFormFromPromoCode,
  promoCodePayloadFromForm,
  type PromoCodeForm,
} from '../features/promo-codes/model/promoCodeForm';
import { PromoCodeFormModal } from '../features/promo-codes/ui/PromoCodeFormModal';
import { PromoCodesTable } from '../features/promo-codes/ui/PromoCodesTable';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { messageFromError } from '../shared/lib';
import { Badge, Button, Card, ErrorAlert, PageHeader, SearchField } from '../shared/ui';
import type { PromoCode } from '../types';

export function AdminPromoCodes() {
  const [promoCodes, setPromoCodes] = useState<PromoCode[]>([]);
  const [search, setSearch] = useState('');
  const [form, setForm] = useState<PromoCodeForm>(emptyPromoCodeForm);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  const filteredPromoCodes = useMemo(
    () => promoCodes.filter((promoCode) => promoCode.code.toLowerCase().includes(search.toLowerCase())),
    [promoCodes, search],
  );

  async function load() {
    const result = await promoCodesApi.list();
    setPromoCodes(result.items);
  }

  useLoadOnMount(load);

  function openCreate() {
    setError(null);
    setForm(emptyPromoCodeForm);
    setDialogOpen(true);
  }

  function openEdit(promoCode: PromoCode) {
    setError(null);
    setForm(promoCodeFormFromPromoCode(promoCode));
    setDialogOpen(true);
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);
    setSaving(true);
    try {
      if (form.id) {
        await promoCodesApi.update(form.id, promoCodePayloadFromForm(form));
      } else {
        await promoCodesApi.create(promoCodePayloadFromForm(form));
      }
      setDialogOpen(false);
      await load();
    } catch (submitError) {
      setError(messageFromError(submitError, 'Не удалось сохранить промокод'));
    } finally {
      setSaving(false);
    }
  }

  async function remove(promoCode: PromoCode) {
    if (!confirm(`Удалить промокод "${promoCode.code}"?`)) {
      return;
    }
    setError(null);
    try {
      await promoCodesApi.delete(promoCode.id);
      await load();
    } catch (removeError) {
      setError(messageFromError(removeError, 'Не удалось удалить промокод'));
    }
  }

  return (
    <>
      <PageHeader
        title="Промокоды"
        subtitle="Скидки для checkout. Раздел работает, когда включены заказы и промокоды."
        actions={<Button onClick={openCreate}><Plus className="h-4 w-4" />Добавить промокод</Button>}
      />

      <ErrorAlert className="mb-5">{dialogOpen ? null : error}</ErrorAlert>

      <Card className="p-6">
        <div className="mb-8 flex flex-wrap items-center gap-4">
          <SearchField placeholder="Поиск промокода..." value={search} onChange={setSearch} />
          <Badge tone="gray">{filteredPromoCodes.length} промокодов</Badge>
        </div>

        <PromoCodesTable promoCodes={filteredPromoCodes} onEdit={openEdit} onRemove={(promoCode) => void remove(promoCode)} />
      </Card>

      <PromoCodeFormModal
        form={form}
        open={dialogOpen}
        error={dialogOpen ? error : null}
        saving={saving}
        setForm={setForm}
        onClose={() => {
          setDialogOpen(false);
          setError(null);
        }}
        onSubmit={(event) => void submit(event)}
      />
    </>
  );
}

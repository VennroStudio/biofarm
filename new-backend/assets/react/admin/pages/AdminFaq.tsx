import { HelpCircle, Plus } from 'lucide-react';
import type { FormEvent } from 'react';
import { useMemo, useState } from 'react';
import { faqApi } from '../api/resources';
import {
  emptyFaqForm,
  faqFormFromItem,
  faqPayloadFromForm,
  type FaqForm,
} from '../features/faq/model/faqForm';
import { FaqFormModal } from '../features/faq/ui/FaqFormModal';
import { FaqItemsTable } from '../features/faq/ui/FaqItemsTable';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { messageFromError } from '../shared/lib';
import { Badge, Button, Card, ErrorAlert, PageHeader, SearchField } from '../shared/ui';
import type { FaqItem } from '../types';

export function AdminFaq() {
  const [items, setItems] = useState<FaqItem[]>([]);
  const [search, setSearch] = useState('');
  const [form, setForm] = useState<FaqForm>(emptyFaqForm);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  const filteredItems = useMemo(
    () => items.filter((item) => `${item.question} ${item.answer} ${item.page_id || ''}`.toLowerCase().includes(search.toLowerCase())),
    [items, search],
  );

  async function load() {
    const result = await faqApi.list();
    setItems(result.items);
  }

  useLoadOnMount(load);

  function openCreate() {
    setError(null);
    setForm(emptyFaqForm);
    setDialogOpen(true);
  }

  function openEdit(item: FaqItem) {
    setError(null);
    setForm(faqFormFromItem(item));
    setDialogOpen(true);
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);
    setSaving(true);
    try {
      if (form.id) {
        await faqApi.update(form.id, faqPayloadFromForm(form));
      } else {
        await faqApi.create(faqPayloadFromForm(form));
      }
      setDialogOpen(false);
      await load();
    } catch (submitError) {
      setError(messageFromError(submitError, 'Не удалось сохранить вопрос'));
    } finally {
      setSaving(false);
    }
  }

  async function remove(item: FaqItem) {
    if (!confirm(`Удалить вопрос "${item.question}"?`)) {
      return;
    }
    setError(null);
    try {
      await faqApi.delete(item.id);
      await load();
    } catch (removeError) {
      setError(messageFromError(removeError, 'Не удалось удалить вопрос'));
    }
  }

  return (
    <>
      <PageHeader
        title="FAQ"
        subtitle="Вопросы для страниц сайта и FAQPage JSON-LD"
        actions={<Button onClick={openCreate}><Plus className="h-4 w-4" />Добавить вопрос</Button>}
      />

      <ErrorAlert className="mb-5">{dialogOpen ? null : error}</ErrorAlert>

      <Card className="p-6">
        <div className="mb-8 flex flex-wrap items-center gap-4">
          <SearchField placeholder="Поиск FAQ..." value={search} onChange={setSearch} />
          <Badge tone="gray"><HelpCircle className="mr-1 h-3.5 w-3.5" />{filteredItems.length} вопросов</Badge>
        </div>
        <FaqItemsTable items={filteredItems} onEdit={openEdit} onRemove={(item) => void remove(item)} />
      </Card>

      <FaqFormModal
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

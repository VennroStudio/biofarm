import { Plus } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import { pagesApi } from '../api/resources';
import { emptyPageForm, pageFormFromPage, pagePayloadFromForm, type PageForm } from '../features/pages/model/pageForm';
import { PageFormModal } from '../features/pages/ui/PageFormModal';
import { pagePath, PagesTable } from '../features/pages/ui/PagesTable';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { messageFromError } from '../shared/lib';
import { Badge, Button, Card, ErrorAlert, PageHeader, SearchField } from '../shared/ui';
import type { CmsPage, CmsPageTemplate } from '../types';

export function AdminPages() {
  const [pages, setPages] = useState<CmsPage[]>([]);
  const [templates, setTemplates] = useState<CmsPageTemplate[]>([]);
  const [search, setSearch] = useState('');
  const [form, setForm] = useState<PageForm>(emptyPageForm);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  const filteredPages = useMemo(() => {
    const query = search.trim().toLowerCase();
    if (!query) {
      return pages;
    }

    return pages.filter((page) => {
      const path = pagePath(page) ?? '';
      return page.title.toLowerCase().includes(query)
        || path.toLowerCase().includes(query)
        || (page.system_key ?? '').toLowerCase().includes(query);
    });
  }, [pages, search]);

  async function load() {
    const [pageResult, templateResult] = await Promise.all([pagesApi.list(), pagesApi.templates()]);
    setPages(pageResult.items);
    setTemplates(templateResult);
  }

  useLoadOnMount(load);

  function openCreate() {
    setError(null);
    setForm(emptyPageForm);
    setDialogOpen(true);
  }

  function openEdit(page: CmsPage) {
    setError(null);
    setForm(pageFormFromPage(page));
    setDialogOpen(true);
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);
    setSaving(true);
    try {
      if (form.id) {
        await pagesApi.update(form.id, pagePayloadFromForm(form));
      } else {
        await pagesApi.create(pagePayloadFromForm(form));
      }

      setDialogOpen(false);
      await load();
    } catch (submitError) {
      setError(messageFromError(submitError, 'Не удалось сохранить страницу'));
    } finally {
      setSaving(false);
    }
  }

  async function remove(page: CmsPage) {
    if (page.page_type === 'system') {
      setError('Системные страницы нельзя удалить.');
      return;
    }

    if (!confirm(`Удалить страницу "${page.title}"?`)) {
      return;
    }

    setError(null);
    try {
      await pagesApi.delete(page.id);
      await load();
    } catch (removeError) {
      setError(messageFromError(removeError, 'Не удалось удалить страницу'));
    }
  }

  return (
    <>
      <PageHeader
        title="Страницы"
        subtitle="SEO системных страниц и простые CMS-страницы"
        actions={<Button onClick={openCreate}><Plus className="h-4 w-4" />Добавить страницу</Button>}
      />

      <ErrorAlert className="mb-5">{dialogOpen ? null : error}</ErrorAlert>

      <Card className="p-6">
        <div className="mb-8 flex flex-wrap items-center gap-4">
          <SearchField placeholder="Поиск страниц..." value={search} onChange={setSearch} />
          <Badge tone="gray">{filteredPages.length} страниц</Badge>
        </div>

        <PagesTable pages={filteredPages} onEdit={openEdit} onRemove={(page) => void remove(page)} />
      </Card>

      {dialogOpen && <PageFormModal
        form={form}
        open={dialogOpen}
        error={dialogOpen ? error : null}
        saving={saving}
        templates={templates}
        setForm={setForm}
        onClose={() => {
          setDialogOpen(false);
          setError(null);
        }}
        onSubmit={(event) => void submit(event)}
      />}
    </>
  );
}

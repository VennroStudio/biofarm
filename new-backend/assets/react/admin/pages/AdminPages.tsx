import { Plus } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import { pagesApi } from '../api/resources';
import { emptyPageForm, pageFormFromPage, pagePayloadFromForm, type PageForm } from '../features/pages/model/pageForm';
import { PageFormModal } from '../features/pages/ui/PageFormModal';
import { pagePath, PagesTable } from '../features/pages/ui/PagesTable';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { Badge, Button, Card, PageHeader, SearchField } from '../shared/ui';
import type { CmsPage, CmsPageTemplate } from '../types';

export function AdminPages() {
  const [pages, setPages] = useState<CmsPage[]>([]);
  const [templates, setTemplates] = useState<CmsPageTemplate[]>([]);
  const [search, setSearch] = useState('');
  const [form, setForm] = useState<PageForm>(emptyPageForm);
  const [dialogOpen, setDialogOpen] = useState(false);
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
    setForm(emptyPageForm);
    setDialogOpen(true);
  }

  function openEdit(page: CmsPage) {
    setForm(pageFormFromPage(page));
    setDialogOpen(true);
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    try {
      if (form.id) {
        await pagesApi.update(form.id, pagePayloadFromForm(form));
      } else {
        await pagesApi.create(pagePayloadFromForm(form));
      }

      setDialogOpen(false);
      await load();
    } finally {
      setSaving(false);
    }
  }

  async function remove(page: CmsPage) {
    if (page.page_type === 'system') {
      alert('Системные страницы нельзя удалить.');
      return;
    }

    if (!confirm(`Удалить страницу "${page.title}"?`)) {
      return;
    }

    await pagesApi.delete(page.id);
    await load();
  }

  return (
    <>
      <PageHeader
        title="Страницы"
        subtitle="SEO системных страниц и простые CMS-страницы"
        actions={<Button onClick={openCreate}><Plus className="h-4 w-4" />Добавить страницу</Button>}
      />

      <Card className="p-6">
        <div className="mb-8 flex flex-wrap items-center gap-4">
          <SearchField placeholder="Поиск страниц..." value={search} onChange={setSearch} />
          <Badge tone="gray">{filteredPages.length} страниц</Badge>
        </div>

        <PagesTable pages={filteredPages} onEdit={openEdit} onRemove={(page) => void remove(page)} />
      </Card>

      <PageFormModal
        form={form}
        open={dialogOpen}
        saving={saving}
        templates={templates}
        setForm={setForm}
        onClose={() => setDialogOpen(false)}
        onSubmit={(event) => void submit(event)}
      />
    </>
  );
}

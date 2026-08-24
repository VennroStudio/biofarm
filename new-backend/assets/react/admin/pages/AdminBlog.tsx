import { Plus } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import { blogApi } from '../api/resources';
import {
  blogFormFromPost,
  blogPayloadFromForm,
  emptyBlogForm,
  type BlogForm,
} from '../features/blog/model/blogForm';
import { BlogFormModal } from '../features/blog/ui/BlogFormModal';
import { BlogTable } from '../features/blog/ui/BlogTable';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { messageFromError } from '../shared/lib';
import { Badge, Button, Card, ErrorAlert, PageHeader, SearchField } from '../shared/ui';
import type { BlogPost } from '../types';

export function AdminBlog() {
  const [posts, setPosts] = useState<BlogPost[]>([]);
  const [search, setSearch] = useState('');
  const [form, setForm] = useState<BlogForm>(emptyBlogForm);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  const filteredPosts = useMemo(
    () => posts.filter((post) => post.title.toLowerCase().includes(search.toLowerCase())),
    [posts, search],
  );

  async function load() {
    const result = await blogApi.list();
    setPosts(result.items);
  }

  useLoadOnMount(load);

  function openCreate() {
    setError(null);
    setForm(emptyBlogForm);
    setDialogOpen(true);
  }

  function openEdit(post: BlogPost) {
    setError(null);
    setForm(blogFormFromPost(post));
    setDialogOpen(true);
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);
    setSaving(true);
    try {
      if (form.id) {
        await blogApi.update(form.id, blogPayloadFromForm(form));
      } else {
        await blogApi.create(blogPayloadFromForm(form));
      }
      setDialogOpen(false);
      await load();
    } catch (submitError) {
      setError(messageFromError(submitError, 'Не удалось сохранить статью'));
    } finally {
      setSaving(false);
    }
  }

  async function remove(post: BlogPost) {
    if (!confirm(`Удалить статью "${post.title}"?`)) {
      return;
    }
    setError(null);
    try {
      await blogApi.delete(post.id);
      await load();
    } catch (removeError) {
      setError(messageFromError(removeError, 'Не удалось удалить статью'));
    }
  }

  return (
    <>
      <PageHeader
        title="Блог"
        subtitle="Управление статьями блога"
        actions={<Button onClick={openCreate}><Plus className="h-4 w-4" />Написать статью</Button>}
      />

      <ErrorAlert className="mb-5">{dialogOpen ? null : error}</ErrorAlert>

      <Card className="p-6">
        <div className="mb-8 flex flex-wrap items-center gap-4">
          <SearchField placeholder="Поиск статей..." value={search} onChange={setSearch} />
          <Badge tone="gray">{filteredPosts.length} статей</Badge>
        </div>

        <BlogTable posts={filteredPosts} onEdit={openEdit} onRemove={(post) => void remove(post)} />
      </Card>

      <BlogFormModal
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

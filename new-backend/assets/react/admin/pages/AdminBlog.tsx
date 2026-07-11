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
import { Badge, Button, Card, PageHeader, SearchField } from '../shared/ui';
import type { BlogPost } from '../types';

export function AdminBlog() {
  const [posts, setPosts] = useState<BlogPost[]>([]);
  const [search, setSearch] = useState('');
  const [form, setForm] = useState<BlogForm>(emptyBlogForm);
  const [dialogOpen, setDialogOpen] = useState(false);
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
    setForm(emptyBlogForm);
    setDialogOpen(true);
  }

  function openEdit(post: BlogPost) {
    setForm(blogFormFromPost(post));
    setDialogOpen(true);
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    try {
      if (form.id) {
        await blogApi.update(form.id, blogPayloadFromForm(form));
      } else {
        await blogApi.create(blogPayloadFromForm(form));
      }
      setDialogOpen(false);
      await load();
    } finally {
      setSaving(false);
    }
  }

  async function remove(post: BlogPost) {
    if (!confirm(`Удалить статью "${post.title}"?`)) {
      return;
    }
    await blogApi.delete(post.id);
    await load();
  }

  return (
    <>
      <PageHeader
        title="Блог"
        subtitle="Управление статьями блога"
        actions={<Button onClick={openCreate}><Plus className="h-4 w-4" />Написать статью</Button>}
      />

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
        saving={saving}
        setForm={setForm}
        onClose={() => setDialogOpen(false)}
        onSubmit={(event) => void submit(event)}
      />
    </>
  );
}

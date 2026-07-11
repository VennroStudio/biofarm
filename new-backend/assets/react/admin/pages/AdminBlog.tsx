import { Edit, Eye, ImageOff, Plus, Search, Trash2 } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import { blogApi } from '../api/resources';
import { ImageUploader } from '../components/ImageUploader';
import {
  AdminTable,
  Badge,
  Button,
  Card,
  EmptyState,
  Field,
  inputClass,
  Modal,
  PageHeader,
  TableCell,
  TableHead,
  TableHeaderCell,
  TableRow,
  textareaClass,
} from '../components/ui';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import type { BlogPost } from '../types';

type BlogForm = {
  id?: number;
  title: string;
  slug: string;
  excerpt: string;
  content: string;
  image: string;
  category_id: string;
  author_name: string;
  read_time: string;
  is_published: boolean;
};

const emptyForm: BlogForm = {
  title: '',
  slug: '',
  excerpt: '',
  content: '',
  image: '',
  category_id: 'health',
  author_name: 'Редакция BioFarm',
  read_time: '5',
  is_published: true,
};

function formFromPost(post: BlogPost): BlogForm {
  return {
    id: post.id,
    title: post.title,
    slug: post.slug,
    excerpt: post.excerpt,
    content: post.content,
    image: post.image,
    category_id: post.category_id,
    author_name: post.author_name,
    read_time: String(post.read_time),
    is_published: post.is_published,
  };
}

function ImagePreview({ src, title }: { src: string; title: string }) {
  const [failed, setFailed] = useState(false);

  if (!src || failed) {
    return (
      <span className="grid h-12 w-12 place-items-center rounded bg-[#eef1e8] text-[#789083]">
        <ImageOff className="h-4 w-4" />
      </span>
    );
  }

  return <img src={src} alt={title} className="h-12 w-12 rounded object-cover" onError={() => setFailed(true)} />;
}

export function AdminBlog() {
  const [posts, setPosts] = useState<BlogPost[]>([]);
  const [search, setSearch] = useState('');
  const [form, setForm] = useState<BlogForm>(emptyForm);
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
    setForm(emptyForm);
    setDialogOpen(true);
  }

  function openEdit(post: BlogPost) {
    setForm(formFromPost(post));
    setDialogOpen(true);
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    try {
      const payload = {
        title: form.title,
        slug: form.slug || null,
        excerpt: form.excerpt,
        content: form.content,
        image: form.image,
        categoryId: form.category_id,
        authorName: form.author_name,
        readTime: Number(form.read_time),
        isPublished: form.is_published,
      };
      if (form.id) {
        await blogApi.update(form.id, payload);
      } else {
        await blogApi.create(payload);
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
          <div className="relative w-full max-w-sm">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#789083]" />
            <input
              className={`${inputClass} pl-10`}
              placeholder="Поиск статей..."
              value={search}
              onChange={(event) => setSearch(event.target.value)}
            />
          </div>
          <Badge tone="gray">{filteredPosts.length} статей</Badge>
        </div>

        {filteredPosts.length === 0 ? (
          <EmptyState>Статьи не найдены</EmptyState>
        ) : (
          <div className="overflow-x-auto">
            <AdminTable>
              <TableHead>
                <tr>
                  <TableHeaderCell className="w-20">Фото</TableHeaderCell>
                  <TableHeaderCell>Заголовок</TableHeaderCell>
                  <TableHeaderCell>Категория</TableHeaderCell>
                  <TableHeaderCell>Автор</TableHeaderCell>
                  <TableHeaderCell>Дата</TableHeaderCell>
                  <TableHeaderCell className="text-right">Действия</TableHeaderCell>
                </tr>
              </TableHead>
              <tbody>
                {filteredPosts.map((post) => (
                  <TableRow key={post.id} className={!post.is_published ? 'opacity-60' : ''}>
                    <TableCell>
                      <ImagePreview src={post.image} title={post.title} />
                    </TableCell>
                    <TableCell className="min-w-[420px]">
                      <p className="line-clamp-1 font-semibold text-[#26382d]">{post.title}</p>
                      <p className="line-clamp-1 text-sm text-[#789083]">{post.excerpt}</p>
                    </TableCell>
                    <TableCell><Badge tone="gray">{post.category_id}</Badge></TableCell>
                    <TableCell>{post.author_name}</TableCell>
                    <TableCell>{new Date(post.created_at).toLocaleDateString('ru-RU')}</TableCell>
                    <TableCell>
                      <div className="flex justify-end gap-2">
                        <a
                          href={`/blog/${post.slug}`}
                          target="_blank"
                          rel="noreferrer"
                          className="grid h-9 w-9 place-items-center rounded-md text-[#53685c] transition hover:bg-[#eef1e8]"
                          title="Открыть"
                        >
                          <Eye className="h-4 w-4" />
                        </a>
                        <Button variant="ghost" size="icon" onClick={() => openEdit(post)} title="Изменить">
                          <Edit className="h-4 w-4" />
                        </Button>
                        <Button variant="ghost" size="icon" className="text-[#ef4444]" onClick={() => void remove(post)} title="Удалить">
                          <Trash2 className="h-4 w-4" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </tbody>
            </AdminTable>
          </div>
        )}
      </Card>

      <Modal
        open={dialogOpen}
        title={form.id ? 'Редактировать статью' : 'Новая статья'}
        maxWidth="max-w-3xl"
        onClose={() => setDialogOpen(false)}
        footer={(
          <>
            <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>Отмена</Button>
            <Button type="submit" form="admin-blog-form" disabled={saving || !form.title || !form.excerpt || !form.content}>
              {saving ? 'Сохранение...' : (form.id ? 'Сохранить' : 'Опубликовать')}
            </Button>
          </>
        )}
      >
        <form id="admin-blog-form" className="grid gap-4" onSubmit={(event) => void submit(event)}>
          <Field label="Заголовок *">
            <input className={inputClass} value={form.title} onChange={(event) => setForm({ ...form, title: event.target.value })} />
          </Field>
          <div className="grid gap-4 md:grid-cols-2">
            <Field label="Категория *">
              <input className={inputClass} value={form.category_id} onChange={(event) => setForm({ ...form, category_id: event.target.value })} placeholder="health" />
            </Field>
            <Field label="Имя автора">
              <input className={inputClass} value={form.author_name} onChange={(event) => setForm({ ...form, author_name: event.target.value })} />
            </Field>
          </div>
          <Field label="URL изображения">
            <input className={inputClass} value={form.image} onChange={(event) => setForm({ ...form, image: event.target.value })} />
          </Field>
          <ImageUploader scope="blog" onUploaded={(url) => setForm({ ...form, image: url })} />
          <Field label="Краткое описание *">
            <textarea className={textareaClass} value={form.excerpt} onChange={(event) => setForm({ ...form, excerpt: event.target.value })} />
          </Field>
          <Field label="Содержание *">
            <textarea className="min-h-72 w-full rounded-md border border-[#d9dece] bg-[#fbfaf4] px-3 py-2 text-sm outline-none focus:border-[#2f7d4b] focus:bg-white" value={form.content} onChange={(event) => setForm({ ...form, content: event.target.value })} />
          </Field>
          <div className="grid gap-4 md:grid-cols-2">
            <Field label="Slug">
              <input className={inputClass} value={form.slug} onChange={(event) => setForm({ ...form, slug: event.target.value })} />
            </Field>
            <Field label="Минут чтения">
              <input className={inputClass} type="number" value={form.read_time} onChange={(event) => setForm({ ...form, read_time: event.target.value })} />
            </Field>
          </div>
          <label className="flex items-center gap-2 text-sm font-semibold text-[#26382d]">
            <input type="checkbox" checked={form.is_published} onChange={(event) => setForm({ ...form, is_published: event.target.checked })} />
            Опубликована
          </label>
        </form>
      </Modal>
    </>
  );
}

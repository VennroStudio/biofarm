import { lazy, Suspense, useEffect, useRef, useState, type Dispatch, type FormEvent, type KeyboardEvent, type ReactNode, type SetStateAction } from 'react';
import { FileText, ImageIcon, Info, Search } from 'lucide-react';
import type { BlogCategory } from '../../../types';
import { ImageUploader } from '../../media/ui/ImageUploader';
import { Button, ErrorAlert, Field, inputClass, Modal, textareaClass } from '../../../shared/ui';
import { blogFormIssue, type BlogForm, type BlogFormIssue } from '../model/blogForm';

const RichTextEditor = lazy(() => import('../../../shared/ui/RichTextEditor'));
const tabs = [
  { id: 'main', title: 'Основные', icon: Info },
  { id: 'cover', title: 'Обложка', icon: ImageIcon },
  { id: 'content', title: 'Содержание', icon: FileText },
  { id: 'seo', title: 'SEO', icon: Search },
] as const;
type Tab = typeof tabs[number]['id'];
type TextKey = { [K in keyof BlogForm]: BlogForm[K] extends string ? K : never }[keyof BlogForm] & string;
const issueTab = (field: BlogFormIssue['field']): Tab => field === 'image' ? 'cover' : field === 'content' || field === 'excerpt' || field === 'read_time' ? 'content' : 'main';

function Group({ title, children }: { title: string; children: ReactNode }) {
  return <section className="space-y-4 rounded-2xl border border-[#dfece9] bg-white p-4 sm:p-5"><h3 className="text-base font-semibold text-[#294555]">{title}</h3>{children}</section>;
}

type Props = {
  form: BlogForm;
  categories: BlogCategory[];
  open: boolean;
  error?: string | null;
  saving: boolean;
  setForm: Dispatch<SetStateAction<BlogForm>>;
  onClose: () => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};

export function BlogFormModal({ form, categories, open, error, saving, setForm, onClose, onSubmit }: Props) {
  const [activeTab, setActiveTab] = useState<Tab>('main');
  const [issue, setIssue] = useState<BlogFormIssue | null>(null);
  const [confirmClose, setConfirmClose] = useState(false);
  const [initialForm] = useState(() => JSON.stringify(form));
  const formRef = useRef<HTMLFormElement>(null);
  const dirty = initialForm !== JSON.stringify(form);

  useEffect(() => {
    if (!dirty) return;
    const beforeUnload = (event: BeforeUnloadEvent) => { event.preventDefault(); event.returnValue = ''; };
    window.addEventListener('beforeunload', beforeUnload);
    return () => window.removeEventListener('beforeunload', beforeUnload);
  }, [dirty]);
  useEffect(() => {
    if (!issue) return;
    const frame = requestAnimationFrame(() => {
      formRef.current?.querySelector<HTMLElement>(`[data-field="${issue.field}"]`)?.querySelector<HTMLElement>('input, select, textarea, [contenteditable], button')?.focus();
    });
    return () => cancelAnimationFrame(frame);
  }, [issue]);

  function close() {
    if (saving) return;
    if (dirty) setConfirmClose(true);
    else onClose();
  }
  function changeTab(tab: Tab) {
    setActiveTab(tab);
    formRef.current?.parentElement?.scrollTo({ top: 0 });
  }
  function tabKey(event: KeyboardEvent<HTMLButtonElement>, index: number) {
    const next = event.key === 'ArrowRight' ? (index + 1) % tabs.length : event.key === 'ArrowLeft' ? (index + tabs.length - 1) % tabs.length : event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : null;
    if (next === null) return;
    event.preventDefault();
    changeTab(tabs[next].id);
    document.getElementById(`blog-tab-${tabs[next].id}`)?.focus();
  }
  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const nextIssue = blogFormIssue(form);
    setIssue(nextIssue);
    if (nextIssue) { changeTab(issueTab(nextIssue.field)); return; }
    onSubmit(event);
  }
  const update = (field: TextKey, value: string) => setForm(current => ({ ...current, [field]: value }));
  const text = (field: TextKey, label: string, placeholder = '', type = 'text') => <div data-field={field}><Field label={label}><input className={inputClass} type={type} min={type === 'number' ? 1 : undefined} step={type === 'number' ? 1 : undefined} value={form[field]} placeholder={placeholder} aria-invalid={issue?.field === field || undefined} onChange={event => update(field, event.target.value)} /></Field></div>;
  const area = (field: TextKey, label: string, placeholder = '') => <div data-field={field}><Field label={label}><textarea className={textareaClass} value={form[field]} placeholder={placeholder} aria-invalid={issue?.field === field || undefined} onChange={event => update(field, event.target.value)} /></Field></div>;

  return <Modal open={open} title={form.id ? 'Редактировать статью' : 'Новая статья'} maxWidth="max-w-5xl" onClose={close}
    headerContent={<div role="tablist" aria-label="Разделы статьи" className="flex gap-1 overflow-x-auto bg-[#f5faf8] px-3 py-2 sm:px-5">
      {tabs.map((tab, index) => <button key={tab.id} id={`blog-tab-${tab.id}`} type="button" role="tab" aria-selected={activeTab === tab.id} aria-controls={`blog-panel-${tab.id}`} tabIndex={activeTab === tab.id ? 0 : -1} onKeyDown={event => tabKey(event, index)} onClick={() => changeTab(tab.id)} className={`inline-flex shrink-0 items-center gap-2 whitespace-nowrap rounded-xl px-3 py-3 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2e8175] ${activeTab === tab.id ? 'bg-[#2e8175] text-white shadow-sm' : 'text-[#526d78] hover:bg-[#e5f1ee]'}`}>
        <tab.icon className="h-4 w-4" />{tab.title}{issue && issueTab(issue.field) === tab.id && <span className="h-2 w-2 rounded-full bg-red-400" aria-label="Есть ошибка" />}
      </button>)}
    </div>}
    footer={confirmClose ? <div className="flex w-full flex-wrap items-center justify-end gap-2"><p className="mr-auto text-sm text-[#294555]">Закрыть окно без сохранения изменений?</p><Button variant="outline" onClick={() => setConfirmClose(false)}>Продолжить редактирование</Button><Button variant="danger" onClick={onClose}>Закрыть без сохранения</Button></div> : <>
      <span className="mr-auto self-center text-xs text-[#5f7580]">{dirty ? 'Есть несохранённые изменения' : 'Все вкладки сохраняются вместе'}</span>
      <Button variant="outline" disabled={saving} onClick={close}>Отмена</Button>
      <Button type="submit" form="admin-blog-form" disabled={saving}>{saving ? 'Сохранение…' : form.id ? 'Сохранить' : form.is_published ? 'Опубликовать' : 'Сохранить черновик'}</Button>
    </>}
  >
    <form ref={formRef} id="admin-blog-form" noValidate onSubmit={submit} className="min-h-[min(25rem,45dvh)] space-y-4">
      <ErrorAlert>{issue?.message || error}</ErrorAlert>
      <fieldset disabled={saving} className="min-w-0">
        <div role="tabpanel" id={`blog-panel-${activeTab}`} aria-labelledby={`blog-tab-${activeTab}`} className="space-y-5">
          {activeTab === 'main' && <>
            <Group title="Основная информация">
              {text('title', 'Заголовок *')}
              <div className="grid gap-4 sm:grid-cols-2"><div data-field="category_id"><Field label="Категория *"><select className={inputClass} value={form.category_id} onChange={event => update('category_id', event.target.value)}><option value="">Выберите категорию</option>{form.category_id && !categories.some(category => category.slug === form.category_id) && <option value={form.category_id} disabled>{form.category_id} — недоступна</option>}{categories.map(category => <option key={category.id} value={category.slug}>{category.name}</option>)}</select></Field>{categories.length === 0 && <p className="mt-2 text-xs text-[#5f7580]">Сначала добавьте категорию во вкладке «Блог → Категории».</p>}</div>{text('author_name', 'Имя автора *')}</div>
            </Group>
            <Group title="Публикация">
              {text('published_at', 'Дата публикации', '', 'datetime-local')}
              <label className="flex items-start gap-3 rounded-xl bg-[#f5faf8] p-3 text-sm text-[#294555]"><input type="checkbox" className="mt-1 accent-[#2e8175]" checked={form.is_published} onChange={event => setForm(current => ({ ...current, is_published: event.target.checked }))} /><span><strong>Опубликована</strong><span className="mt-1 block text-xs text-[#5f7580]">Выключите, чтобы сохранить статью как черновик.</span></span></label>
            </Group>
          </>}
          {activeTab === 'cover' && <Group title="Обложка статьи">
            <div data-field="image" className="space-y-4">
              <p className="text-sm text-[#5f7580]">Изображение для карточки в блоге и страницы статьи. *</p>
              <ImageUploader scope="blog" onUploaded={url => setForm(current => ({ ...current, image: url }))} />
              {form.image ? <div className="space-y-3 rounded-xl border border-[#dfece9] p-3">
                <img src={form.image} alt={form.image_alt || form.title} className="max-h-64 w-full rounded-lg bg-[#f5faf8] object-contain" />
                <div className="flex flex-wrap items-center gap-3"><p className="min-w-0 flex-1 break-all text-xs text-[#5f7580]">{form.image}</p><Button variant="outline" onClick={() => update('image', '')}>Убрать обложку</Button></div>
              </div> : <p className="rounded-xl border border-dashed border-[#cfe2de] p-8 text-center text-sm text-[#5f7580]">Обложка ещё не добавлена.</p>}
            </div>
            {text('image_alt', 'Описание изображения (alt)', 'Кратко опишите, что изображено на обложке')}
          </Group>}
          {activeTab === 'content' && <>
            <Group title="Анонс">
              {area('excerpt', 'Краткое описание *', 'Короткий анонс для карточки статьи в блоге')}
              {text('read_time', 'Время чтения, минут *', 'Например, 5', 'number')}
            </Group>
            <Group title="Текст статьи">
              <div data-field="content" className="space-y-2"><p className="text-sm font-semibold text-[#294555]">Содержание *</p><Suspense fallback={<p className="p-4 text-sm text-[#5f7580]">Загрузка редактора…</p>}><RichTextEditor label="Содержание статьи" value={form.content} disabled={saving} onChange={html => update('content', html)} /></Suspense></div>
            </Group>
          </>}
          {activeTab === 'seo' && <Group title="Поисковое оформление">
            {text('slug', 'Адрес страницы (slug)', 'Например, kak-podderzhat-immunitet')}
            <p className="text-xs text-[#5f7580]">Если оставить пустым, адрес сформируется из заголовка статьи.</p>
            {text('h1', 'H1 — заголовок на странице', 'Если отличается от заголовка статьи')}
            {text('seo_title', 'Title — заголовок в поиске')}
            {area('seo_description', 'Description — описание в поиске')}
          </Group>}
        </div>
      </fieldset>
    </form>
  </Modal>;
}

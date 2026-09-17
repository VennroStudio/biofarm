import { lazy, Suspense, useEffect, useRef, useState, type Dispatch, type FormEvent, type KeyboardEvent, type ReactNode, type SetStateAction } from 'react';
import { FileText, Share2, Info, Search } from 'lucide-react';
import type { CmsPageTemplate } from '../../../types';
import { ImageUploader } from '../../media/ui/ImageUploader';
import { Button, ErrorAlert, Field, inputClass, Modal, textareaClass } from '../../../shared/ui';
import { pageFormIssue, type PageForm, type PageFormIssue } from '../model/pageForm';

const RichTextEditor = lazy(() => import('../../../shared/ui/RichTextEditor'));
const tabs = [
  { id: 'main', title: 'Основные', icon: Info },
  { id: 'content', title: 'Содержание', icon: FileText },
  { id: 'seo', title: 'SEO', icon: Search },
  { id: 'social', title: 'Соцсети', icon: Share2 },
] as const;
type Tab = typeof tabs[number]['id'];
type TextKey = { [K in keyof PageForm]: PageForm[K] extends string ? K : never }[keyof PageForm] & string;


function Group({ title, children }: { title: string; children: ReactNode }) {
  return <section className="space-y-4 rounded-2xl border border-[#dfece9] bg-white p-4 sm:p-5"><h3 className="text-base font-semibold text-[#294555]">{title}</h3>{children}</section>;
}

type Props = {
  form: PageForm;
  templates: CmsPageTemplate[];
  open: boolean;
  error?: string | null;
  saving: boolean;
  setForm: Dispatch<SetStateAction<PageForm>>;
  onClose: () => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};

export function PageFormModal({ form, templates, open, error, saving, setForm, onClose, onSubmit }: Props) {
  const isSystem = form.page_type === 'system';
  const visibleTabs = tabs.filter(tab => !isSystem || tab.id !== 'content');
  const [activeTab, setActiveTab] = useState<Tab>('main');
  const [issue, setIssue] = useState<PageFormIssue | null>(null);
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
    const next = event.key === 'ArrowRight' ? (index + 1) % visibleTabs.length : event.key === 'ArrowLeft' ? (index + visibleTabs.length - 1) % visibleTabs.length : event.key === 'Home' ? 0 : event.key === 'End' ? visibleTabs.length - 1 : null;
    if (next === null) return;
    event.preventDefault();
    changeTab(visibleTabs[next].id);
    document.getElementById(`page-tab-${visibleTabs[next].id}`)?.focus();
  }
  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const nextIssue = pageFormIssue(form);
    setIssue(nextIssue);
    if (nextIssue) { changeTab('main'); return; }
    onSubmit(event);
  }
  const update = (field: TextKey, value: string) => setForm(current => ({ ...current, [field]: value }));
  const text = (field: TextKey, label: string, placeholder = '', type = 'text') => <div data-field={field}><Field label={label}><input className={inputClass} type={type}  step={type === 'number' ? 1 : undefined} value={form[field]} placeholder={placeholder} aria-invalid={issue?.field === field || undefined} onChange={event => update(field, event.target.value)} /></Field></div>;
  const area = (field: TextKey, label: string, placeholder = '') => <div data-field={field}><Field label={label}><textarea className={textareaClass} value={form[field]} placeholder={placeholder} aria-invalid={issue?.field === field || undefined} onChange={event => update(field, event.target.value)} /></Field></div>;

  return <Modal open={open} title={form.id ? 'Редактировать страницу' : 'Новая страница'} maxWidth="max-w-5xl" onClose={close}
    headerContent={<div role="tablist" aria-label="Разделы страницы" className="flex gap-1 overflow-x-auto bg-[#f5faf8] px-3 py-2 sm:px-5">
      {visibleTabs.map((tab, index) => <button key={tab.id} id={`page-tab-${tab.id}`} type="button" role="tab" aria-selected={activeTab === tab.id} aria-controls={`page-panel-${tab.id}`} tabIndex={activeTab === tab.id ? 0 : -1} onKeyDown={event => tabKey(event, index)} onClick={() => changeTab(tab.id)} className={`inline-flex shrink-0 items-center gap-2 whitespace-nowrap rounded-xl px-3 py-3 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2e8175] ${activeTab === tab.id ? 'bg-[#2e8175] text-white shadow-sm' : 'text-[#526d78] hover:bg-[#e5f1ee]'}`}>
        <tab.icon className="h-4 w-4" />{tab.title}{issue && tab.id === 'main' && <span className="h-2 w-2 rounded-full bg-red-400" aria-label="Есть ошибка" />}
      </button>)}
    </div>}
    footer={confirmClose ? <div className="flex w-full flex-wrap items-center justify-end gap-2"><p className="mr-auto text-sm text-[#294555]">Закрыть окно без сохранения изменений?</p><Button variant="outline" onClick={() => setConfirmClose(false)}>Продолжить редактирование</Button><Button variant="danger" onClick={onClose}>Закрыть без сохранения</Button></div> : <>
      <span className="mr-auto self-center text-xs text-[#5f7580]">{dirty ? 'Есть несохранённые изменения' : 'Все вкладки сохраняются вместе'}</span>
      <Button variant="outline" disabled={saving} onClick={close}>Отмена</Button>
      <Button type="submit" form="admin-page-form" disabled={saving}>{saving ? 'Сохранение…' : form.id ? 'Сохранить' : form.is_published ? 'Добавить' : 'Сохранить черновик'}</Button>
    </>}
  >
    <form ref={formRef} id="admin-page-form" noValidate onSubmit={submit} className="min-h-[min(25rem,45dvh)] space-y-4">
      <ErrorAlert>{issue?.message || error}</ErrorAlert>
      <fieldset disabled={saving} className="min-w-0">
        <div role="tabpanel" id={`page-panel-${activeTab}`} aria-labelledby={`page-tab-${activeTab}`} className="space-y-5">
          {activeTab === 'main' && <>
            <Group title="Основная информация">
              {text('title', 'Название *')}
              {isSystem ? <p className="rounded-xl bg-[#f5faf8] p-3 text-sm text-[#5f7580]">Системная страница. Адрес, шаблон и содержимое заданы в коде сайта; здесь доступны настройки публикации, меню и SEO.</p> : <>
                {text('slug_path', 'Адрес страницы *', 'Например, dostavka или info/sertifikaty')}
                <Field label="Шаблон"><select className={inputClass} value={form.template} onChange={event => update('template', event.target.value)}>{templates.map(template => <option key={template.key} value={template.key}>{template.label}</option>)}</select></Field>
                <p className="text-xs text-[#5f7580]">{templates.find(template => template.key === form.template)?.description}</p>
              </>}
            </Group>
            <Group title="Публикация">
              {!isSystem && text('published_at', 'Дата публикации', '', 'datetime-local')}
              <Toggle checked={form.is_published} label="Опубликована" onChange={checked => setForm(current => ({ ...current, is_published: checked }))} />
            </Group>
            <Group title="Навигация сайта">
              <div className="grid gap-3 sm:grid-cols-2">
                <Toggle checked={form.show_in_header} label="Показывать в шапке" onChange={checked => setForm(current => ({ ...current, show_in_header: checked }))} />
                <Toggle checked={form.show_in_footer} label="Показывать в футере" onChange={checked => setForm(current => ({ ...current, show_in_footer: checked }))} />
              </div>
              {text('sort_order', 'Порядок вывода', '', 'number')}
              <p className="text-xs text-[#5f7580]">Страницы с меньшим числом показываются в меню первыми.</p>
            </Group>
          </>}
          {activeTab === 'content' && !isSystem && <>
            <Group title="Вступление">{area('excerpt', 'Краткое описание', 'Короткий вводный текст страницы')}</Group>
            <Group title="Текст страницы">
              <Suspense fallback={<p className="p-4 text-sm text-[#5f7580]">Загрузка редактора…</p>}><RichTextEditor label="Содержание страницы" value={form.content} disabled={saving} onChange={html => update('content', html)} /></Suspense>
            </Group>
          </>}
          {activeTab === 'seo' && <>
            <Group title="Поисковое оформление">
              {text('h1', 'H1 — заголовок на странице', 'Если отличается от названия страницы')}
              {text('seo_title', 'Title — заголовок в поиске')}
              {area('seo_description', 'Description — описание в поиске')}
            </Group>
            <Group title="Индексация">
              <Toggle checked={form.is_indexable} label="Разрешить индексацию" onChange={checked => setForm(current => ({ ...current, is_indexable: checked }))} />
              <Toggle checked={form.is_indexable && form.show_in_sitemap} disabled={!form.is_indexable} label="Добавлять в карту сайта (sitemap)" onChange={checked => setForm(current => ({ ...current, show_in_sitemap: checked }))} />
              {!form.is_indexable && <p className="text-xs text-[#5f7580]">Страница с выключенной индексацией не включается в sitemap.</p>}
            </Group>
          </>}
          {activeTab === 'social' && <>
            <Group title="Превью ссылки">
              <p className="text-sm text-[#5f7580]">Заголовок, описание и изображение для ссылки в соцсетях и мессенджерах.</p>
              {text('og_title', 'Заголовок превью (OG title)')}
              {area('og_description', 'Описание превью (OG description)')}
            </Group>
            <Group title="Изображение превью">
              {text('og_image', 'Ссылка на изображение', 'https://… или /uploads/…')}
              <ImageUploader scope="pages" onUploaded={url => setForm(current => ({ ...current, og_image: url }))} />
              {form.og_image && <img className="max-h-64 w-full rounded-xl bg-[#f5faf8] object-contain" src={form.og_image} alt={form.og_image_alt || form.title} />}
              {text('og_image_alt', 'Описание изображения (alt)')}
            </Group>
          </>}
        </div>
      </fieldset>
    </form>
  </Modal>;
}

function Toggle({ checked, label, disabled = false, onChange }: { checked: boolean; label: string; disabled?: boolean; onChange: (checked: boolean) => void }) {
  return <label className={`flex items-center gap-3 rounded-xl bg-[#f5faf8] p-3 text-sm font-semibold text-[#294555] ${disabled ? 'opacity-50' : ''}`}><input type="checkbox" className="accent-[#2e8175]" checked={checked} disabled={disabled} onChange={event => onChange(event.target.checked)} />{label}</label>;
}

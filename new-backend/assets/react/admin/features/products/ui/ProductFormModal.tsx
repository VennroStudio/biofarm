import { MaterialSelector } from '../../material-selection/MaterialSelector';
import { lazy, Suspense, useEffect, useRef, useState, type Dispatch, type FormEvent, type KeyboardEvent, type ReactNode, type SetStateAction } from 'react';
import { ArrowDown, ArrowUp, FileText, Images, Info, Link2, Search, Settings2, ShoppingBag, X } from 'lucide-react';
import { ImageUploader } from '../../media/ui/ImageUploader';
import { Button, ErrorAlert, Field, inputClass, Modal, textareaClass } from '../../../shared/ui';
import type { BlogPost, Category, ProductAttribute, ProductGroup } from '../../../types';
import { productFormIssue, setMainImage, type ProductForm, type ProductFormIssue, type ProductImageForm } from '../model/productForm';

const RichTextEditor = lazy(() => import('../../../shared/ui/RichTextEditor'));
const tabs = [
  { id: 'main', title: 'Основные', icon: Info },
  { id: 'photos', title: 'Фотографии', icon: Images },
  { id: 'about', title: 'О товаре', icon: FileText },
  { id: 'attributes', title: 'Атрибуты', icon: Settings2 },
  { id: 'related', title: 'Связанные данные', icon: Link2 },
  { id: 'marketplaces', title: 'Маркетплейсы', icon: ShoppingBag },
  { id: 'seo', title: 'SEO', icon: Search },
] as const;
type Tab = typeof tabs[number]['id'];
const issueTab = (field: ProductFormIssue['field']): Tab => field === 'description' ? 'about' : field === 'images' ? 'photos' : field === 'wb_link' || field === 'ozon_link' ? 'marketplaces' : 'main';
const toggleId = (ids: number[], id: number) => ids.includes(id) ? ids.filter(item => item !== id) : [...ids, id];
type TextKey = { [K in keyof ProductForm]: ProductForm[K] extends string ? K : never }[keyof ProductForm] & string;
function Group({ title, children }: { title: string; children: ReactNode }) {
  return <section className="space-y-4 rounded-2xl border border-[#dfece9] bg-white p-4 sm:p-5"><h3 className="text-base font-semibold text-[#294555]">{title}</h3>{children}</section>;
}

type Props = {
  categories: Category[];
  attributes: ProductAttribute[];
  productGroups: ProductGroup[];
  blogPosts: BlogPost[];
  form: ProductForm;
  open: boolean;
  error?: string | null;
  saving: boolean;
  setForm: Dispatch<SetStateAction<ProductForm>>;
  onAddImage: (url: string) => void;
  onClose: () => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};


export function ProductFormModal({ categories, attributes, productGroups, blogPosts, form, open, error, saving, setForm, onAddImage, onClose, onSubmit }: Props) {
  const [activeTab, setActiveTab] = useState<Tab>('main');
  const [issue, setIssue] = useState<ProductFormIssue | null>(null);
  const [confirmClose, setConfirmClose] = useState(false);
  const [attributeSearch, setAttributeSearch] = useState('');
  const [articleSearch, setArticleSearch] = useState('');
  const [initialForm] = useState(() => JSON.stringify(form));
  const bodyRef = useRef<HTMLFormElement>(null);
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
      const field = bodyRef.current?.querySelector<HTMLElement>(`[data-field="${issue.field}"]`);
      field?.querySelector<HTMLElement>('input, select, textarea, [contenteditable], button')?.focus();
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
    const body = bodyRef.current?.parentElement;
    body?.scrollTo({ top: 0 });
  }
  function tabKey(event: KeyboardEvent<HTMLButtonElement>, index: number) {
    const next = event.key === 'ArrowRight' ? (index + 1) % tabs.length : event.key === 'ArrowLeft' ? (index + tabs.length - 1) % tabs.length : event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : null;
    if (next === null) return;
    event.preventDefault();
    changeTab(tabs[next].id);
    document.getElementById(`product-tab-${tabs[next].id}`)?.focus();
  }
  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const nextIssue = productFormIssue(form);
    setIssue(nextIssue);
    if (nextIssue) { changeTab(issueTab(nextIssue.field)); return; }
    onSubmit(event);
  }
  const update = (field: TextKey, value: string) => setForm(current => ({ ...current, [field]: value }));
  const text = (field: TextKey, label: string, placeholder = '', type = 'text') => <div data-field={field}>
    <Field label={label}><input className={inputClass} type={type} min={type === 'number' ? 0 : undefined} step={type === 'number' ? 1 : undefined} value={form[field]} placeholder={placeholder} aria-invalid={issue?.field === field || undefined} onChange={event => update(field, event.target.value)} /></Field>
  </div>;
  const area = (field: TextKey, label: string, placeholder = '') => <Field label={label}><textarea className={textareaClass} value={form[field]} placeholder={placeholder} onChange={event => update(field, event.target.value)} /></Field>;
  const matches = (value: string, search: string) => value.toLocaleLowerCase('ru').includes(search.trim().toLocaleLowerCase('ru'));
  const selectedArticles = blogPosts.filter(post => form.related_blog_post_ids.includes(post.id));
  const visibleAttributes = attributes.filter(attribute => attribute.values.length > 0 && (matches(attribute.name, attributeSearch) || attribute.values.some(value => matches(value.name, attributeSearch))));
  const updateImage = (index: number, updates: Partial<ProductImageForm>) => {
    setForm((current) => {
      const imageItems = current.image_items.map((item, itemIndex) => (
        itemIndex === index ? { ...item, ...updates } : item
      ));
      const mainImage = imageItems.find((item) => item.is_main) ?? imageItems[0];

      return {
        ...current,
        image_items: imageItems,
        image: mainImage?.path ?? '',
        image_alt: mainImage?.alt ?? '',
      };
    });
  };
  const removeImage = (index: number) => {
    setForm((current) => {
      const imageItems = current.image_items.filter((_, itemIndex) => itemIndex !== index)
        .map((item, itemIndex) => ({ ...item, sort_order: itemIndex }));
      const normalized = imageItems.length > 0 && !imageItems.some((item) => item.is_main)
        ? setMainImage(imageItems, 0)
        : imageItems;
      const mainImage = normalized.find((item) => item.is_main) ?? normalized[0];

      return {
        ...current,
        image_items: normalized,
        image: mainImage?.path ?? '',
        image_alt: mainImage?.alt ?? '',
      };
    });
  };
  const markMainImage = (index: number) => {
    setForm((current) => {
      const imageItems = setMainImage(current.image_items, index);
      const mainImage = imageItems[index];

      return {
        ...current,
        image_items: imageItems,
        image: mainImage?.path ?? '',
        image_alt: mainImage?.alt ?? '',
      };
    });
  };

  function moveImage(index: number, direction: number) {
    setForm(current => {
      const items = [...current.image_items];
      const next = index + direction;
      if (!items[next]) return current;
      [items[index], items[next]] = [items[next], items[index]];
      return { ...current, image_items: items.map((item, position) => ({ ...item, sort_order: position })) };
    });
  }
  const selectedChip = (title: string, remove: () => void) => <button type="button" key={title} onClick={remove} className="inline-flex max-w-full items-center gap-2 rounded-lg bg-[#eaf5f1] px-3 py-2 text-left text-xs text-[#18574f]" aria-label={`Убрать: ${title}`}><span className="truncate">{title}</span><X className="h-3 w-3 shrink-0" /></button>;

  return <Modal open={open} title={form.id ? 'Редактировать товар' : 'Новый товар'} description={form.id ? form.name : 'Заполните данные товара по разделам'} maxWidth="max-w-5xl" onClose={close}
    headerContent={<div role="tablist" aria-label="Разделы товара" className="flex gap-1 overflow-x-auto bg-[#f5faf8] px-3 py-2 sm:px-5">
      {tabs.map((tab, index) => <button key={tab.id} id={`product-tab-${tab.id}`} type="button" role="tab" aria-selected={activeTab === tab.id} aria-controls={`product-panel-${tab.id}`} tabIndex={activeTab === tab.id ? 0 : -1} onKeyDown={event => tabKey(event, index)} onClick={() => changeTab(tab.id)} className={`inline-flex shrink-0 items-center gap-2 whitespace-nowrap rounded-xl px-3 py-3 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2e8175] ${activeTab === tab.id ? 'bg-[#2e8175] text-white shadow-sm' : 'text-[#526d78] hover:bg-[#e5f1ee]'}`}>
        <tab.icon className="h-4 w-4" />{tab.title}{issue && issueTab(issue.field) === tab.id && <span className="h-2 w-2 rounded-full bg-red-400" aria-label="Есть ошибка" />}
      </button>)}
    </div>}
    footer={confirmClose ? <div className="flex w-full flex-wrap items-center justify-end gap-2"><p className="mr-auto text-sm text-[#294555]">Закрыть окно без сохранения изменений?</p><Button variant="outline" onClick={() => setConfirmClose(false)}>Продолжить редактирование</Button><Button variant="danger" onClick={onClose}>Закрыть без сохранения</Button></div> : <>
      <span className="mr-auto self-center text-xs text-[#5f7580]">{dirty ? 'Есть несохранённые изменения' : 'Все вкладки сохраняются вместе'}</span>
      <Button variant="outline" disabled={saving} onClick={close}>Отмена</Button>
      <Button type="submit" form="admin-product-form" disabled={saving}>{saving ? 'Сохранение…' : form.id ? 'Сохранить' : 'Добавить'}</Button>
    </>}
  >
    <form ref={bodyRef} id="admin-product-form" noValidate onSubmit={submit} className="min-h-[min(25rem,45dvh)] space-y-4">
      <ErrorAlert>{issue?.message || error}</ErrorAlert>
      <fieldset disabled={saving} className="min-w-0">
        <div role="tabpanel" id={`product-panel-${activeTab}`} aria-labelledby={`product-tab-${activeTab}`} className="space-y-5">
          {activeTab === 'main' && <>
            <Group title="Основная информация">
              {text('name', 'Название *')}
              <div className="grid gap-4 sm:grid-cols-2">
                <div data-field="category_id"><Field label="Категория *"><select className={inputClass} value={form.category_id} onChange={event => update('category_id', event.target.value)}><option value="">Выберите категорию</option>{categories.map(category => <option key={category.id} value={category.id}>{category.name}</option>)}</select></Field></div>
                <Field label="Группа товара"><select className={inputClass} value={form.product_group_id} onChange={event => update('product_group_id', event.target.value)}><option value="">Без группы</option>{productGroups.map(group => <option key={group.id} value={group.id}>{group.name}</option>)}</select></Field>
              </div>
            </Group>
            <Group title="Цена и публикация">
              <div className="grid gap-4 sm:grid-cols-2">
                {text('price', 'Цена, ₽ *', '', 'number')}{text('old_price', 'Старая цена, ₽', '', 'number')}
                {text('weight', 'Вес/Объём *', 'Например, 130 г или 60 капсул')}
                {text('sku', 'Артикул (SKU)', 'Внутренний артикул товара')}
                <Field label="Наличие"><select className={inputClass} value={form.availability} onChange={event => update('availability', event.target.value)}><option value="in_stock">В наличии</option><option value="out_of_stock">Нет в наличии</option><option value="preorder">Предзаказ</option></select></Field>
                {text('badge', 'Бейдж', 'Например, Хит')}
              </div>
              <label className="flex items-start gap-3 rounded-xl bg-[#f5faf8] p-3 text-sm text-[#294555]"><input type="checkbox" className="mt-1 accent-[#2e8175]" checked={form.is_active} onChange={event => setForm(current => ({ ...current, is_active: event.target.checked }))} /><span><strong>Активен</strong><span className="mt-1 block text-xs text-[#5f7580]">Товар опубликован на сайте. Наличие задаётся отдельно.</span></span></label>
            </Group>
          </>}
          {activeTab === 'about' && <>
            <Group title="Описание">
              {area('short_description', 'Краткое описание', 'Коротко о товаре для карточки в каталоге')}
              <div data-field="description" className="space-y-2"><p className="text-sm font-semibold text-[#294555]">Полное описание *</p><Suspense fallback={<p className="p-4 text-sm text-[#5f7580]">Загрузка редактора…</p>}><RichTextEditor value={form.description} disabled={saving} onChange={html => update('description', html)} /></Suspense></div>
              {area('features', 'Особенности', 'Каждая особенность — с новой строки')}
            </Group>
            <Group title="Состав и активные компоненты"><div className="grid gap-4 sm:grid-cols-2">{area('ingredients', 'Состав')}{area('active_components_text', 'Активные компоненты')}</div></Group>
            <Group title="Применение и противопоказания"><div className="grid gap-4 sm:grid-cols-2">{area('usage_text', 'Способ применения')}{area('contraindications', 'Противопоказания')}</div></Group>
            <Group title="Производство и хранение"><div className="grid gap-4 sm:grid-cols-2">{text('country', 'Страна производства')}{text('shelf_life', 'Срок годности')}</div>{area('storage_conditions', 'Условия хранения')}</Group>
            <Group title="Предупреждение о БАД">{area('bad_disclaimer', 'Текст предупреждения')}</Group>
          </>}
          {activeTab === 'photos' && <div data-field="images" className="space-y-4">
            <p className="text-sm text-[#5f7580]">Выберите главное фото для каталога. Стрелками меняйте порядок фотографий в галерее.</p>
        <div className="space-y-2">
          <p className="text-sm font-semibold text-[#294555]">Фотографии товара *</p>
          <div className="flex flex-wrap gap-2">
            <ImageUploader scope="products" onUploaded={onAddImage} />
          </div>
          {form.image_items.length > 0 && (
            <div className="mt-3 space-y-3">
              {form.image_items.map((image, index) => (
                <div key={`${image.path}-${index}`} className="grid gap-3 rounded-lg border border-[#dfece9] bg-white p-3 md:grid-cols-[120px_1fr]">
                  <img src={image.path} alt={image.alt} className="h-28 w-28 rounded-xl bg-[#f5faf8] object-contain" />
                  <div className="grid gap-3">
                    <div className="grid gap-3 md:grid-cols-[1fr_auto]">
                      <p className="min-h-10 break-all rounded-md border border-[#dfece9] bg-[#f5faf8] px-3 py-2 text-xs font-semibold text-[#5f7580]">
                        {image.path}
                      </p>
                      <label className="flex items-center gap-2 whitespace-nowrap text-sm font-semibold text-[#294555]">
                        <input
                          type="radio"
                          name="main-product-image"
                          checked={image.is_main}
                          onChange={() => markMainImage(index)}
                        />
                        Главное
                      </label>
                    </div>
                    <div className="grid gap-3 md:grid-cols-2">
                      <input
                        className={inputClass}
                        value={image.alt}
                        onChange={(event) => updateImage(index, { alt: event.target.value })}
                        aria-label={`Описание фотографии ${index + 1}`}
                        placeholder="Описание изображения (alt)"
                      />
                      <input
                        className={inputClass}
                        value={image.title}
                        onChange={(event) => updateImage(index, { title: event.target.value })}
                        aria-label={`Подпись фотографии ${index + 1}`}
                        placeholder="Подпись изображения"
                      />
                    </div>
                    <div className="flex flex-wrap justify-end gap-2">
                      <Button size="sm" variant="outline" aria-label={`Переместить фото ${index + 1} выше`} disabled={index === 0} onClick={() => moveImage(index, -1)}><ArrowUp className="h-4 w-4" /></Button>
                      <Button size="sm" variant="outline" aria-label={`Переместить фото ${index + 1} ниже`} disabled={index === form.image_items.length - 1} onClick={() => moveImage(index, 1)}><ArrowDown className="h-4 w-4" /></Button>
                      <Button type="button" variant="danger" onClick={() => removeImage(index)}>
                        Удалить фото
                      </Button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

            {form.image_items.length === 0 && <p className="rounded-xl border border-dashed border-[#cfe2de] p-8 text-center text-sm text-[#5f7580]">Фотографии ещё не добавлены.</p>}
          </div>}
          {activeTab === 'attributes' && <>
            <Field label="Поиск атрибутов"><input type="search" className={inputClass} placeholder="Название или значение атрибута" value={attributeSearch} onChange={event => setAttributeSearch(event.target.value)} /></Field>
            <p className="text-sm text-[#5f7580]">Выбрано значений: {form.attribute_value_ids.length}</p>
            {visibleAttributes.map(attribute => <Group key={attribute.id} title={attribute.name}><div className="grid gap-2 sm:grid-cols-2">{attribute.values.filter(value => matches(attribute.name, attributeSearch) || matches(value.name, attributeSearch)).map(value => <label key={value.id} className={`flex items-center gap-3 rounded-xl border p-3 text-sm ${form.attribute_value_ids.includes(value.id) ? 'border-[#2e8175]/40 bg-[#eaf5f1] text-[#18574f]' : 'border-[#dfece9] text-[#294555]'}`}><input type="checkbox" className="accent-[#2e8175]" checked={form.attribute_value_ids.includes(value.id)} onChange={() => setForm(current => ({ ...current, attribute_value_ids: toggleId(current.attribute_value_ids, value.id) }))} />{value.name}</label>)}</div></Group>)}
            {visibleAttributes.length === 0 && <p className="py-8 text-center text-sm text-[#5f7580]">{attributes.length ? 'Ничего не найдено.' : 'Сначала добавьте атрибуты и их значения в разделе «Магазин → Атрибуты».'}</p>}
          </>}
          {activeTab === 'related' && <>
            <Group title="Статьи">
              <Field label="Поиск статей"><input className={inputClass} type="search" value={articleSearch} onChange={event => setArticleSearch(event.target.value)} placeholder="Название статьи" /></Field>
              <div className="flex flex-wrap gap-2">{selectedArticles.map(post => selectedChip(post.title, () => setForm(current => ({ ...current, related_blog_post_ids: toggleId(current.related_blog_post_ids, post.id) }))))}</div>
              <div className="max-h-60 space-y-2 overflow-y-auto">{blogPosts.filter(post => matches(post.title, articleSearch)).map(post => <label key={post.id} className="flex items-start gap-3 rounded-xl border border-[#dfece9] p-3 text-sm text-[#294555]"><input className="mt-1 accent-[#2e8175]" type="checkbox" checked={form.related_blog_post_ids.includes(post.id)} onChange={() => setForm(current => ({ ...current, related_blog_post_ids: toggleId(current.related_blog_post_ids, post.id) }))} /><span>{post.title}<span className="block text-xs text-[#5f7580]">{post.is_published ? 'Опубликована' : 'Черновик'}</span></span></label>)}</div>
              {!blogPosts.some(post => matches(post.title, articleSearch)) && <p className="text-sm text-[#5f7580]">{blogPosts.length ? 'Статьи не найдены.' : 'Статей пока нет.'}</p>}
            </Group>
            <MaterialSelector kind="certificate" ids={form.certificate_ids} onChange={ids => setForm(current => ({ ...current, certificate_ids: ids }))} />
            <MaterialSelector kind="faq" ids={form.faq_ids} onChange={ids => setForm(current => ({ ...current, faq_ids: ids }))} />
          </>}
          {activeTab === 'seo' && <Group title="Поисковое оформление">
            {text('slug', 'Адрес страницы (slug)', 'Например, ekstrakt-kory-osiny')}
            <p className="text-xs text-[#5f7580]">Если оставить пустым, адрес сформируется из названия товара.</p>
            {text('h1', 'H1 — заголовок на странице', 'Если отличается от названия товара')}
            {text('seo_title', 'Title — заголовок в поиске')}
            {area('seo_description', 'Description — описание в поиске')}
          </Group>}
          {activeTab === 'marketplaces' && <Group title="Ссылки на товар">
            <p className="text-sm text-[#5f7580]">Укажите страницы этого товара на маркетплейсах.</p>
            {text('wb_link', 'Ссылка Wildberries', 'https://www.wildberries.ru/…', 'url')}
            {text('ozon_link', 'Ссылка Ozon', 'https://www.ozon.ru/…', 'url')}
          </Group>}
        </div>
      </fieldset>
    </form>
  </Modal>;
}

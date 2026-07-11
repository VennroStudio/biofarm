import { Edit, Image as ImageIcon, Plus, Save, Star, Trash2, X } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import { productsApi, reviewsApi } from '../api/resources';
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
import type { Product, Review } from '../types';

type ReviewForm = {
  id?: string;
  product_id: string;
  user_name: string;
  rating: number;
  text: string;
  source: string;
  user_id: string;
  images: string[];
  is_approved: boolean;
};

const emptyForm: ReviewForm = {
  product_id: '',
  user_name: '',
  rating: 5,
  text: '',
  source: 'site',
  user_id: '',
  images: [],
  is_approved: true,
};

const sourceLabels: Record<string, string> = {
  site: 'Сайт',
  wildberries: 'WB',
  wb: 'WB',
  ozon: 'Ozon',
};

function formFromReview(review: Review): ReviewForm {
  return {
    id: review.id,
    product_id: String(review.product_id),
    user_name: review.user_name,
    rating: review.rating,
    text: review.text,
    source: review.source || 'site',
    user_id: review.user_id ?? '',
    images: review.images ?? [],
    is_approved: review.is_approved,
  };
}

function Stars({ rating }: { rating: number }) {
  return (
    <div className="flex gap-0.5">
      {[1, 2, 3, 4, 5].map((star) => (
        <Star key={star} className={`h-4 w-4 ${star <= rating ? 'fill-[#e5a11a] text-[#e5a11a]' : 'text-[#d9dece]'}`} />
      ))}
    </div>
  );
}

export function AdminReviews() {
  const [reviews, setReviews] = useState<Review[]>([]);
  const [products, setProducts] = useState<Product[]>([]);
  const [form, setForm] = useState<ReviewForm>(emptyForm);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [imageUrl, setImageUrl] = useState('');
  const [saving, setSaving] = useState(false);

  const productById = useMemo(() => new Map(products.map((product) => [product.id, product.name])), [products]);

  async function load() {
    const [reviewResult, productResult] = await Promise.all([reviewsApi.list(), productsApi.list()]);
    setReviews(reviewResult.items);
    setProducts(productResult.items);
  }

  useLoadOnMount(load);

  function openCreate() {
    setForm({ ...emptyForm, product_id: String(products[0]?.id ?? '') });
    setImageUrl('');
    setDialogOpen(true);
  }

  function openEdit(review: Review) {
    setForm(formFromReview(review));
    setImageUrl('');
    setDialogOpen(true);
  }

  function addImage(url: string) {
    setForm((current) => ({
      ...current,
      images: current.images.includes(url) ? current.images : [...current.images, url],
    }));
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    try {
      const payload = {
        productId: Number(form.product_id),
        userName: form.user_name,
        rating: form.rating,
        text: form.text,
        source: form.source,
        userId: form.user_id || null,
        images: form.images,
        isApproved: form.is_approved,
      };
      if (form.id) {
        await reviewsApi.update(form.id, payload);
      } else {
        await reviewsApi.create(payload);
      }
      setDialogOpen(false);
      await load();
    } finally {
      setSaving(false);
    }
  }

  async function approve(review: Review) {
    await reviewsApi.approve(review.id);
    await load();
  }

  async function remove(review: Review) {
    if (!confirm(`Удалить отзыв "${review.user_name}"?`)) {
      return;
    }
    await reviewsApi.delete(review.id);
    await load();
  }

  return (
    <>
      <PageHeader
        title="Отзывы"
        subtitle={`Всего отзывов: ${reviews.length}`}
        actions={<Button onClick={openCreate}><Plus className="h-4 w-4" />Добавить отзыв</Button>}
      />

      <Card className="p-0">
        {reviews.length === 0 ? (
          <div className="p-6">
            <EmptyState>Отзывы пока не добавлены</EmptyState>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <AdminTable>
              <TableHead>
                <tr>
                  <TableHeaderCell>Автор</TableHeaderCell>
                  <TableHeaderCell>Товар</TableHeaderCell>
                  <TableHeaderCell>Рейтинг</TableHeaderCell>
                  <TableHeaderCell>Источник</TableHeaderCell>
                  <TableHeaderCell>Фото</TableHeaderCell>
                  <TableHeaderCell className="text-right">Действия</TableHeaderCell>
                </tr>
              </TableHead>
              <tbody>
                {reviews.map((review) => (
                  <TableRow key={review.id}>
                    <TableCell className="font-semibold">{review.user_name}</TableCell>
                    <TableCell>{productById.get(review.product_id) || `ID: ${review.product_id}`}</TableCell>
                    <TableCell><Stars rating={review.rating} /></TableCell>
                    <TableCell><Badge tone="gray">{sourceLabels[review.source] ?? review.source}</Badge></TableCell>
                    <TableCell>
                      {review.images && review.images.length > 0 ? (
                        <div className="flex gap-1">
                          {review.images.slice(0, 3).map((image, index) => (
                            <a key={`${image}-${index}`} href={image} target="_blank" rel="noreferrer">
                              <img src={image} alt="" className="h-8 w-8 rounded object-cover" />
                            </a>
                          ))}
                        </div>
                      ) : (
                        <span className="text-[#789083]">—</span>
                      )}
                    </TableCell>
                    <TableCell>
                      <div className="flex justify-end gap-2">
                        {!review.is_approved && (
                          <Button variant="secondary" size="sm" onClick={() => void approve(review)}>Одобрить</Button>
                        )}
                        <Button variant="ghost" size="icon" onClick={() => openEdit(review)} title="Изменить">
                          <Edit className="h-4 w-4" />
                        </Button>
                        <Button variant="ghost" size="icon" className="text-[#ef4444]" onClick={() => void remove(review)} title="Удалить">
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
        title={form.id ? 'Редактировать отзыв' : 'Добавить отзыв'}
        description={form.id ? 'Измените данные отзыва' : 'Создайте отзыв вручную'}
        maxWidth="max-w-lg"
        onClose={() => setDialogOpen(false)}
        footer={(
          <>
            <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>Отмена</Button>
            <Button type="submit" form="admin-review-form" disabled={saving || !form.product_id || !form.user_name || !form.text}>
              {form.id ? <><Save className="h-4 w-4" />Сохранить</> : 'Добавить'}
            </Button>
          </>
        )}
      >
        <form id="admin-review-form" className="grid gap-4" onSubmit={(event) => void submit(event)}>
          <Field label="Товар *">
            <select className={inputClass} value={form.product_id} onChange={(event) => setForm({ ...form, product_id: event.target.value })}>
              <option value="" disabled>Выберите товар</option>
              {products.map((product) => <option key={product.id} value={product.id}>{product.name}</option>)}
            </select>
          </Field>
          <Field label="Имя автора *">
            <input className={inputClass} value={form.user_name} onChange={(event) => setForm({ ...form, user_name: event.target.value })} />
          </Field>
          <div className="space-y-2">
            <p className="text-sm font-semibold text-[#26382d]">Рейтинг</p>
            <div className="flex gap-1">
              {[1, 2, 3, 4, 5].map((star) => (
                <button key={star} type="button" className="p-1" onClick={() => setForm({ ...form, rating: star })}>
                  <Star className={`h-6 w-6 ${star <= form.rating ? 'fill-[#e5a11a] text-[#e5a11a]' : 'text-[#d9dece]'}`} />
                </button>
              ))}
            </div>
          </div>
          <Field label="Текст отзыва *">
            <textarea className={textareaClass} value={form.text} onChange={(event) => setForm({ ...form, text: event.target.value })} />
          </Field>
          <Field label="Источник">
            <select className={inputClass} value={form.source} onChange={(event) => setForm({ ...form, source: event.target.value })}>
              <option value="site">Сайт</option>
              <option value="wildberries">Wildberries</option>
              <option value="ozon">Ozon</option>
            </select>
          </Field>
          <div className="space-y-2">
            <p className="text-sm font-semibold text-[#26382d]">Фотографии</p>
            <div className="flex flex-wrap gap-2">
              <input className={`${inputClass} flex-1`} value={imageUrl} onChange={(event) => setImageUrl(event.target.value)} placeholder="URL изображения" />
              <Button
                type="button"
                variant="outline"
                onClick={() => {
                  if (imageUrl.trim()) {
                    addImage(imageUrl.trim());
                    setImageUrl('');
                  }
                }}
              >
                <ImageIcon className="h-4 w-4" />
              </Button>
              <ImageUploader scope="reviews" onUploaded={addImage} />
            </div>
            {form.images.length > 0 && (
              <div className="mt-2 flex flex-wrap gap-2">
                {form.images.map((image, index) => (
                  <div key={`${image}-${index}`} className="relative">
                    <img src={image} alt="" className="h-16 w-16 rounded object-cover" />
                    <button
                      type="button"
                      className="absolute -right-2 -top-2 rounded-full bg-[#b94b4b] p-1 text-white"
                      onClick={() => setForm({ ...form, images: form.images.filter((_, itemIndex) => itemIndex !== index) })}
                    >
                      <X className="h-3 w-3" />
                    </button>
                  </div>
                ))}
              </div>
            )}
          </div>
          <label className="flex items-center gap-2 text-sm font-semibold text-[#26382d]">
            <input type="checkbox" checked={form.is_approved} onChange={(event) => setForm({ ...form, is_approved: event.target.checked })} />
            Одобрен
          </label>
        </form>
      </Modal>
    </>
  );
}

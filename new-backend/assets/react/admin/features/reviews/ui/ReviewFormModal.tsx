import { Save, Star, X } from 'lucide-react';
import type { Dispatch, FormEvent, SetStateAction } from 'react';
import { ImageUploader } from '../../media/ui/ImageUploader';
import { Button, Field, inputClass, Modal, textareaClass } from '../../../shared/ui';
import type { Product } from '../../../types';
import type { ReviewForm } from '../model/reviewForm';

type Props = {
  form: ReviewForm;
  open: boolean;
  products: Product[];
  saving: boolean;
  setForm: Dispatch<SetStateAction<ReviewForm>>;
  onAddImage: (url: string) => void;
  onClose: () => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};

export function ReviewFormModal({
  form,
  open,
  products,
  saving,
  setForm,
  onAddImage,
  onClose,
  onSubmit,
}: Props) {
  return (
    <Modal
      open={open}
      title={form.id ? 'Редактировать отзыв' : 'Добавить отзыв'}
      description={form.id ? 'Измените данные отзыва' : 'Создайте отзыв вручную'}
      maxWidth="max-w-lg"
      onClose={onClose}
      footer={(
        <>
          <Button type="button" variant="outline" onClick={onClose}>Отмена</Button>
          <Button type="submit" form="admin-review-form" disabled={saving || !form.product_id || !form.user_name || !form.text}>
            {form.id ? <><Save className="h-4 w-4" />Сохранить</> : 'Добавить'}
          </Button>
        </>
      )}
    >
      <form id="admin-review-form" className="grid gap-4" onSubmit={onSubmit}>
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
            <ImageUploader scope="reviews" onUploaded={onAddImage} />
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
  );
}

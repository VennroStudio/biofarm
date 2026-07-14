import { Plus } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import { productsApi, reviewsApi } from '../api/resources';
import {
  emptyReviewForm,
  reviewFormFromReview,
  reviewPayloadFromForm,
  type ReviewForm,
} from '../features/reviews/model/reviewForm';
import { ReviewFormModal } from '../features/reviews/ui/ReviewFormModal';
import { ReviewsTable } from '../features/reviews/ui/ReviewsTable';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { Button, Card, PageHeader } from '../shared/ui';
import type { Product, Review } from '../types';

export function AdminReviews() {
  const [reviews, setReviews] = useState<Review[]>([]);
  const [products, setProducts] = useState<Product[]>([]);
  const [form, setForm] = useState<ReviewForm>(emptyReviewForm);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [saving, setSaving] = useState(false);

  const productById = useMemo(() => new Map(products.map((product) => [product.id, product.name])), [products]);

  async function load() {
    const [reviewResult, productResult] = await Promise.all([reviewsApi.list(), productsApi.list()]);
    setReviews(reviewResult.items);
    setProducts(productResult.items);
  }

  useLoadOnMount(load);

  function openCreate() {
    setForm({ ...emptyReviewForm, product_id: String(products[0]?.id ?? '') });
    setDialogOpen(true);
  }

  function openEdit(review: Review) {
    setForm(reviewFormFromReview(review));
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
      if (form.id) {
        await reviewsApi.update(form.id, reviewPayloadFromForm(form));
      } else {
        await reviewsApi.create(reviewPayloadFromForm(form));
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
        <ReviewsTable
          productById={productById}
          reviews={reviews}
          onApprove={(review) => void approve(review)}
          onEdit={openEdit}
          onRemove={(review) => void remove(review)}
        />
      </Card>

      <ReviewFormModal
        form={form}
        open={dialogOpen}
        products={products}
        saving={saving}
        setForm={setForm}
        onAddImage={addImage}
        onClose={() => setDialogOpen(false)}
        onSubmit={(event) => void submit(event)}
      />
    </>
  );
}

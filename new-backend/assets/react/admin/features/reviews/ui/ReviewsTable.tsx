import { Edit, Trash2 } from 'lucide-react';
import {
  AdminTable,
  Badge,
  Button,
  EmptyState,
  TableCell,
  TableHead,
  TableHeaderCell,
  TableRow,
} from '../../../shared/ui';
import type { Review } from '../../../types';
import { reviewSourceLabels } from '../model/reviewForm';
import { RatingStars } from './RatingStars';

type Props = {
  productById: Map<number, string>;
  reviews: Review[];
  onApprove: (review: Review) => void;
  onEdit: (review: Review) => void;
  onRemove: (review: Review) => void;
};

export function ReviewsTable({ productById, reviews, onApprove, onEdit, onRemove }: Props) {
  if (reviews.length === 0) {
    return (
      <div className="p-6">
        <EmptyState>Отзывы пока не добавлены</EmptyState>
      </div>
    );
  }

  return (
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
              <TableCell><RatingStars rating={review.rating} /></TableCell>
              <TableCell><Badge tone="gray">{reviewSourceLabels[review.source] ?? review.source}</Badge></TableCell>
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
                    <Button variant="secondary" size="sm" onClick={() => onApprove(review)}>Одобрить</Button>
                  )}
                  <Button variant="ghost" size="icon" onClick={() => onEdit(review)} title="Изменить">
                    <Edit className="h-4 w-4" />
                  </Button>
                  <Button variant="ghost" size="icon" className="text-[#ef4444]" onClick={() => onRemove(review)} title="Удалить">
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
              </TableCell>
            </TableRow>
          ))}
        </tbody>
      </AdminTable>
    </div>
  );
}

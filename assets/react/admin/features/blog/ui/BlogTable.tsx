import { Edit, Eye, Trash2 } from 'lucide-react';
import { formatDate } from '../../../shared/lib';
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
import type { BlogPost, BlogCategory } from '../../../types';
import { ImagePreview } from './ImagePreview';

type Props = {
  posts: BlogPost[];
  categories: BlogCategory[];
  onEdit: (post: BlogPost) => void;
  onRemove: (post: BlogPost) => void;
};

export function BlogTable({ posts, categories, onEdit, onRemove }: Props) {
  if (posts.length === 0) {
    return <EmptyState>Статьи не найдены</EmptyState>;
  }

  return (
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
          {posts.map((post) => (
            <TableRow key={post.id} className={!post.is_published ? 'opacity-60' : ''}>
              <TableCell>
                <ImagePreview src={post.image} title={post.title} />
              </TableCell>
              <TableCell className="min-w-[420px]">
                <p className="line-clamp-1 font-semibold text-[#294555]">{post.title}</p>
                <p className="line-clamp-1 text-sm text-[#5f7580]">{post.excerpt}</p>
              </TableCell>
              <TableCell><Badge tone="gray">{categories.find(category => category.slug === post.category_id)?.name ?? post.category_id}</Badge></TableCell>
              <TableCell>{post.author_name}</TableCell>
              <TableCell>{formatDate(post.published_at || post.created_at)}</TableCell>
              <TableCell>
                <div className="flex justify-end gap-2">
                  <a
                    href={`/blog/${post.slug}`}
                    target="_blank"
                    rel="noreferrer"
                    className="grid h-9 w-9 place-items-center rounded-md text-[#526d78] transition hover:bg-[#eaf5f1]"
                    title="Открыть"
                  >
                    <Eye className="h-4 w-4" />
                  </a>
                  <Button variant="ghost" size="icon" onClick={() => onEdit(post)} title="Изменить">
                    <Edit className="h-4 w-4" />
                  </Button>
                  <Button variant="ghost" size="icon" className="text-[#ef4444]" onClick={() => onRemove(post)} title="Удалить">
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

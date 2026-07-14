import { Edit2, ExternalLink, Trash2 } from 'lucide-react';
import { AdminTable, Badge, Button, EmptyState, TableCell, TableHead, TableHeaderCell, TableRow } from '../../../shared/ui';
import type { CmsPage } from '../../../types';

const systemPagePaths: Record<string, string> = {
  home: '/',
  catalog: '/catalog',
  blog: '/blog',
  privacy: '/privacy',
  oferta: '/oferta',
  cart: '/cart',
  checkout: '/checkout',
  order_success: '/order-success',
  login: '/login',
  profile: '/profile',
};

const systemPageLabels: Record<string, string> = {
  home: 'Главная',
  catalog: 'Каталог',
  blog: 'Блог',
  privacy: 'Политика',
  oferta: 'Оферта',
  cart: 'Корзина',
  checkout: 'Оформление',
  order_success: 'Успешный заказ',
  login: 'Вход',
  profile: 'Профиль',
};

type Props = {
  pages: CmsPage[];
  onEdit: (page: CmsPage) => void;
  onRemove: (page: CmsPage) => void;
};

export function PagesTable({ pages, onEdit, onRemove }: Props) {
  if (pages.length === 0) {
    return <EmptyState>Страницы пока не добавлены</EmptyState>;
  }

  return (
    <div className="overflow-x-auto">
      <AdminTable>
        <TableHead>
          <tr>
            <TableHeaderCell>Страница</TableHeaderCell>
            <TableHeaderCell>URL</TableHeaderCell>
            <TableHeaderCell>Тип</TableHeaderCell>
            <TableHeaderCell>SEO</TableHeaderCell>
            <TableHeaderCell className="text-right">Действия</TableHeaderCell>
          </tr>
        </TableHead>
        <tbody>
          {pages.map((page) => {
            const path = pagePath(page);
            return (
              <TableRow key={page.id}>
                <TableCell>
                  <div>
                    <p className="font-semibold text-[#26382d]">{page.title}</p>
                    <p className="mt-1 text-xs text-[#789083]">
                      {page.page_type === 'system' ? systemPageName(page) : page.template || 'basic'}
                    </p>
                  </div>
                </TableCell>
                <TableCell>
                  {path ? (
                    <a className="inline-flex items-center gap-2 text-sm font-semibold text-[#2f7d4b] hover:underline" href={path} target="_blank" rel="noreferrer">
                      {path}
                      <ExternalLink className="h-3.5 w-3.5" />
                    </a>
                  ) : (
                    <span className="text-[#789083]">—</span>
                  )}
                </TableCell>
                <TableCell>
                  <Badge tone={page.page_type === 'system' ? 'blue' : 'green'}>
                    {page.page_type === 'system' ? 'Системная' : 'CMS'}
                  </Badge>
                </TableCell>
                <TableCell>
                  <div className="flex flex-wrap gap-2">
                    <Badge tone={page.is_published ? 'green' : 'gray'}>{page.is_published ? 'Опубликована' : 'Черновик'}</Badge>
                    <Badge tone={page.is_indexable ? 'green' : 'amber'}>{page.is_indexable ? 'index' : 'noindex'}</Badge>
                    {page.show_in_sitemap ? <Badge tone="blue">sitemap</Badge> : null}
                  </div>
                </TableCell>
                <TableCell className="text-right">
                  <div className="inline-flex items-center gap-2">
                    <Button type="button" size="sm" variant="outline" onClick={() => onEdit(page)}>
                      <Edit2 className="h-4 w-4" />
                      Изменить
                    </Button>
                    {page.page_type === 'custom' ? (
                      <Button type="button" size="sm" variant="danger" onClick={() => onRemove(page)}>
                        <Trash2 className="h-4 w-4" />
                        Удалить
                      </Button>
                    ) : null}
                  </div>
                </TableCell>
              </TableRow>
            );
          })}
        </tbody>
      </AdminTable>
    </div>
  );
}

export function pagePath(page: CmsPage) {
  if (page.page_type === 'system') {
    return page.system_key ? systemPagePaths[page.system_key] ?? null : null;
  }

  return page.slug_path ? `/${page.slug_path}` : null;
}

function systemPageName(page: CmsPage) {
  return page.system_key ? systemPageLabels[page.system_key] ?? page.system_key : 'Системная страница';
}

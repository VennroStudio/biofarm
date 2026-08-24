import { Heart, Trash2 } from 'lucide-react';
import type { FavoriteProduct } from '../../../site/api';
import { Button, Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../../site/ui';

type FavoritesPanelProps = {
  favorites: FavoriteProduct[];
  onRemove: (product: FavoriteProduct) => void;
};

export function FavoritesPanel({ favorites, onRemove }: FavoritesPanelProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Heart className="h-5 w-5 text-primary" />
          Избранное
        </CardTitle>
        <CardDescription>Сохраненные товары для быстрого перехода к покупке.</CardDescription>
      </CardHeader>
      <CardContent>
        {favorites.length === 0 ? (
          <div className="rounded-lg border border-dashed p-6 text-center text-muted-foreground">
            В избранном пока нет товаров.
          </div>
        ) : (
          <div className="grid gap-4 md:grid-cols-2">
            {favorites.map((product) => (
              <article className="flex gap-4 rounded-lg border bg-background p-4" key={product.id}>
                <a className="h-24 w-24 flex-shrink-0 overflow-hidden rounded-lg bg-white" href={`/product/${product.slug}`}>
                  <img
                    className="h-full w-full object-cover"
                    src={product.image}
                    alt={product.imageAlt || product.title}
                    loading="lazy"
                  />
                </a>
                <div className="min-w-0 flex-1">
                  <a className="font-semibold text-foreground transition-colors hover:text-primary" href={`/product/${product.slug}`}>
                    {product.title}
                  </a>
                  <p className="mt-1 text-sm text-muted-foreground">{product.weight}</p>
                  <p className="mt-2 text-lg font-bold text-primary">{product.price.toLocaleString('ru-RU')} ₽</p>
                  <Button className="mt-3" size="sm" variant="outline" onClick={() => onRemove(product)}>
                    <Trash2 className="h-4 w-4" />
                    Убрать
                  </Button>
                </div>
              </article>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
}

import { useState, useEffect } from 'react';
import { Star, Plus, Image as ImageIcon, X, Edit, Trash2, Save } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Pagination, PaginationContent, PaginationItem, PaginationLink, PaginationNext, PaginationPrevious } from '@/components/ui/pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { reviewsApi, Review } from '@/data/reviews';
import { products } from '@/data/products';
import { useToast } from '@/hooks/use-toast';

const REVIEWS_PER_PAGE = 10;

const AdminReviews = () => {
  const { toast } = useToast();
  const [reviews, setReviews] = useState<Review[]>([]);
  const [currentPage, setCurrentPage] = useState(1);
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingReview, setEditingReview] = useState<Review | null>(null);
  const [form, setForm] = useState({
    productId: '',
    userName: '',
    rating: 5,
    text: '',
    source: 'site' as 'site' | 'wildberries' | 'ozon',
    images: [] as string[],
  });
  const [imageUrl, setImageUrl] = useState('');

  useEffect(() => {
    reviewsApi.getAllReviews().then(setReviews);
  }, []);

  const totalPages = Math.ceil(reviews.length / REVIEWS_PER_PAGE);
  const paginatedReviews = reviews.slice(
    (currentPage - 1) * REVIEWS_PER_PAGE,
    currentPage * REVIEWS_PER_PAGE
  );

  const resetForm = () => {
    setForm({ productId: '', userName: '', rating: 5, text: '', source: 'site', images: [] });
    setEditingReview(null);
  };

  const handleDelete = async (id: string) => {
    await reviewsApi.deleteReview(id);
    setReviews(prev => prev.filter(r => r.id !== id));
    toast({ title: 'Отзыв удалён' });
  };

  const handleEdit = (review: Review) => {
    setEditingReview(review);
    setForm({
      productId: String(review.productId),
      userName: review.userName,
      rating: review.rating,
      text: review.text,
      source: (review.source as 'site' | 'wildberries' | 'ozon') || 'site',
      images: review.images || [],
    });
    setIsDialogOpen(true);
  };

  const handleAddImage = () => {
    if (imageUrl.trim()) {
      setForm(prev => ({ ...prev, images: [...prev.images, imageUrl.trim()] }));
      setImageUrl('');
    }
  };

  const handleRemoveImage = (index: number) => {
    setForm(prev => ({ ...prev, images: prev.images.filter((_, i) => i !== index) }));
  };

  const handleSaveReview = async () => {
    if (!form.productId || !form.userName || !form.text) {
      toast({ title: 'Заполните все обязательные поля', variant: 'destructive' });
      return;
    }

    if (editingReview) {
      // Update existing review
      const updatedReview: Review = {
        ...editingReview,
        productId: Number(form.productId),
        userName: form.userName,
        rating: form.rating,
        text: form.text,
        source: form.source,
        images: form.images.length > 0 ? form.images : undefined,
      };
      setReviews(prev => prev.map(r => r.id === editingReview.id ? updatedReview : r));
      toast({ title: 'Отзыв обновлён' });
    } else {
      // Add new review
      const review = await reviewsApi.addReview({
        productId: Number(form.productId),
        userId: 'admin',
        userName: form.userName,
        rating: form.rating,
        text: form.text,
        source: form.source,
        images: form.images.length > 0 ? form.images : undefined,
      });
      await reviewsApi.approveReview(review.id);
      setReviews(prev => [{ ...review, isApproved: true }, ...prev]);
      toast({ title: 'Отзыв добавлен' });
    }
    
    resetForm();
    setIsDialogOpen(false);
  };

  const renderStars = (rating: number) => (
    <div className="flex gap-0.5">
      {[1, 2, 3, 4, 5].map((star) => (
        <Star key={star} className={`h-4 w-4 ${star <= rating ? 'fill-amber-400 text-amber-400' : 'text-gray-300'}`} />
      ))}
    </div>
  );

  const sourceLabels: Record<string, string> = { site: 'Сайт', wildberries: 'WB', ozon: 'Ozon' };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Отзывы</h1>
          <p className="text-muted-foreground">Всего отзывов: {reviews.length}</p>
        </div>
        
        <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if (!open) resetForm(); }}>
          <DialogTrigger asChild>
            <Button><Plus className="h-4 w-4 mr-2" />Добавить отзыв</Button>
          </DialogTrigger>
          <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>{editingReview ? 'Редактировать отзыв' : 'Добавить отзыв'}</DialogTitle>
              <DialogDescription>{editingReview ? 'Измените данные отзыва' : 'Создайте отзыв вручную'}</DialogDescription>
            </DialogHeader>
            
            <div className="space-y-4 py-4">
              <div className="space-y-2">
                <Label>Товар *</Label>
                <Select value={form.productId} onValueChange={(v) => setForm(prev => ({ ...prev, productId: v }))}>
                  <SelectTrigger><SelectValue placeholder="Выберите товар" /></SelectTrigger>
                  <SelectContent>
                    {products.map(product => (
                      <SelectItem key={product.id} value={String(product.id)}>{product.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              
              <div className="space-y-2">
                <Label>Имя автора *</Label>
                <Input value={form.userName} onChange={(e) => setForm(prev => ({ ...prev, userName: e.target.value }))} placeholder="Иван Иванов" />
              </div>
              
              <div className="space-y-2">
                <Label>Рейтинг</Label>
                <div className="flex gap-1">
                  {[1, 2, 3, 4, 5].map((star) => (
                    <button key={star} type="button" onClick={() => setForm(prev => ({ ...prev, rating: star }))} className="p-1">
                      <Star className={`h-6 w-6 ${star <= form.rating ? 'fill-amber-400 text-amber-400' : 'text-gray-300'}`} />
                    </button>
                  ))}
                </div>
              </div>
              
              <div className="space-y-2">
                <Label>Текст отзыва *</Label>
                <Textarea value={form.text} onChange={(e) => setForm(prev => ({ ...prev, text: e.target.value }))} placeholder="Отзыв покупателя..." rows={4} />
              </div>
              
              <div className="space-y-2">
                <Label>Источник</Label>
                <Select value={form.source} onValueChange={(v: 'site' | 'wildberries' | 'ozon') => setForm(prev => ({ ...prev, source: v }))}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="site">Сайт</SelectItem>
                    <SelectItem value="wildberries">Wildberries</SelectItem>
                    <SelectItem value="ozon">Ozon</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              
              <div className="space-y-2">
                <Label>Фотографии</Label>
                <div className="flex gap-2">
                  <Input value={imageUrl} onChange={(e) => setImageUrl(e.target.value)} placeholder="URL изображения" />
                  <Button type="button" variant="outline" onClick={handleAddImage}><ImageIcon className="h-4 w-4" /></Button>
                </div>
                {form.images.length > 0 && (
                  <div className="flex gap-2 flex-wrap mt-2">
                    {form.images.map((img, i) => (
                      <div key={i} className="relative">
                        <img src={img} alt="" className="w-16 h-16 object-cover rounded" />
                        <button type="button" onClick={() => handleRemoveImage(i)} className="absolute -top-2 -right-2 bg-destructive text-destructive-foreground rounded-full p-1">
                          <X className="h-3 w-3" />
                        </button>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>
            
            <div className="flex justify-end gap-2">
              <Button variant="outline" onClick={() => { setIsDialogOpen(false); resetForm(); }}>Отмена</Button>
              <Button onClick={handleSaveReview}>
                {editingReview ? <><Save className="h-4 w-4 mr-2" />Сохранить</> : 'Добавить'}
              </Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardContent className="p-0">
          {paginatedReviews.length === 0 ? (
            <div className="py-12 text-center">
              <Star className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
              <p className="text-muted-foreground">Нет отзывов</p>
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Автор</TableHead>
                  <TableHead>Товар</TableHead>
                  <TableHead>Рейтинг</TableHead>
                  <TableHead>Источник</TableHead>
                  <TableHead>Фото</TableHead>
                  <TableHead className="text-right">Действия</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {paginatedReviews.map(review => (
                  <TableRow key={review.id}>
                    <TableCell className="font-medium">{review.userName}</TableCell>
                    <TableCell className="max-w-[200px] truncate">
                      {products.find(p => p.id === review.productId)?.name || `ID: ${review.productId}`}
                    </TableCell>
                    <TableCell>{renderStars(review.rating)}</TableCell>
                    <TableCell>
                      <Badge variant="outline">{sourceLabels[review.source || 'site']}</Badge>
                    </TableCell>
                    <TableCell>
                      {review.images && review.images.length > 0 ? (
                        <div className="flex gap-1">
                          {review.images.slice(0, 2).map((img, i) => (
                            <img key={i} src={img} alt="" className="w-8 h-8 object-cover rounded" />
                          ))}
                          {review.images.length > 2 && (
                            <span className="text-xs text-muted-foreground">+{review.images.length - 2}</span>
                          )}
                        </div>
                      ) : (
                        <span className="text-muted-foreground">—</span>
                      )}
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex justify-end gap-1">
                        <Button size="sm" variant="ghost" onClick={() => handleEdit(review)}>
                          <Edit className="h-4 w-4" />
                        </Button>
                        <Button size="sm" variant="ghost" className="text-destructive" onClick={() => handleDelete(review.id)}>
                          <Trash2 className="h-4 w-4" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {totalPages > 1 && (
        <Pagination>
          <PaginationContent>
            <PaginationItem>
              <PaginationPrevious onClick={() => setCurrentPage(p => Math.max(1, p - 1))} className={currentPage === 1 ? 'pointer-events-none opacity-50' : 'cursor-pointer'} />
            </PaginationItem>
            {Array.from({ length: totalPages }, (_, i) => i + 1).slice(Math.max(0, currentPage - 3), currentPage + 2).map(page => (
              <PaginationItem key={page}>
                <PaginationLink onClick={() => setCurrentPage(page)} isActive={currentPage === page} className="cursor-pointer">{page}</PaginationLink>
              </PaginationItem>
            ))}
            <PaginationItem>
              <PaginationNext onClick={() => setCurrentPage(p => Math.min(totalPages, p + 1))} className={currentPage === totalPages ? 'pointer-events-none opacity-50' : 'cursor-pointer'} />
            </PaginationItem>
          </PaginationContent>
        </Pagination>
      )}
    </div>
  );
};

export default AdminReviews;

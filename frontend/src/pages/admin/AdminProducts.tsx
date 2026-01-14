import { useState } from 'react';
import { motion } from 'framer-motion';
import { Plus, Search, Edit, Trash2, ExternalLink } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { products as initialProducts, categories, Product } from '@/data/products';
import { useToast } from '@/hooks/use-toast';

const AdminProducts = () => {
  const { toast } = useToast();
  const [productList, setProductList] = useState<Product[]>(initialProducts);
  const [search, setSearch] = useState('');
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingProduct, setEditingProduct] = useState<Product | null>(null);
  
  const [form, setForm] = useState({
    name: '',
    category: 'honey',
    price: '',
    oldPrice: '',
    weight: '',
    shortDescription: '',
    description: '',
    ingredients: '',
    image: '',
    images: [] as string[],
    badge: '',
    wbLink: '',
    ozonLink: '',
  });
  const [imageUrl, setImageUrl] = useState('');

  const filteredProducts = productList.filter(p => 
    p.name.toLowerCase().includes(search.toLowerCase())
  );

  const resetForm = () => {
    setForm({
      name: '', category: 'honey', price: '', oldPrice: '', weight: '',
      shortDescription: '', description: '', ingredients: '', image: '', images: [], badge: '', wbLink: '', ozonLink: '',
    });
    setEditingProduct(null);
    setImageUrl('');
  };

  const handleEdit = (product: Product) => {
    setEditingProduct(product);
    setForm({
      name: product.name,
      category: product.category,
      price: product.price.toString(),
      oldPrice: product.oldPrice?.toString() || '',
      weight: product.weight,
      shortDescription: product.shortDescription,
      description: product.description,
      ingredients: product.ingredients || '',
      image: product.image,
      images: product.images || [],
      badge: product.badge || '',
      wbLink: product.wbLink || '',
      ozonLink: product.ozonLink || '',
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

  const handleSave = () => {
    const slug = form.name.toLowerCase().replace(/\s+/g, '-').replace(/[^\w-]/g, '');
    
    if (editingProduct) {
      setProductList(prev => prev.map(p => 
        p.id === editingProduct.id 
          ? { ...p, ...form, price: Number(form.price), oldPrice: form.oldPrice ? Number(form.oldPrice) : undefined, images: form.images, ingredients: form.ingredients || undefined, slug }
          : p
      ));
      toast({ title: 'Товар обновлён' });
    } else {
      const newProduct: Product = {
        id: Date.now(),
        slug,
        name: form.name,
        category: form.category,
        price: Number(form.price),
        oldPrice: form.oldPrice ? Number(form.oldPrice) : undefined,
        weight: form.weight,
        shortDescription: form.shortDescription,
        description: form.description,
        ingredients: form.ingredients || undefined,
        image: form.image || 'https://images.unsplash.com/photo-1587049352846-4a222e784d38?w=400&q=80',
        images: form.images,
        badge: form.badge || undefined,
        wbLink: form.wbLink || undefined,
        ozonLink: form.ozonLink || undefined,
      };
      setProductList(prev => [...prev, newProduct]);
      toast({ title: 'Товар добавлен' });
    }
    
    setIsDialogOpen(false);
    resetForm();
  };

  const handleDelete = (id: number) => {
    setProductList(prev => prev.filter(p => p.id !== id));
    toast({ title: 'Товар удалён' });
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold">Товары</h1>
          <p className="text-muted-foreground">Управление каталогом товаров</p>
        </div>
        <Dialog open={isDialogOpen} onOpenChange={(open) => { setIsDialogOpen(open); if (!open) resetForm(); }}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="h-4 w-4 mr-2" />
              Добавить товар
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>{editingProduct ? 'Редактировать товар' : 'Новый товар'}</DialogTitle>
              <DialogDescription>Заполните информацию о товаре</DialogDescription>
            </DialogHeader>
            <div className="grid gap-4 py-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Название *</Label>
                  <Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
                </div>
                <div className="space-y-2">
                  <Label>Категория *</Label>
                  <Select value={form.category} onValueChange={(v) => setForm({ ...form, category: v })}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                      {categories.filter(c => c.id !== 'all').map(c => (
                        <SelectItem key={c.id} value={c.id}>{c.label}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              </div>
              <div className="grid grid-cols-3 gap-4">
                <div className="space-y-2">
                  <Label>Цена *</Label>
                  <Input type="number" value={form.price} onChange={(e) => setForm({ ...form, price: e.target.value })} />
                </div>
                <div className="space-y-2">
                  <Label>Старая цена</Label>
                  <Input type="number" value={form.oldPrice} onChange={(e) => setForm({ ...form, oldPrice: e.target.value })} />
                </div>
                <div className="space-y-2">
                  <Label>Вес/Объём *</Label>
                  <Input value={form.weight} onChange={(e) => setForm({ ...form, weight: e.target.value })} placeholder="500 г" />
                </div>
              </div>
              <div className="space-y-2">
                <Label>Краткое описание *</Label>
                <Input value={form.shortDescription} onChange={(e) => setForm({ ...form, shortDescription: e.target.value })} />
              </div>
              <div className="space-y-2">
                <Label>Полное описание *</Label>
                <RichTextEditor 
                  content={form.description} 
                  onChange={(content) => setForm({ ...form, description: content })} 
                />
              </div>
              <div className="space-y-2">
                <Label>Состав</Label>
                <Input value={form.ingredients} onChange={(e) => setForm({ ...form, ingredients: e.target.value })} placeholder="100% натуральный мёд" />
              </div>
              <div className="space-y-2">
                <Label>URL основного изображения</Label>
                <Input value={form.image} onChange={(e) => setForm({ ...form, image: e.target.value })} placeholder="https://..." />
              </div>
              <div className="space-y-2">
                <Label>Дополнительные фото</Label>
                <div className="flex gap-2">
                  <Input value={imageUrl} onChange={(e) => setImageUrl(e.target.value)} placeholder="URL изображения" />
                  <Button type="button" variant="outline" onClick={handleAddImage}>Добавить</Button>
                </div>
                {form.images.length > 0 && (
                  <div className="flex gap-2 flex-wrap mt-2">
                    {form.images.map((img, i) => (
                      <div key={i} className="relative group">
                        <img src={img} alt="" className="w-16 h-16 object-cover rounded" />
                        <button 
                          type="button" 
                          onClick={() => handleRemoveImage(i)} 
                          className="absolute -top-2 -right-2 bg-destructive text-destructive-foreground rounded-full w-5 h-5 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                        >
                          ×
                        </button>
                      </div>
                    ))}
                  </div>
                )}
              </div>
              <div className="space-y-2">
                <Label>Бейдж</Label>
                <Input value={form.badge} onChange={(e) => setForm({ ...form, badge: e.target.value })} placeholder="Хит продаж" />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Ссылка Wildberries</Label>
                  <Input value={form.wbLink} onChange={(e) => setForm({ ...form, wbLink: e.target.value })} />
                </div>
                <div className="space-y-2">
                  <Label>Ссылка Ozon</Label>
                  <Input value={form.ozonLink} onChange={(e) => setForm({ ...form, ozonLink: e.target.value })} />
                </div>
              </div>
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setIsDialogOpen(false)}>Отмена</Button>
              <Button onClick={handleSave} disabled={!form.name || !form.price || !form.weight}>
                {editingProduct ? 'Сохранить' : 'Добавить'}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>

      <Card>
        <CardHeader>
          <div className="flex items-center gap-4">
            <div className="relative flex-1 max-w-sm">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input 
                placeholder="Поиск товаров..." 
                value={search} 
                onChange={(e) => setSearch(e.target.value)}
                className="pl-10"
              />
            </div>
            <Badge variant="secondary">{filteredProducts.length} товаров</Badge>
          </div>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-16">Фото</TableHead>
                  <TableHead>Название</TableHead>
                  <TableHead>Категория</TableHead>
                  <TableHead>Цена</TableHead>
                  <TableHead>Маркетплейсы</TableHead>
                  <TableHead className="text-right">Действия</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredProducts.map((product, index) => (
                  <motion.tr
                    key={product.id}
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    transition={{ delay: index * 0.05 }}
                    className="group"
                  >
                    <TableCell>
                      <img src={product.image} alt={product.name} className="w-12 h-12 object-cover rounded" />
                    </TableCell>
                    <TableCell>
                      <div>
                        <p className="font-medium">{product.name}</p>
                        <p className="text-sm text-muted-foreground">{product.weight}</p>
                      </div>
                    </TableCell>
                    <TableCell>
                      <Badge variant="outline">
                        {categories.find(c => c.id === product.category)?.label}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <div>
                        <p className="font-medium">{product.price} ₽</p>
                        {product.oldPrice && (
                          <p className="text-sm text-muted-foreground line-through">{product.oldPrice} ₽</p>
                        )}
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className="flex gap-1">
                        {product.wbLink && (
                          <a href={product.wbLink} target="_blank" rel="noopener noreferrer">
                            <Badge variant="secondary" className="cursor-pointer">WB</Badge>
                          </a>
                        )}
                        {product.ozonLink && (
                          <a href={product.ozonLink} target="_blank" rel="noopener noreferrer">
                            <Badge variant="secondary" className="cursor-pointer">Ozon</Badge>
                          </a>
                        )}
                      </div>
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex justify-end gap-2">
                        <Button variant="ghost" size="icon" onClick={() => handleEdit(product)}>
                          <Edit className="h-4 w-4" />
                        </Button>
                        <Button variant="ghost" size="icon" className="text-destructive" onClick={() => handleDelete(product.id)}>
                          <Trash2 className="h-4 w-4" />
                        </Button>
                      </div>
                    </TableCell>
                  </motion.tr>
                ))}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default AdminProducts;
